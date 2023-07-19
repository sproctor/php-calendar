<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Form\EventFormType;
use App\Repository\CalendarRepository;
use App\Repository\EventRepository;
use App\Repository\UserPermissionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/event')]
class EventController extends AbstractController
{
    const MAX_OCCURRENCES = 1000;

    public function __construct(
        private EntityManagerInterface    $entity_manager,
        private CalendarRepository        $calendar_repository,
        private UserPermissionsRepository $user_permissions_repository,
        private EventRepository           $event_repository,
    )
    {
    }

    #[Route('/{eid}', name: 'event_view')]
    public function view(int $eid): Response
    {
        $event = $this->event_repository->find($eid);
        $calendar = $event->getCalendar();
        $user = $this->getUser();
        $permissions = $this->user_permissions_repository->getUserPermissions($calendar->getCid(), $user);

        return $this->render('event/view.html.twig', [
            'event' => $event,
            'user' => $user,
            'permissions' => $permissions,
            'calendar' => $calendar,
        ]);
    }

    #[Route('/create/{cid}', name: 'create_event')]
    public function createEvent(
        int     $cid,
        Request $request,
    ): Response
    {
        $calendar = $this->calendar_repository->find($cid);
        $user = $this->getUser();
        return $this->eventForm(
            $request,
            new Event($calendar, $user),
            $calendar,
            $user,
            new \DateTimeImmutable(),
            false,
        );
    }

    #[Route('/edit/{eid}', name: 'modify_event')]
    public function modifyEvent(
        int $eid,
        Request $request,
    ): Response
    {
        $event = $this->event_repository->find($eid);
        $user = $this->getUser();
        return $this->eventForm(
            $request,
            $event,
            $event->getCalendar(),
            $user,
            new \DateTimeImmutable(),
            true,
        );
    }

    private function eventForm(
        Request            $request,
        Event              $event,
        Calendar           $calendar,
        ?User              $user,
        \DateTimeInterface $date,
        bool               $modifying,
    ): Response
    {
        $default_date = \DateTime::createFromInterface($date);
        $default_date->setTime(17, 0);
        $end_datetime = clone $default_date;
        $end_datetime->setTime(18, 0);

        $cid = $calendar->getCid();
        
        $permissions = $this->user_permissions_repository->getUserPermissions($cid, $user);

        $form = $this->createForm(
            EventFormType::class,
            $event,
            [
                'maxlength' => $calendar->getMaxSubjectLength(),
                'date' => $default_date,
                'end' => $end_datetime,
                'modifying' => $modifying,
            ]
        );

        // Check constraints
        //                    if ($end->getTimestamp() < $start->getTimestamp()) {
//                        $form->get($error_element)->addError(
//                            new FormError(__('end-before-start-date-time-error'))
//                        );
//                    }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entity_manager->persist($event);

            if (!$modifying || $form->get('modify')->getData()) {
                $time_type = $form->get('time_type')->getData();
                if ($time_type === 0) {
                    $start = $form->get('start')->getData();
                    $end = $form->get('end')->getData();
                } else {
                    $start = $form->get('start_date')->getData();
                    $end = $form->get('end_date')->getData();
                }

                $repeats = $form->get('repeats')->getData();
                if ($repeats === '0') {
                    $occurrence = new Occurrence($event, $start, $end, $time_type);
                    $this->entity_manager->persist($occurrence);
                } else {
                    $interval = new \DateInterval('P'.$form->get('frequency')->getData().$repeats);

                    $until = $form->get('until')->getData();
                    echo "days between: " . days_between($start, $until);

                    $occurrence_count = 0;
                    while ($occurrence_count <= self::MAX_OCCURRENCES && days_between($start, $until) >= 0) {
                        $occurrence = new Occurrence($event, $start, $end, $time_type);
                        $occurrence_count++;
                        $this->entity_manager->persist($occurrence);

                        $start->add($interval);
                        $end->add($interval);
                    }
                }
            }
            $this->entity_manager->flush();
            return $this->redirectToRoute('event_view', ['eid' => $event->getEid()]);
        }
        //echo "<pre>"; var_dump($context->request); echo "</pre>";
//        if ($modifying) {
//            $occs = $event->getOccurrences();
//            $occurrence = $occs[0];
//        }

        /*
        $calendar_choices = array();
        foreach($context->db->getCalendars() as $calendar) {
        if($calendar->canWrite($context->getUser()))
        $calendar_choices[$calendar->getTitle()] = $calendar->getCID();
        }

        if(sizeof($calendar_choices) > 1) {
        $builder->add('cid', ChoiceType::class, array('choices' => $calendar_choices));
        } else {
        $builder->add('cid', HiddenType::class, array('data' => $context->getCalendar()->getCID()));
        }*/


        return $this->render('event/form.html.twig', [
            'form' => $form,
            'calendar' => $calendar,
            'permissions' => $permissions,
            'user' => $user,
        ]);
    }
}
