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
     * @param Context $context
     * @param Calendar|null $calendar
     * @return Form
    */
    public function getForm(Context $context, $calendar = null)
    {
        $builder = $context->getFormFactory()->createBuilder();
        $builder->add('title', TextType::class, array('label' => __('calendar-title-label')))
        ->add('subject_max', IntegerType::class, array('label' => __('max-subject-length-label'), 'data' => 50))
        ->add('events_max', IntegerType::class, array('label' => __('max-events-display-label'), 'data' => 8))
        ->add(
            'anon_permission',
            ChoiceType::class,
            array('label' => __('public-permissions-label'), 'choices' => array(
                __('no-read-no-write-events-label') => 0,
                __('read-no-write-events-label') => 1,
                __('read-create-no-modify-events-label') => 2,
                __('read-create-modify-events-label') => 3
            ))
        )
        ->add(
            'timezone',
            TimezoneType::class,
            array(
                'label' => __('default-timezone-label'),
                'data' => $context->user->getTimezone(),
                'preferred_choices' => array('America/New_York')
            )
        )
        ->add(
            'language',
            ChoiceType::class,
            array('label' => __('default-language-label'), 'choices' => get_language_mappings(), 'data' => 'en')
        );
        /*->add(
            'submit',
            SubmitType::class,
            array('label' => $cid === null ? __('Create Calendar') : __('Update Calendar'))
        );*/
        if ($calendar !== null) {
            // TODO: add hidden cid if we want to work on other calendars
            $builder->get('title')->setData($calendar->getTitle());
            $builder->get('subject_max')->setData($calendar->getMaxSubjectLength());
            $builder->get('events_max')->setData($calendar->getMaxDisplayEvents());
            $builder->get('anon_permission')->setData($calendar->getAnonPermission());
            $builder->get('timezone')->setData($calendar->getTimezone());
            $builder->get('language')->setData($calendar->getLocale());
        }

        return $builder->getForm();
    }
}
