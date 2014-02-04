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

function user_delete()
{
	global $vars, $phpcid, $phpcdb, $phpc_script;

	$html = tag('div', attributes('class="phpc-container"'));

	if(!is_admin()) {
		$html->add(tag('p', __('You must be an admin to delete users.')));
		return $html;
	}

	if(empty($vars["uid"])) {
		$html->add(tag('p', __('No user selected.')));
		return $html;
	}

	if (is_array($vars["uid"])) {
		$ids = $vars["uid"];
	} else {
		$ids = array($vars["uid"]);
	}

	if (empty($vars["confirm"])) {
		$list = tag('ul');
		foreach ($ids as $id) {
			$user = $phpcdb->get_user($id);
			$list->add(tag('li', "$id: ".$user->get_username()));
		}
		$html->add(tag('p', __('Confirm you want to delete:')));
		$html->add($list);
		$html->add(" [ ", create_action_link(__('Confirm'),
					"user_delete", array("uid" => $ids,
						"confirm" => "1")), " ] ");
		$html->add(" [ ", create_action_link(__('Deny'),
					"display_month"), " ] ");
		return $html;
	}

	foreach($ids as $id) {
		if($phpcdb->delete_user($id)) {
			$html->add(tag('p', __("Removed user: $id")));
		} else {        
			$html->add(tag('p', __("Could not remove user: $id")));
		}
	}

        return message_redirect($html, "$phpc_script?action=admin&phpcid=$phpcid");
}

?>
