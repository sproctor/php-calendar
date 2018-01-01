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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
        $calendar_form = (new CalendarForm())->getForm($context, $context->calendar->getCid());
        $calendar_form->handleRequest($context->request);
        if ($calendar_form->isSubmitted() && $calendar_form->isValid()) {
            return $this->processCalendarForm($context, $calendar_form->getData());
        }

        return new Response(
            $context->twig->render(
                "admin.html.twig",
                array('calendar_form' => $calendar_form->createView())
            )
        );
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
