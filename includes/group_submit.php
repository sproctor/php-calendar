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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function group_submit()
{
	global $vars, $phpcdb, $phpc_script, $phpc_cal;

	if(!isset($vars['gid'])) {
		$modify = false;

		if(!isset($vars['cid'])) {
			$cid = null;
			if(!is_admin())
				permission_error(__('You do not have permission to add a global group.'));
		} else { 
			$cid = $vars['cid'];
			$calendar = $phpcdb->get_calendar($cid);
			if(!$calendar->can_admin())
				permission_error(__('You do not have permission to add a group to this calendar.'));
		}
		$gid = $phpcdb->create_group($cid, $vars["name"]);
	} else {
		$modify = true;

		$gid = $vars['gid'];
		$group = $phpcdb->get_group($gid);

		if(!(empty($group['cid']) && is_admin() ||
					$phpcdb->get_calendar($group["cid"])
					->can_admin()))
			soft_error(__("You do not have permission to modify this group."));
			
		$phpcdb->modify_group($gid, $vars['name']);
	}

	$page = "$phpc_script?action=cadmin&phpcid=".$vars['cid'];

	if($modify)
		return message_redirect(__("Modified group: ") . $gid,
				$page);

	if($gid > 0)
		return message_redirect(__("Created group: ") . $gid,
				$page);

	return tag('div', attributes('class="phpc-error"'),
			__('Error submitting group.'));
}
?>
