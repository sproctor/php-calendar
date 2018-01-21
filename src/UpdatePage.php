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

class UpdatePage extends Page
{
    /**
     * Update the database
     *
     * @param  Context $context
     * @return Response
     * @throws PermissionException
     * @throws \Exception
     */
    public function action(Context $context)
    {
        if ($context->db->getConfig('version') == PHPC_DB_VERSION && !$context->user->isAdmin()) {
            throw new PermissionException();
        }
        if ($context->getAction() != 'update') {
            return new Response($context->render("update_page.html.twig"));
        }
        
        $context->db->update();
        $updates = $context->db->update();
        if (sizeof($updates) > 0) {
            $context->addMessage(__('db-updated-notification'));
        } else {
            $context->addMessage(__('db-already-updated-notification'));
        }
        return new RedirectResponse($context->createUrl());
    }
}
