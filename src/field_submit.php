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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function field_submit()
{
	global $vars, $phpcdb, $phpc_script, $phpc_cal;

	$form_page = "$phpc_script?action=field_form";
	if(!empty($vars["cid"]))
		$form_page .= "&cid={$vars["cid"]}";
	if(!empty($vars["fid"]))
		$form_page .= "&fid={$vars["fid"]}";

	if(empty($vars["name"])) {
		return input_error(__("Name not specified."), $form_page);
	}

	$required = !empty($vars['name']) && $vars['required'] == '1';

	if(empty($vars['format']))
		$format = false;
	else
		$format = $vars['format'];

	if(!isset($vars['fid'])) {
		$modify = false;

		if(!isset($vars['cid'])) {
			$cid = null;
			if(!is_admin())
				permission_error(__('You do not have permission to add fields to all calendars.'));
		} else { 
			$cid = $vars['cid'];
			$calendar = $phpcdb->get_calendar($cid);
			if(!$calendar->can_admin())
				permission_error(__('You do not have permission to add fields to this calendar.'));
		}
		$fid = $phpcdb->create_field($cid, $vars["name"], $required, $format);
	} else {
		$modify = true;

		$fid = $vars['fid'];
		$field = $phpcdb->get_field($fid);

		if(!(empty($field['cid']) && is_admin() ||
					$phpcdb->get_calendar($field["cid"])->can_admin()))
			permission_error(__("You do not have permission to modify this field."));
			
		$phpcdb->modify_field($fid, $vars['name'], $required, $format);
	}

	$page = "$phpc_script?action=cadmin&phpcid={$vars['phpcid']}#phpc-fields";

	if($modify)
		return message_redirect(__("Modified field: ") . $fid, $page);

	if($fid > 0)
		return message_redirect(__("Created field: ") . $fid, $page);

	return tag('div', attributes('class="phpc-error"'),
			__('Error submitting field.'));
}
?>
