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

class CalendarDeletePage
{

    /**
     * Delete calendar specified by 'cid'
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        $cid = $context->request->get('cid');

        if ($cid === null) {
            throw new InvalidInputException(__('no-calendar-specified-error'));
        }

        $calendar = $context->db->getCalendar($cid);

        if (empty($calendar)) {
            throw new InvalidInputException(__('invalid-calendar-id-error'));
        }

        if (!$calendar->canAdmin($context->user)) {
            throw new PermissionException();
        }

        $context->db->deleteCalendar($cid);
        $context->addMessage(__("removed-calendar-notification", array('%title%' => $calendar->getTitle())));
        
        return new RedirectResponse(action_url($context, 'admin'));
    }
}
