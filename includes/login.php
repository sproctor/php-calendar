<?php 
/*
 * Copyright 2012 Sean Proctor
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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

function login()
{
	global $vars, $phpc_script;

	$html = tag('div');

	//Check password and username
	if(isset($vars['username'])){
		$user = $vars['username'];
		$password = $vars['password'];

		if(login_user($user, $password)){
			$url = $phpc_script;
                        if(!empty($vars['lasturl'])) {
				$url .= '?' . urldecode($vars['lasturl']);
			}
                        redirect($url);
			return tag('h2', __('Logged in.'));
		}

		$html->add(tag('h2', __('Sorry, Invalid Login')));

	}

	$html->add(login_form());
	return $html;
}


function login_form()
{
        global $vars, $phpc_script;

        $submit_data = tag('td', attributes('colspan="2"'),
                                create_hidden('action', 'login'),
                                create_submit(__('Log in')));

        if(!empty($vars['lasturl'])) {
		$lasturl = $vars['lasturl'];
                $submit_data->prepend(create_hidden('lasturl',
                                        $lasturl));
	}

	return tag('form', attributes("action=\"$phpc_script\"",
				'method="post"'),
		tag('table',
			tag('caption', __('Log in')),
                        tag('thead',
                                tag('tr',
                                        tag('th', attributes('colspan="2"'),
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
