<?php
/*
 * Copyright 2017 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventFormPage extends Page
{
    /**
     * Display event form or submit event
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        if (!$context->calendar->canWrite($context->user)) {
            throw new PermissionException();
        }

        $form = $this->eventForm($context);

        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processForm($context, $form->getData());
        }
        
        // else
        return new Response($context->twig->render("event_form.html.twig", array('form' => $form->createView())));
    }

    /**
     * @param Context $context
     * @return Form
     */
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
        $builder->add(
            'subject',
            TextType::class,
            array('attr' => array('autocomplete' => 'off', 'maxlength' => $context->calendar->getMaxSubjectLength()),
                'label' => _('Subject'), 'constraints' => new Assert\NotBlank())
        )
        ->add('description', TextareaType::class, array('required' => false))
        ->add(
            'start',
            DateTimeType::class,
            array('label' => __('from-label'), 'date_widget' => 'single_text', 'time_widget' => 'single_text',
                'data' => $default_date->setTime(17, 0))
        )
        ->add(
            'end',
            DateTimeType::class,
            array('label' => __('to-label'), 'date_widget' => 'single_text', 'time_widget' => 'single_text',
                'data' => (clone $default_date)->setTime(18, 0))
        )
        ->add(
            'time_type',
            ChoiceType::class,
            array('label' => __('time-type-label'),
                'choices' => array(
                    __('normal-label') => 0,
                    __('full-day-label') => 1,
                    __('to-be-announced-label') => 2))
        )
        ->add(
            'repeats',
            ChoiceType::class,
            array('label' => __('repeats-label'),
                'choices' => array(
                    __('never-label') => '0',
                    __('daily-label') => 'D',
                    __('weekly-label') => 'W',
                    __('monthly-label') => 'M',
                    __('yearly-label') => 'Y'))
        )
        ->add(
            'frequency',
            IntegerType::class,
            array('constraints' => new Assert\GreaterThan(0), 'data' => 1)
        )
        ->add('until', DateType::class, array('label' => __('until-label'), 'widget' => 'single_text'));

        //echo "<pre>"; var_dump($context->request); echo "</pre>";
        if ($context->request->get('eid') !== null) {
            $eid = $context->request->get('eid');
            $event = $context->db->getEvent($eid);
            $occs = $event->getOccurrences();
            $occurrence = $occs[0];
            $builder->add(
                'modify',
                CheckboxType::class,
                array('label' => __('change-event-date-time-label'), 'required' => false)
            );
            $builder->add('eid', HiddenType::class, array('data' => $eid));
            $builder->get('subject')->setData($event->getRawSubject());
            $builder->get('description')->setData($event->getDescription());
            $builder->get('start')->setData($occurrence->getStart());
            $builder->get('end')->setData($occurrence->getEnd());
            $builder->add(
                'save',
                SubmitType::class,
                array('label' => __('modify-event-button'), 'attr' => array('class' => 'btn btn-primary'))
            );
        } else {
            $builder->add(
                'save',
                SubmitType::class,
                array('label' => __('create-event-button'), 'attr' => array('class' => 'btn btn-primary'))
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
                    if ($data['end']->getTimestamp() < $data['start']->getTimestamp()) {
                        $form->get('end')->addError(
                            new FormError(__('end-before-start-date-time-error'))
                        );
                    }
                }
            }
        );

        return $builder->getForm();
    }

    /**
     * @param Context $context
     * @param array   $data
     * @return Response
     */
    private function processForm(Context $context, $data)
    {
        // When modifying events, this is the value of the checkbox that
        //   determines if the date should change
        $modify_occur = !isset($data['eid']) || !empty($data['modify']);
    
        if (!$context->calendar->canWrite($context->user)) {
            permission_error();
        }
    
        $catid = empty($data['catid']) ? null : $data['catid'];
    
        if (!isset($data['eid'])) {
            $modify = false;
            $eid = $context->db->createEvent(
                $context->calendar->getCid(),
                $context->user->getUid(),
                $data["subject"],
                (string) $data["description"],
                $catid
            );
        } else {
            $modify = true;
            $eid = $data['eid'];
            $context->db->modifyEvent(
                $eid,
                $data['subject'],
                $data['description'],
                $catid
            );
            if ($modify_occur) {
                $context->db->deleteOccurrences($eid);
            }
        }
    
        /*foreach($calendar->get_fields() as $field) {
        $fid = $field['fid'];
        if(empty($vars["phpc-field-$fid"])) {
        if($field['required'])
        throw new Exception(sprintf(__('Field "%s" is required but was not set.'), $field['name']));
        continue;
        }
        $phpcdb->add_event_field($eid, $fid, $vars["phpc-field-$fid"]);
        }*/
    
        if ($modify_occur) {
            $occurrences = 0;
            
            if ($data['repeats'] == '0') {
                $context->db->createOccurrence($eid, $data['time_type'], $data['start'], $data['end']);
            } else {
                $interval = new \DateInterval('P'.$data['frequency'].$data['repeats']);
                
                echo "days between: " . days_between($data['start'], $data['until']);

                while ($occurrences <= 730 && days_between($data['start'], $data['until']) >= 0) {
                    $oid = $context->db->createOccurrence($eid, $data['time_type'], $data['start'], $data['end']);
                    $occurrences++;
        
                    $data['start']->add($interval);
                    $data['end']->add($interval);
                }
            }
        }
        $context->addMessage(__($modify ? "modified-event-notification" : "created-event-notification"));
        return new RedirectResponse($context->createEventUrl('display_event', $eid));
    }
}

function display_form()
{

    $categories = new FormDropdownQuestion('catid', __('Category'));
    $categories->add_option('', __('None'));
    $have_categories = false;
    foreach ($phpc_cal->get_visible_categories($phpc_user->get_uid()) as $category) {
        $categories->add_option($category['catid'], $category['name']);
        $have_categories = true;
    }
    if ($have_categories) {
        $form->add_part($categories);
    }

    foreach ($phpc_cal->get_fields() as $field) {
        $form->add_part(new FormFreeQuestion('phpc-field-'.$field['fid'], $field['name']));
    }

    if (isset($vars['eid'])) {
        foreach ($event->get_fields() as $field) {
            $defaults["phpc-field-{$field['fid']}"] = $field['value'];
        }

        if (!empty($event->catid)) {
            $defaults['catid'] = $event->catid;
        }

        add_repeat_defaults($occs, $defaults);
    }
    return $form->get_form($defaults);
}

function add_repeat_defaults($occs, &$defaults)
{
    // TODO: Handle unevenly spaced occurrences

    $defaults['repeats'] = 'never';

    if (sizeof($occs) < 2) {
        return;
    }

    $event = $occs[0];
    $day = $event->get_start_day();
    $month = $event->get_start_month();
    $year = $event->get_start_year();

    // Test if they repeat every N years
    $nyears = $occs[1]->get_start_year() - $event->get_start_year();
    $repeats_yearly = true;
    $nmonths = ($occs[1]->get_start_year() - $year) * 12
        + $occs[1]->get_start_month() - $month;
    $repeats_monthly = true;
    $ndays = days_between($event->get_start_ts(), $occs[1]->get_start_ts());
    $repeats_daily = true;

    for ($i = 1; $i < sizeof($occs); $i++) {
        $cur_occ = $occs[$i];
        $cur_year = $cur_occ->get_start_year();
        $cur_month = $cur_occ->get_start_month();
        $cur_day = $cur_occ->get_start_day();

        // Check year
        $cur_nyears = $cur_year - $occs[$i - 1]->get_start_year();
        if ($cur_day != $day || $cur_month != $month
            || $cur_nyears != $nyears
        ) {
            $repeats_yearly = false;
        }

        // Check month
        $cur_nmonths = ($cur_year - $occs[$i - 1]->get_start_year())
        * 12 + $cur_month - $occs[$i - 1]->get_start_month();
        if ($cur_day != $day || $cur_nmonths != $nmonths) {
            $repeats_monthly = false;
        }

        // Check day
        $cur_ndays = days_between(
            $occs[$i - 1]->get_start_ts(),
            $occs[$i]->get_start_ts()
        );
        if ($cur_ndays != $ndays) {
            $repeats_daily = false;
        }
    }

    $defaults['yearly-until-date'] = "$cur_month/$cur_day/$cur_year";
    $defaults['monthly-until-date'] = "$cur_month/$cur_day/$cur_year";
    $defaults['weekly-until-date'] = "$cur_month/$cur_day/$cur_year";
    $defaults['daily-until-date'] = "$cur_month/$cur_day/$cur_year";

    if ($repeats_daily) {
        // repeats weekly
        if ($ndays % 7 == 0) {
            $defaults['repeats'] = 'weekly';
            $defaults['every-week'] = $ndays / 7;
        } else {
            $defaults['every-week'] = 1;

            // repeats daily
            $defaults['repeats'] = 'daily';
            $defaults['every-day'] = $ndays;
        }
    } else {
        $defaults['every-day'] = 1;
        $defaults['every-week'] = 1;
    }

    if ($repeats_monthly) {
        $defaults['repeats'] = 'monthly';
        $defaults['every-month'] = $nmonths;
    } else {
        $defaults['every-month'] = 1;
    }

    if ($repeats_yearly) {
        $defaults['repeats'] = 'yearly';
        $defaults['every-year'] = $nyears;
    } else {
        $defaults['every-year'] = 1;
    }
}
