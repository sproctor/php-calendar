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

class LoginPage extends Page
{
    /**
     * @param Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        //Check password and username
        if (isset($_REQUEST['username'])) {
            $username = $_REQUEST['username'];
            if (empty($_REQUEST['password'])) {
                $context->addMessage(__("No password specified."));
            } else {
                $password = $_REQUEST['password'];

                if ($context->loginUser($username, $password)) {
                    $url = $context->request->getScriptName();
                    if (!empty($_REQUEST['lasturl'])) {
                        $url .= '?' . urldecode($_REQUEST['lasturl']);
                    }
                    return new RedirectResponse($url);
                } else {
                    $context->addMessage(__("Invalid login credentials."));
                }
            }
        }
        return new Response($context->twig->render("login.html.twig"));
    }
}
