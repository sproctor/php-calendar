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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/form.php");

function group_form() {
	global $phpc_script, $vars, $phpcdb, $phpcid;

        $form = new Form($phpc_script, __('Group Form'));
        $form->add_part(new FormFreeQuestion('name', __('Name'),
				false, 32, true));

	$form->add_hidden('cid', $phpcid);

	$form->add_hidden('action', 'group_submit');
	$form->add_part(new FormSubmitButton(__("Submit Group")));

	if(isset($vars['gid'])) {
		$form->add_hidden('gid', $vars['gid']);
		$group = $phpcdb->get_group($vars['gid']);
		$defaults = array('name' => htmlspecialchars($group['name']));
	} else {
		$defaults = array();
	}
        return $form->get_form($defaults);
}

?>
