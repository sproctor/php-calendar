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

class EventDeletePage
{

    /**
     * Delete calendar specified by 'cid'
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        if (empty($context->request->get("eid"))) {
            throw new InvalidInputException(__('no-event-specified-error'));
        }

        if (is_array($context->request->get("eid"))) {
            $eids = $context->request->get("eid");
        } else {
            $eids = array($context->request->get("eid"));
        }

        $removed_events = array();

        foreach ($eids as $eid) {
            $event = $context->db->getEvent($eid);
            if ($event == null) {
                continue;
            }
            if (!$event->canModify($context->user)) {
                throw new PermissionException();
            }
            $context->db->deleteEvent($eid);
            $removed_events[] = $event->getSubject();
        }

        if (sizeof($removed_events) > 0) {
            $context->addMessage(transchoice(
                'removed-events-notification',
                sizeof($removed_events),
                ['%subject%' => '"'.implode('", "', $removed_events).'"']
            ));
        }
        
        return new RedirectResponse($context->createUrl());
    }
}
