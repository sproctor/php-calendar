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

function category_submit()
{
	global $vars, $phpcdb, $phpc_script, $phpc_cal;

	if(empty($vars["text-color"]) || empty($vars["bg-color"])) {
		$page = "$phpc_script?action=category_form";
		if(!empty($vars["cid"]))
			$page .= "&cid={$vars["cid"]}";
		if(!empty($vars["catid"]))
			$page .= "&catid={$vars["catid"]}";

		return message_redirect(__("Color not specified."), $page);
	}

	// The current widget produces hex values without the "#".
	//   We may in the future want to allow different input, so store the
	//   values with the "#"
	$text_color = '#'.$vars["text-color"];
	$bg_color = '#'.$vars["bg-color"];
	if(empty($vars['gid']) || strlen($vars['gid']) == 0)
		$gid = 0;
	else
		$gid = $vars['gid'];

	if(!check_color($text_color) || !check_color($bg_color))
		soft_error(__("Invalid color."));

	if(!isset($vars['catid'])) {
		$modify = false;

		if(!isset($vars['cid'])) {
			$cid = null;
			if(!is_admin())
				permission_error(__('You do not have permission to add categories to all calendars.'));
		} else { 
			$cid = $vars['cid'];
			$calendar = $phpcdb->get_calendar($cid);
			if(!$calendar->can_admin())
				permission_error(__('You do not have permission to add categories to this calendar.'));
		}
		$catid = $phpcdb->create_category($cid, $vars["name"],
				$text_color, $bg_color, $gid);
	} else {
		$modify = true;

		$catid = $vars['catid'];
		$category = $phpcdb->get_category($catid);

		if(!(empty($category['cid']) && is_admin() ||
					$phpcdb->get_calendar($category["cid"])
					->can_admin()))
			soft_error(__("You do not have permission to modify this category."));
			
		$phpcdb->modify_category($catid, $vars['name'],
				$text_color, $bg_color, $gid);
	}

	$page = "$phpc_script?action=cadmin&phpcid=".$vars['phpcid'];

	if($modify)
		return message_redirect(__("Modified category: ") . $catid,
				$page);

	if($catid > 0)
		return message_redirect(__("Created category: ") . $catid,
				$page);

	return tag('div', attributes('class="phpc-error"'),
			__('Error submitting category.'));
}

function check_color($color) {
	return preg_match('/^#[0-9a-fA-F]{6}$/', $color) == 1;
}
?>
