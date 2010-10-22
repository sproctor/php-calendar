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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function category_submit()
{
	global $vars, $phpcdb;

	if(empty($vars["text-color"]) || empty($vars["bg-color"]))
		soft_error(_("Color not specified."));

	$text_color = $vars["text-color"];
	$bg_color = $vars["bg-color"];

	if(!check_color($text_color) || !check_color($bg_color))
		soft_error(_("Invalid color."));

	if(!isset($vars['catid'])) {
		$modify = false;

		if(!isset($vars['cid'])) {
			$cid = null;
			if(!is_admin())
				permission_error(_('You do not have permission to add categories to all calendars.'));
		} else { 
			$cid = $vars['cid'];
			if(!can_admin_calendar($cid))
				permission_error(_('You do not have permission to add categories to this calendar.'));
		}

		$catid = $phpcdb->create_category($cid, $vars["name"],
				$text_color, $bg_color);
	} else {
		$modify = true;

		$catid = $vars['catid'];
		$category = $phpcdb->get_category($catid);

		if(!(empty($category['cid']) && is_admin()
					|| can_admin_calendar($catid)))
			soft_error(_("You do not have permission to modify this category."));

		$phpcdb->modify_category($catid, $vars['name'],
				$text_color, $bg_color);
	}

	if($modify)
		return tag('div', _("Modified category: ") . $catid);

	if($catid > 0)
		return tag('div', _("Created category: ") . $catid);

	return tag('div', attributes('class="phpc-error"'),
			_('Error submitting category.'));
}

function check_color($color) {
	return preg_match('/^#[0-9a-fA-F]{6}$/', $color) == 1;
}
?>
