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
	global $phpc_script, $phpc_token, $phpcdb, $vars, $phpc_cal;

	$groups = array();
	foreach($phpc_cal->get_groups() as $group) {
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
					create_hidden('uid', $vars['uid']),
					create_hidden('action', 'user_groups'),
					create_hidden('submit_form',
						'submit_form'),
					create_submit(__('Submit')))));
}

function process_form()
{
	global $phpcid, $vars, $phpcdb, $phpc_script, $phpc_cal;

	verify_token();

	$user = $phpcdb->get_user($vars["uid"]);
	// Remove existing groups for this calendar
	foreach($user->get_groups() as $group) {
		if($group["cid"] == $phpcid)
			$phpcdb->user_remove_group($vars["uid"], $group["gid"]);
	}
	
	$valid_groups = array();
	foreach($phpc_cal->get_groups() as $group) {
		$valid_groups[] = $group["gid"];
	}
	if(!empty($vars["groups"])) {
		foreach($vars["groups"] as $gid) {
			if(!in_array($gid, $valid_groups))
				soft_error("Invalid gid");

			$phpcdb->user_add_group($vars["uid"], $gid);
		}
	}

        return message(__('Groups updated.'));
}

?>
