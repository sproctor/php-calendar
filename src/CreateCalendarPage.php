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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CreateCalendarPage extends Page
{
    /**
     * Display calendar form or submit calendar
     *
     * @param  Context $context
     * @return Response
     * @throws PermissionException
     * @throws \Exception
     */
    public function action(Context $context)
    {
        if (!$context->user->isAdmin()) {
                throw new PermissionException();
        }

        $form = (new CalendarForm)->getForm($context);

        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processForm($context, $form->getData());
        }
        
        // else
        return new Response($context->render("calendar_create.html.twig", array('form' => $form->createView())));
    }

    /**
     * @param Context $context
     * @param array $data
     * @return Response
     */
    private function processForm(Context $context, $data)
    {
    
        $cid = $context->db->createCalendar();

        foreach ($data as $key => $value) {
            $context->db->setCalendarConfig($cid, $key, $value);
        }

        $context->addMessage(__('calendar-created-notification'));

        return new RedirectResponse($context->createUrl('admin'));
    }
}
