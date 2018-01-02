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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class AdminPage extends Page
{
    /**
     * Display admin page and process forms
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        $calendar_form = (new CalendarForm())->getForm($context, $context->calendar);
        $calendar_form->handleRequest($context->request);
        if ($calendar_form->isSubmitted() && $calendar_form->isValid()) {
            return $this->processCalendarForm($context, $calendar_form->getData());
        }

        $user_form = $this->createUserForm($context, $context->calendar);
        $user_form->handleRequest($context->request);
        if ($user_form->isSubmitted() && $user_form->isValid()) {
            //return $this->processCalendarForm($context, $calendar_form->getData());
        }

        return new Response(
            $context->twig->render(
                "admin.html.twig",
                array('calendar_form' => $calendar_form->createView(), 'user_form' => $user_form->createView())
            )
        );
    }

    /**
     * @param Context $context
     * @param Calendar $calendar
     * @return Form
     */
    private function createUserForm(Context $context, Calendar $calendar)
    {
        $builder = $context->getFormFactory()->createBuilder();

        foreach ($context->db->getUsersWithPermissions($calendar->getCid()) as $user) {
            $uid = $user['uid'];
            $builder->add(
                $builder->create("uid$uid", FormType::class, array('inherit_data' => true, 'label' => $user['username']))
                ->add('read', CheckboxType::class, array('label' => __('read'), 'data' => $user['read'], 'label_attr' => array('class' => 'checkbox-inline')))
                ->add('write', CheckboxType::class, array('label' => __('write'), 'data' => $user['write'], 'label_attr' => array('class' => 'checkbox-inline')))
                ->add('modify', CheckboxType::class, array('label' => __('modify'), 'data' => $user['modify']))
                ->add('admin', CheckboxType::class, array('label' => __('admin'), 'data' => $user['admin']))
            );
        }

        return $builder->getForm();
    }

    /**
     * @param Context $context
     * @param array $data
     * @return Response
     */
    private function processCalendarForm(Context $context, $data)
    {
        foreach ($data as $key => $value) {
            $context->db->setCalendarConfig($context->calendar->getCid(), $key, $value);
        }

        $context->addMessage(__('Calendar updated.'));

        return new RedirectResponse(action_url($context, 'admin', array(), 'calendar'));
    }
}
