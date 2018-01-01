<?php
/*
 * Copyright 2012 Sean Proctor
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
            throw new InvalidInputException(__('No event selected.'));
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
                throw new PermissionException(__('You do not have permission to remove event').": $eid");
            }
            if (!$context->db->deleteEvent($eid)) {
                throw new \Exception(__('Could not delete event').": $eid");
            }
            $removed_events[] = $eid;
        }

        if (sizeof($removed_events) > 0) {
            if (sizeof($removed_events) == 1) {
                $text = __("Removed event");
            } else {
                $text = __("Removed events");
            }
            $text .= ': ' . implode(', ', $removed_events);
            $context->addMessage($text);
        }
        
        return new RedirectResponse(action_url(context));
    }
}
