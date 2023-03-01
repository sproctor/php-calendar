<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Form\EventFormType;
use App\Repository\CalendarRepository;
use App\Repository\UserPermissionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    const MAX_OCCURRENCES = 1000;

    public function __construct(
        private EntityManagerInterface    $entity_manager,
        private CalendarRepository        $calendar_repository,
        private UserPermissionsRepository $user_permissions_repository,
    )
    {
    }

    #[Route('/event/{eid}', name: 'app_event')]
    public function view(int $eid): Response
    {
        return $this->render('event/index.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }

    #[Route('/calendar/{cid}/event/new', name: 'create_event')]
    public function create_event(
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

        // TODO: factor this out
        $user_permissions = null;
        if ($user !== null) {
            $user_permissions = $this->user_permissions_repository->getUserPermissions($cid, $user->getUid());
        }
        if ($user_permissions === null) {
            $user_permissions = new UserPermissions($cid, $user?->getUid());
        }
        $default_permissions = $this->user_permissions_repository->getUserPermissions($cid, null);
        $permissions = get_actual_permissions($user_permissions, $default_permissions, $user?->isAdmin() == true);

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
