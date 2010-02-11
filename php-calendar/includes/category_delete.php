<?php
/*
 * Copyright 2009 Sean Proctor
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

function category_delete()
{
	global $vars, $phpcdb;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["catid"])) {
		$html->add(tag('p', _('No category selected.')));
		return $html;
	}

	if (is_array($vars["catid"])) {
		$ids = $vars["catid"];
	} else {
		$ids = array($vars["catid"]);
	}

	$categories = array();
	foreach ($ids as $id) {
		$categories[] = $phpcdb->get_category($id);
	}

	if (empty($vars["confirm"])) {
		$list = tag('ul');
		foreach ($categories as $category) {
			$list->add(tag('li', "$id: ".$category['name']));
		}
		$html->add(tag('p', _('Confirm you want to delete:')));
		$html->add($list);
		$html->add(" [ ", create_action_link(_('Confirm'),
					"category_delete", array("catid" => $ids,
						"confirm" => "1")), " ] ");
		$html->add(" [ ", create_action_link(_('Deny'),
					"display_month"), " ] ");
		return $html;
	}

	foreach($categories as $category) {
		if((empty($category['cid']) && !is_admin()) ||
					!can_admin_calendar($category['cid'])) {
			$html->add(tag('p', _("You do not have permission to delete category: ") . $category['catid']));
			continue;
		}

		if($phpcdb->delete_category($category['catid'])) {
			$html->add(tag('p', _("Removed category: ")
					. $category['catid']));
		} else {        
			$html->add(tag('p', _("Could not remove category: ")
						. $category['catid']));
		}
	}

        return $html;
}

?>
