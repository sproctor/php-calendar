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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UpdatePage extends Page
{
    /**
     * Update the database
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        if ($context->db->getConfig('version') == PHPC_DB_VERSION) {
            throw new PermissionException("No need to update database.");
        }
        if ($context->getAction() != 'update') {
            return new Response($context->twig->render("update_page.html.twig", array('script' => $context->script)));
        }
        
        $context->db->update();
        if (!$context->db->update()) {
            $context->addMessage(__('Already up to date.'));
        }
        return new RedirectResponse(action_url($context));
    }
}
