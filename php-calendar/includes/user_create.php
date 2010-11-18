<?php 
/*
 * Copyright 2010 Sean Proctor
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
	global $phpcid, $vars, $phpcdb;

        if(!is_admin()) {
                return tag('div', _('Permission denied'));
        }

	verify_token();

        if(empty($vars['user_name'])) {
                return tag('div', _('You must specify a user name'));
        }

        if(empty($vars['password1'])) {
                return tag('div', _('You must specify a password'));
        }

        if(empty($vars['password2'])
                || $vars['password1'] != $vars['password2']) {
                return tag('div', _('Your passwords did not match'));
        }

	if(empty($vars['make_admin'])) {
		$make_admin = '0';
	} else {
		$make_admin = '1';
	}

        $passwd = md5($vars['password1']);

	$user = $phpcdb->get_user($vars["user_name"]);

        if($user) {
		soft_error(_('User already exists.'));
        }
	
	$phpcdb->create_user($vars["user_name"], $passwd, $make_admin);

        return tag('div', _('Added user.'));
}

?>
