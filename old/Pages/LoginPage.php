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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use function PhpCalendar\__;

class LoginPage extends Page
{
    /**
     * @param Context $context
     * @return Response
     * @throws \Exception
     */
    public function action(Context $context)
    {
        //Check password and username
        if (isset($_REQUEST['username'])) {
            $username = $_REQUEST['username'];
            if (empty($_REQUEST['password'])) {
                $context->addMessage(__("no-password-specified-notification"));
            } else {
                $password = $_REQUEST['password'];

                // TODO: split notices for bad password and nonexistent user
                if ($context->loginUser($username, $password)) {
                    $url = $context->request->getScriptName();
                    if (!empty($_REQUEST['lasturl'])) {
                        $url .= '?' . urldecode($_REQUEST['lasturl']);
                    }
                    return new RedirectResponse($url);
                } else {
                    $context->addMessage(__("invalid-login-credentials-notification"));
                }
            }
        }
        return new Response($context->render("login.html.twig"));
    }
}
