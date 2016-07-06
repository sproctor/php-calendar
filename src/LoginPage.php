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

	function display(Context $context)
	{
		$html = tag('div');

		//Check password and username
		if(isset($_REQUEST['username'])){
			$user = $_REQUEST['username'];
			if(!isset($_REQUEST['password'])) {
				$context->addMessage(__("No password specified."));
			} else {
				$password = $_REQUEST['password'];

				if(login_user($context, $user, $password)){
					$url = $context->script;
					if(!empty($_REQUEST['lasturl'])) {
						$url .= '?' . urldecode($_REQUEST['lasturl']);
					}
					//redirect($context, $url);
					return tag('h2', __('Logged in.'));
				}

				$html->add(tag('h2', __('Sorry, Invalid Login')));
			}
		}

		$html->add(login_form($context));
		return $html;
	}
}

function login_form(Context $context)
{
	$submit_data = tag('td', new AttributeList('colspan="2"'),
			create_hidden('action', 'login'),
			create_submit(__('Log in')));

	if(!empty($_REQUEST['lasturl'])) {
		$submit_data->prepend(create_hidden('lasturl', escape_entities(urlencode($_REQUEST['lasturl']))));
	}

	return tag('form', new AttributeList('action="' . $context->script . '"', 'method="post"'),
			tag('table',
				tag('caption', __('Log in')),
				tag('thead',
					tag('tr',
						tag('th', new AttributeList('colspan="2"'),
							__('You must have cookies enabled to login.')))),
				tag('tfoot',
					tag('tr', $submit_data)),
				tag('tbody',
					tag('tr',
						tag('th', __('Username')),
						tag('td', create_text('username'))),
					tag('tr',
						tag('th', __('Password')),
						tag('td', create_password('password'))))));
}

?>
