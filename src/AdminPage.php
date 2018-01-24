<?php
/*
 * Copyright Sean Proctor
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
use Symfony\Component\Form\FormInterface;

class AdminPage extends Page
{
    /**
     * Display admin page and process forms
     *
     * @param  Context $context
     * @return Response
     * @throws \Exception
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
            return $this->processUserForm($context, $user_form->getData());
        }

        return new Response(
            $context->render(
                "admin.html.twig",
                array('calendar_form' => $calendar_form->createView(), 'user_form' => $user_form->createView())
            )
        );
    }

    /**
     * @param Context $context
     * @param Calendar $calendar
     * @return FormInterface
     */
    private function createUserForm(Context $context, Calendar $calendar)
    {
        $builder = $context->getFormFactory()->createNamedBuilder('user_form');

        foreach ($context->db->getUsersPermissions($calendar->getCid()) as $user) {
            $uid = $user['uid'];
            /*$data = [
                'read' => $user['read'],
                'write' => $user['write'],
                'modify' => $user['modify'],
                'admin' => $user['admin']
            ];*/
            $builder->add(
                "user_$uid",
                UserPermissionsType::class,
                ['data' => $user, 'label' => $context->db->getUser($uid)->getUsername()]
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

        $context->addMessage(__(
            'updated-type-notification',
            ['%type%' => __('calendar'), '%name%' => $context->calendar->getTitle()]
        ));

        return new RedirectResponse($context->createUrl('admin', array(), 'calendar'));
    }

    /**
     * @param Context $context
     * @param array $data
     * @return Response
     */
    private function processUserForm(Context $context, $data)
    {
        foreach ($data as $user) {
            $context->db->updatePermissions($context->calendar->getCid(), $user['uid'], $user);
        }

        $context->addMessage(__('users-updated-notification'));

        return new RedirectResponse($context->createUrl('admin', array(), 'calendar-users'));
    }
}
