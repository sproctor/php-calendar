<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
    public function create_event(int $cid): Response
    {
        return $this->render('event/form.html.twig');
    }

    private function eventForm(Context $context)
    {
        $builder = $context->getFormFactory()->createBuilder();

        $default_date = new \DateTime();
        if ($context->request->get('year') !== null && $context->request->get('month') !== null) {
            $default_date->setDate(
                $context->request->get('year'),
                $context->request->get('month'),
                $context->request->get('day', 1)
            );
        }
        $default_date->setTime(17, 0);
        $end_datetime = clone $default_date;
        $end_datetime->setTime(18, 0);


        //echo "<pre>"; var_dump($context->request); echo "</pre>";
        if ($context->request->get('eid') !== null) {
            $eid = $context->request->get('eid');
            $event = $context->db->getEvent($eid);
            $occs = $event->getOccurrences();
            $occurrence = $occs[0];
            $builder->add(
                'modify',
                CheckboxType::class,
                ['label' => __('change-event-date-time-label'), 'required' => false]
            );
            $builder->add('eid', HiddenType::class, ['data' => $eid]);
            $builder->get('subject')->setData($event->getRawSubject());
            $builder->get('description')->setData($event->getDescription());
            $builder->get('start')->setData($occurrence->getStart());
            $builder->get('end')->setData($occurrence->getEnd());
            $builder->add(
                'save',
                SubmitType::class,
                ['label' => __('modify-event-button'), 'attr' => ['class' => 'btn btn-primary']]
            );
            $builder->get('delay_publish')->setData($occurrence->getPublishDate() != null);
            $builder->get('publish_datetime')->setData($occurrence->getPublishDate());
        } else {
            $builder->add(
                'save',
                SubmitType::class,
                ['label' => __('create-event-button'), 'attr' => ['class' => 'btn btn-primary']]
            );
        }

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

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $form->getData();
                if (!empty($data) && !empty($data['save']) && (empty($data['eid']) || $data['modify'])) {
                    if ($data['time_type'] == 0) {
                        $start = $data['start'];
                        $end = $data['end'];
                        $error_element = 'end';
                    } else {
                        $start = $data['start_date'];
                        $end = $data['end_date'];
                        $error_element = 'end_date';
                    }
                    if ($end->getTimestamp() < $start->getTimestamp()) {
                        $form->get($error_element)->addError(
                            new FormError(__('end-before-start-date-time-error'))
                        );
                    }
                }
            }
        );

        return $builder->getForm();
    }
}
