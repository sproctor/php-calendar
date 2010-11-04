<?php
/*
 * Copyright 2009 Sean Proctor
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

function logout()
{
	global $vars, $phpc_script;

        session_destroy();
	setcookie("phpc_user", "0");

	$url_string = $phpc_script;
	if(!empty($vars['lasturl'])) {
		$url_string .= '?' . urldecode($vars['lasturl']);
	}

        redirect($url_string);

        return tag('h2', _('Loggin out...'));
}
?>
