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

function group_delete()
{
	global $vars, $phpcdb, $phpcid, $phpc_script;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["gid"])) {
		return message_redirect(__('No group selected.'),
				"$phpc_script?action=cadmin&phpcid=$phpcid");
	}

	if (is_array($vars["gid"])) {
		$ids = $vars["gid"];
	} else {
		$ids = array($vars["gid"]);
	}

	$groups = array();
	foreach ($ids as $id) {
		$groups[] = $phpcdb->get_group($id);
	}

	if (empty($vars["confirm"])) {
		$list = tag('ul');
		foreach ($groups as $group) {
			$list->add(tag('li', "$id: ".$group['name']));
		}
		$html->add(tag('p', __('Confirm you want to delete:')));
		$html->add($list);
		$html->add(" [ ", create_action_link(__('Confirm'),
					"group_delete", array("gid" => $ids,
						"confirm" => "1")), " ] ");
		$html->add(" [ ", create_action_link(__('Deny'),
					"display_month"), " ] ");
		return $html;
	}

	foreach($groups as $group) {
		if((empty($group['cid']) && !is_admin()) ||
					!$phpcdb->get_calendar($group['cid'])
					->can_admin()) {
			$html->add(tag('p', __("You do not have permission to delete group: ") . $group['gid']));
			continue;
		}

		if($phpcdb->delete_group($group['gid'])) {
			$html->add(tag('p', __("Removed group: ")
					. $group['gid']));
		} else {        
			$html->add(tag('p', __("Could not remove group: ")
						. $group['gid']));
		}
	}

        return message_redirect($html, "$phpc_script?action=cadmin&phpcid=$phpcid");
}

?>
