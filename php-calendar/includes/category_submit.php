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

	if(!isset($vars['catid'])) {
		$modify = false;

		if(!isset($vars['cid'])) {
			$cid = null;
			if(!is_admin())
				soft_error(_('You do not have permission to add categories to all calendars.'));
		} else { 
			$cid = $vars['cid'];
			if(!can_admin_calendar($cid))
				soft_error(_('You do not have permission to add categories to this calendar.'));
		}

		$catid = $phpcdb->create_category($cid, $vars["name"],
				$vars["text-color"], $vars['bg-color']);
	} else {
		$modify = true;
		$catid = $vars['catid'];
		$phpcdb->modify_category($catid, $vars['name'],
				$vars['text-color'], $vars['bg-color']);
	}

	if($modify)
		return tag('div', _("Modified category: ") . $catid);

	if($catid > 0)
		return tag('div', _("Created category: ") . $catid);

	return tag('div', attributes('class="phpc-error"'),
			_('Error submitting category.'));
}

?>
