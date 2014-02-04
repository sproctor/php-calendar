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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/form.php");

function category_form() {
	global $phpc_script, $vars, $phpcdb, $phpcid;

        $form = new Form($phpc_script, __('Category Form'));
        $form->add_part(new FormFreeQuestion('name', __('Name'),
				false, 32, true));

	if(isset($vars['cid'])) {
		$form->add_hidden('cid', $vars['cid']);
		$cid = $vars['cid'];
	} else {
		$cid = $phpcid;
	}

	$form->add_hidden('action', 'category_submit');
	$form->add_hidden('phpcid', $phpcid);
	$form->add_part(new FormColorPicker('text-color',__('Text Color')));
	$form->add_part(new FormColorPicker('bg-color',__('Background Color')));
	$group_question = new FormDropDownQuestion('gid',
			__('Visible to groups'));
	$group_question->add_option('', __('None'));
	foreach($phpcdb->get_groups($cid) as $group) {
		$group_question->add_option($group['gid'], $group['name']);
	}
	$form->add_part($group_question);
	$form->add_part(new FormSubmitButton(__("Submit Category")));

	if(isset($vars['catid'])) {
		$form->add_hidden('catid', $vars['catid']);
		$category = $phpcdb->get_category($vars['catid']);
		$defaults = array(
				'name' => htmlspecialchars($category['name']),
				'text-color' => htmlspecialchars(str_replace('#', '', $category['text_color'])),
				'bg-color' => htmlspecialchars(str_replace('#', '', $category['bg_color'])),
				'gid' => htmlspecialchars($category['gid']),
				);
	} else {
		$defaults = array(
				'text-color' => '000000',
				'bg-color' => 'FFFFFF',
				);
	}
        return $form->get_form($defaults);
}

?>
