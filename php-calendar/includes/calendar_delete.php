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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function calendar_delete()
{
	global $vars, $phpcdb, $phpc_script;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["cid"])) {
		$html->add(tag('p', __('No calendar selected.')));
		return $html;
	}

	if (is_array($vars["cid"])) {
		$ids = $vars["cid"];
	} else {
		$ids = array($vars["cid"]);
	}

	if (empty($vars["confirm"])) {
		$list = tag('ul');
		foreach ($ids as $id) {
			$calendar = $phpcdb->get_calendar($id);
			$list->add(tag('li', "$id: ".$calendar->get_title()));
		}
		$html->add(tag('p', __('Confirm you want to delete:')));
		$html->add($list);
		$html->add(" [ ", create_action_link(__('Confirm'),
					"calendar_delete", array("cid" => $ids,
						"confirm" => "1")), " ] ");
		$html->add(" [ ", create_action_link(__('Deny'),
					"display_month"), " ] ");
		return $html;
	}

	foreach($ids as $id) {
		$calendar = $phpcdb->get_calendar($id);
		if(!$calendar->can_admin()) {
			$html->add(tag('p', __("You do not have permission to remove calendar") . ": $id"));
			continue;
		}

		if($phpcdb->delete_calendar($id)) {
			$html->add(tag('p', __("Removed calendar") . ": $id"));
		} else {        
			$html->add(tag('p', __("Could not remove calendar")
						. ": $id"));
		}
	}

        return message_redirect($html, "$phpc_script?action=admin");
}

?>
