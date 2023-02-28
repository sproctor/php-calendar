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

namespace old\Pages;

use Exception;
use PhpCalendar\Context;
use PhpCalendar\Entity\Calendar;
use PhpCalendar\Pages\Page;
use PhpCalendar\PermissionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use function PhpCalendar\Pages\__;

class InstallPage extends Page
{
    /**
     * Update the database
     *
     * @param  Context $context
     * @throws PermissionException
     * @throws Exception
     */
    public function action(Context $context): Response
    {
        if (empty($context->findAllCalendars())) {
            // TODO: make calendar
            $context->persist(new Calendar);
            $context->addMessage(__('calendar-created'));
        }

        return new Response($context->render("install_page.html.twig", ['context' => $context]));
        
        return new RedirectResponse($context->createUrl());
    }
}
