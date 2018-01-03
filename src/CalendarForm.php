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
        $builder->add('title', TextType::class, array('label' => __('Calendar Title')))
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
        ->add('locale', ChoiceType::class, array('label' => __('default-language-label'), 'choices' => get_languages()));
        /*->add(
            'submit',
            SubmitType::class,
            array('label' => $cid === null ? __('Create Calendar') : __('Update Calendar'))
        );*/
        if ($calendar !== null) {
            // TODO: add hidden cid if we want to work on other calendars
            $builder->get('title')->setData($calendar->getTitle());
            $builder->get('subject_max')->setData($calendar->getSubjectMax());
            $builder->get('events_max')->setData($calendar->getMaxDisplayEvents());
            $builder->get('anon_permission')->setData($calendar->getAnonPermission());
            $builder->get('timezone')->setData($calendar->getTimezone());
            $builder->get('locale')->setData($calendar->getLocale());
        }

        return $builder->getForm();
    }
}
