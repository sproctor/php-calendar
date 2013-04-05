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

function user_create()
{
	global $phpcid, $vars, $phpcdb, $phpc_script;

        if(!is_admin()) {
                return tag('div', _('Permission denied'));
        }

	verify_token();

	$form_page = "$phpc_script?action=admin";

        if(empty($vars['user_name'])) {
                return message_redirect(_('You must specify a user name'),
				$form_page);
        }

        if(empty($vars['password1'])) {
                return message_redirect(_('You must specify a password'),
				$form_page);
        }

        if(empty($vars['password2'])
                || $vars['password1'] != $vars['password2']) {
                return message_redirect(_('Your passwords did not match'),
				$form_page);
        }

	$make_admin = empty($vars['make_admin']) ? 0 : 1;

        $passwd = md5($vars['password1']);
		
	if($phpcdb->get_user_by_name($vars["user_name"]))
		return message_redirect(_('User already exists.'), $form_page);
	
	$phpcdb->create_user($vars["user_name"], $passwd, $make_admin);

        return message_redirect(_('Added user. Now set its permission inside the Calendar Admin'), $form_page);
}

?>
