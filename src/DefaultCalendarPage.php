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

class DefaultCalendarPage extends Page
{
    /**
     * Display admin page and process forms
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        if (!$context->user->isAdmin()) {
            throw new PermissionException(__('You do not have the privilege of changing the default calendar.'));
        }
        if (empty($context->request->get("cid"))) {
            $context->addMessage(__('No calendar selected.'));
        } else {
            $context->db->setConfig('default_cid', $context->request->get('cid'));
            $context->addMessage(__('Default calendar set to: ').$context->request->get('cid'));
        }

        return new RedirectResponse(action_url($context, 'admin', array(), "calendars"));
    }
}
