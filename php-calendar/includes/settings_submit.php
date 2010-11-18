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

function settings_submit()
{
	global $phpcid, $vars, $phpcdb;

        if(!is_user()) {
                return tag('div', _('You must be logged in.'));
        }

	verify_token();

	$uid = $_SESSION['phpc_uid'];
	$user = $phpcdb->get_user($uid);

	if(!$user)
		soft_error(_('Invalid user.'));

        if(empty($vars['timezone']) || empty($vars['language'])) {
                return tag('div', _('Form error.'));
	}

	$phpcdb->set_timezone($uid, $vars['timezone']);
	$phpcdb->set_language($uid, $vars['language']);

        return tag('div', _('Settings updated.'));
}

?>
