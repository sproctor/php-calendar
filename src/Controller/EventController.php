<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\User;
use App\Form\EventFormType;
use App\Repository\CalendarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/event/{eid}', name: 'app_event')]
    public function view(int $eid): Response
    {
        return $this->render('event/index.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }

    #[Route('/calendar/{cid}/event/new', name: 'create_event')]
    public function create_event(
        int $cid,
        Request $request,
        ?User $user,
        EntityManagerInterface $entity_manager,
        CalendarRepository $calendar_repository,
    ): Response
    {
        $calendar = $calendar_repository->find($cid);
        return $this->eventForm(
            $request,
            $entity_manager,
            new Event($calendar, $user),
            $calendar,
            new \DateTimeImmutable(),
            false,
        );
    }

    private function eventForm(
        Request $request,
        EntityManagerInterface $entity_manager,
        Event $event,
        Calendar $calendar,
        \DateTimeInterface $date,
        bool $modifying,
    ): Response
    {
        $default_date = \DateTime::createFromInterface($date);
        $default_date->setTime(17, 0);
        $end_datetime = clone $default_date;
        $end_datetime->setTime(18, 0);

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

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity_manager->persist($event);
            $entity_manager->flush();
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

//        $builder->addEventListener(
//            FormEvents::POST_SUBMIT,
//            function (FormEvent $event) {
//                $form = $event->getForm();
//                $data = $form->getData();
//                if (!empty($data) && !empty($data['save']) && (empty($data['eid']) || $data['modify'])) {
//                    if ($data['time_type'] == 0) {
//                        $start = $data['start'];
//                        $end = $data['end'];
//                        $error_element = 'end';
//                    } else {
//                        $start = $data['start_date'];
//                        $end = $data['end_date'];
//                        $error_element = 'end_date';
//                    }
//                    if ($end->getTimestamp() < $start->getTimestamp()) {
//                        $form->get($error_element)->addError(
//                            new FormError(__('end-before-start-date-time-error'))
//                        );
//                    }
//                }
//            }
//        );

        return $this->render('event/form.html.twig', ['form' => $form]);
    }
}
