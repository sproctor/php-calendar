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

/*
   This file has the functions for the main displays of the calendar
*/

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;

class EventPage extends Page
{
    /**
     * Display for a single event
     *
     * @param  Context $context
     * @return Response
     * @throws PermissionException
     * @throws \Exception
     */
    public function action(Context $context)
    {
        if ($context->request->get('eid') === null) {
            throw new InvalidInputException(__("no-event-specified-error"));
        }

        $event = $context->db->getEvent($context->request->get('eid'));
        if ($event == null) {
            throw new InvalidInputException(__('nonexistent-value-error', ['%name%' => __('event')]));
        }

        if (!$event->canRead($context->user)) {
            throw new PermissionException();
        }

        return new Response($context->render('view.html.twig', ['event' => $event]));
    }
}
