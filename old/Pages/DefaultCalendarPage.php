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

namespace old\Pages;

use PhpCalendar\Context;
use PhpCalendar\Page;
use PhpCalendar\PermissionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use function PhpCalendar\__;


class DefaultCalendarPage extends Page
{
    /**
     * Display admin page and process forms
     *
     * @param  Context $context
     * @return Response
     * @throws PermissionException
     */
    public function action(Context $context)
    {
        if (!$context->user->isAdmin()) {
            throw new PermissionException();
        }
        if (empty($context->request->get("cid"))) {
            $context->addMessage(__('no-calendar-specified'));
        } else {
            $calendar = $context->db->getCalendar($context->request->get('cid'));

            $context->db->setConfig('default_cid', $calendar->getCid());
            $context->addMessage(__('default-calendar-changed-notification', ['%title%' => $calendar->getCid()]));
        }

        return new RedirectResponse($context->createUrl('admin', [], "calendars"));
    }
}
