<?php
/*
 * Copyright 2014 Sean Proctor
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

function field_delete()
{
	global $vars, $phpcdb, $phpcid, $phpc_script;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["fid"])) {
		return message_redirect(__('No field selected.'),
				"$phpc_script?action=cadmin&phpcid=$phpcid");
	}

	if (is_array($vars["fid"])) {
		$ids = $vars["fid"];
	} else {
		$ids = array($vars["fid"]);
	}

	$fields = array();
	foreach ($ids as $id) {
		$fields[] = $phpcdb->get_field($id);
	}

	foreach($fields as $field) {
		if((empty($field['cid']) && !is_admin()) ||
					!$phpcdb->get_calendar($field['cid'])
					->can_admin()) {
			$html->add(tag('p', __("You do not have permission to delete field: ") . $field['fid']));
			continue;
		}

		if($phpcdb->delete_field($field['fid'])) {
			$html->add(tag('p', __("Removed field: ") . $field['fid']));
		} else {        
			$html->add(tag('p', __("Could not remove field: ") . $field['fid']));
		}
	}

        return message_redirect($html, "$phpc_script?action=cadmin&phpcid=$phpcid");
}

?>
