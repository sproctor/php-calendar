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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/form.php");

function admin() {
	global $phpc_version;

        if(!is_admin()) {
                permission_error(__('You must be logged in as an admin.'));
        }

	$menu = tag('ul',
			tag('li', tag('a', attrs('href="#phpc-admin-calendars"'),
					__('Calendars'))),
			tag('li', tag('a', attrs('href="#phpc-admin-users"'),
					__('Users'))),
			tag('li', tag('a', attrs('href="#phpc-admin-import"'),
					__('Import'))),
			tag('li', tag('a', attrs('href="#phpc-admin-translate"'),
					__('Translate'))));

	$version = tag('div', attrs('class="phpc-bar ui-widget-content"'),
			__('Version') . ": $phpc_version");

	return tag('', tag('div', attrs('class="phpc-tabs"'), $menu,
				calendar_list(), user_list(), import(),
				translation_link()), $version);
}

function calendar_list()
{
	global $phpc_script, $phpcdb;

        $tbody = tag('tbody');
        foreach($phpcdb->get_calendars() as $calendar) {
                $title = $calendar->get_title();
                $cid = $calendar->get_cid();

                $tbody->add(tag('tr',
                                tag('td', $title),
                                tag('td', create_action_link(__("Admin"),
						"cadmin",
						array("phpcid" => $cid)),
					" ", create_action_link(__("Delete"),
						"calendar_delete",
						array("cid" => $cid),
						attrs('class="phpc-button"')))));
        }

        return tag('div', attrs('id="phpc-admin-calendars"'),
			tag('div', attrs('class="phpc-sub-title"'),
				__('Calendar List')),
			tag('table', attrs('class="phpc-container"'),
				tag('thead',
					tag('tr', attrs('class="ui-widget-header"'),
						tag('th', __("Calendar")),
						tag('th', __("Action")))),
				$tbody),
			create_action_link(__('Create Calendar'),
				'calendar_form', false,
				attrs('class="phpc-button"')));

}

function user_list()
{
	global $phpc_script, $phpcdb;

	$tbody = tag('tbody');
        foreach($phpcdb->get_users() as $user) {
		$group_list = array();
		foreach($user->get_groups() as $group) {
			$group_list[] = $group['name'];
		}
		$groups = implode(', ', $group_list);
		if (!$user->is_disabled()) {
			$disable_link =  create_action_link(__("Disable"),
					"user_disable",
					array("uid" => $user->uid));
		} else {
			$disable_link =  create_action_link(__("Enable"),
					"user_enable",
					array("uid" => $user->uid));
		}
		$tbody->add(tag('tr', tag('td', $user->username),
					tag('td', $groups),
					tag('td', create_action_link(__("Edit Groups"), "user_groups", array("uid" => $user->uid))),
					tag('td', $disable_link)));
	}

        return tag('div', attrs('id="phpc-admin-users"'),
			tag('div', attrs('class="phpc-sub-title"'),
				__('User List')),
			tag('table',
				attrs('class="phpc-container ui-widget ui-widget-content"'),
				tag('thead',
					tag('tr', attrs('class="ui-widget-header"'),
						tag('th', __("Username")),
						tag('th', __("Groups")),
						tag('th', __("Edit Groups")),
						tag('th', __("Action")))),
				$tbody),
			create_action_link(__('Create User'),
				'user_create', false,
				attrs('class="phpc-button"',
					'id="phpc-create-user"')));
}

function import() {
	global $phpc_script, $vars;

	$form = new Form($phpc_script, __('Import Form'));
	$form->add_part(new FormFreeQuestion('host', __('MySQL Host Name')));
	$form->add_part(new FormFreeQuestion('dbname', __('MySQL Database Name')));
	$form->add_part(new FormFreeQuestion('port', __('MySQL Port Number'), __('Leave blank for default')));
	$form->add_part(new FormFreeQuestion('username', __('MySQL User Name')));
	$pwq = new FormFreeQuestion('passwd', __('MySQL User Password'));
	$pwq->type = 'password';
	$form->add_part($pwq);
	$form->add_part(new FormFreeQuestion('prefix', __('PHP-Calendar Table Prefix')));

	$form->add_hidden('action', 'import');
	$form->add_hidden('submit_form', 'submit_form');

	$form->add_part(new FormSubmitButton(__("Import Calendar")));

	$defaults = array(
			'host' => 'localhost',
			'dbname' => 'calendar',
			'prefix' => 'phpc_',
			);

	return tag('div', attrs('id="phpc-admin-import"'),
			$form->get_form($defaults));
}

function translation_link() {
	global $phpc_script;

	return tag('div', attrs('id="phpc-admin-translate"'),
			tag('p', __('This script needs read access to your calendar directory in order to write the translation files. Alternatively, you could run translate.php from the command line or use msgfmt or any other gettext tool that can generate .mo files from .po files.')),
			tag('a', attrs('class="phpc-button"',
					"href=\"$phpc_script?action=translate\""),
				__('Generate Translations')));
}
?>
