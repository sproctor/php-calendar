<?php 
/*
 * Copyright 2013 Sean Proctor
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

function user_groups() {
	global $vars, $phpc_cal;

        if(!$phpc_cal->can_admin()) {
                return tag('div', __('Permission denied'));
        }

	if(!empty($vars['submit_form']))
		process_form();

	return display_form();

}

function display_form() {
	global $phpc_script, $phpc_token, $phpcdb, $vars;

	$groups = array();
	foreach($phpcdb->get_groups() as $group) {
		$groups[$group['gid']] = $group['name'];
	}

	$size = sizeof($groups);
	if($size > 6)
		$size = 6;

	$user = $phpcdb->get_user($vars["uid"]);

	$user_groups = array();
	foreach($user->get_groups() as $group) {
		$user_groups[] = $group['gid'];
	}

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('div', attributes("class=\"phpc-container\""),
				tag('h2', __('Edit User Groups')),
				tag('div', create_select('groups[]', $groups,
					$user_groups,
					attrs('multiple', "size=\"$size\""))),
				tag('div',
					create_hidden('phpc_token',
						$phpc_token),
					create_hidden('action', 'user_create'),
					create_hidden('submit_form',
						'submit_form'),
					create_submit(__('Submit')))));
}

function process_form()
{
	global $phpcid, $vars, $phpcdb, $phpc_script;

	verify_token();

        if(empty($vars['user_name'])) {
                return message(__('You must specify a user name'));
        }

        if(empty($vars['password1'])) {
                return message(__('You must specify a password'));
        }

        if(empty($vars['password2'])
                || $vars['password1'] != $vars['password2']) {
                return message(__('Your passwords did not match'));
        }

	$make_admin = empty($vars['make_admin']) ? 0 : 1;

        $passwd = md5($vars['password1']);

	if($phpcdb->get_user_by_name($vars["user_name"]))
		return message(__('User already exists.'));
	
	$uid = $phpcdb->create_user($vars["user_name"], $passwd, $make_admin);

	if(!empty($vars['groups'])) {
		foreach($vars['groups'] as $gid) {
			$phpcdb->user_add_group($uid, $gid);
		}
	}

        return message(__('Added user.'));
}

?>
