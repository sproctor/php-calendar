<?php 
/*
 * Copyright 2016 Sean Proctor
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

class LoginPage extends Page
{

    /**
     * @param Context $context
     * @param string[] $template_variables
     * @return string
     */
    function display(Context $context, $template_variables)
	{
		//Check password and username
		if(isset($_REQUEST['username'])){
			$username = $_REQUEST['username'];
			if(empty($_REQUEST['password'])) {
				$context->addMessage(__("No password specified."));
			} else {
				$password = $_REQUEST['password'];

				if(login_user($context, $username, $password)){
					$url = $context->script;
					if(!empty($_REQUEST['lasturl'])) {
						$url .= '?' . urldecode($_REQUEST['lasturl']);
					}
					redirect($context, $url);
					$template_variables['logged_in'] = true;
				} else {
					$context->addMessage(__("Invalid login credentials."));
				}
			}
		}
		$template_variables['messages'] = $context->getMessages();
        return $context->twig->render("login.html", $template_variables);
	}
}