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

use Symfony\Component\Form;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
 
class CalendarForm
{
    /**
     * @return Form
    */
    public function getForm(Context $context, $cid = null)
    {
        $builder = $context->getFormFactory()->createBuilder();
        $builder->add('title', TextType::class, array('label' => __('Calendar Title')))
        ->add(
            'week_start',
            ChoiceType::class,
            array(
                'choices' => array(
                    __('Sunday') => 0,
                    __('Monday') => 1,
                    __('Saturday') => 6
                ),
                'label' => __('Week Start')
            )
        )
        ->add('hours_24', CheckboxType::class, array('label' => __('24 Hour Time'), 'required' => false))
        ->add('subject_max', IntegerType::class, array('label' => __('Maximum Subject Length'), 'data' => 50))
        ->add('events_max', IntegerType::class, array('label' => __('Events Display Daily Maximum'), 'data' => 8))
        ->add(
            'anon_permission',
            ChoiceType::class,
            array('label' => __('Public Permissions'), 'choices' => array(
                __('Cannot read nor write events') => 0,
                __('Can read but not write events') => 1,
                __('Can create but not modify events') => 2,
                __('Can create and modify events') => 3
            ))
        )
        ->add(
            'timezone',
            TimezoneType::class,
            array(
                'label' => __('Default Timezone'),
                'data' => $context->user->getTimezone(),
                'preferred_choices' => array('America/New_York')
            )
        )
        ->add('language', ChoiceType::class, array('label' => __('Default Language'), 'choices' => get_languages()))
        ->add(
            'date_format',
            ChoiceType::class,
            array('label' => __('Date Format'), 'choices' => array(
                __("Month Day Year") => 0,
                __("Year Month Day") => 1,
                __("Day Month Year") => 2
            ))
        );
        /*->add(
            'submit',
            SubmitType::class,
            array('label' => $cid === null ? __('Create Calendar') : __('Update Calendar'))
        );*/
        if ($cid !== null) {
            $calendar = $context->db->getCalendar($cid);
            $builder->get('title')->setData($calendar->getTitle());
            $builder->get('week_start')->setData($calendar->getWeekStart());
            $builder->get('hours_24')->setData($calendar->is24Hour());
            $builder->get('subject_max')->setData($calendar->getSubjectMax());
            $builder->get('events_max')->setData($calendar->getMaxDisplayEvents());
            $builder->get('anon_permission')->setData($calendar->getAnonPermission());
            $builder->get('timezone')->setData($calendar->getTimezone());
            $builder->get('language')->setData($calendar->getLanguage());
            $builder->get('date_format')->setData($calendar->getDateFormat());
        }

        return $builder->getForm();
    }
}
