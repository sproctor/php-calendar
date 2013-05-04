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

function admin() {
	global $phpc_version;

        if(!is_admin()) {
                permission_error(__('You must be logged in as an admin.'));
        }

	$menu = tag('div', attrs('class="phpc-bar ui-widget-content"'),
			create_action_link(__('Import from PHP-Calendar 1.1'),
				'import_form'),
			create_action_link(__('Generate translations'),
				'translate'));

	$version = tag('div', attrs('class="phpc-bar ui-widget-content"'),
			__('Version') . ": $phpc_version");

	return tag('div', $menu, new_user_form(), calendar_list(), user_list(),
			$version);
}

function new_user_form()
{
	global $phpc_script;

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', __('Create User')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'user_create'),
							create_submit(__('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', __('User Name')),
						tag('td', create_text('user_name'))),
					tag('tr',
						tag('th', __('Password')),
						tag('td', create_password('password1'))),
					tag('tr',
						tag('th', __('Confirm Password')),
						tag('td', create_password('password2'))),
					tag('tr',
						tag('th', __('Make Admin')),
						tag('td', create_checkbox('make_admin', '1', false, __('Admin')))),
					tag('tr',
						tag('th', __('Group')),
						tag('td', create_text('group')))
				   )));
}

function calendar_list()
{
	global $phpc_script, $phpcdb;

        $tbody = tag('tbody');

		$tbody->add(tag('tr', tag('th', __("Calendar")),
					tag('th', __("Action"))));
        foreach($phpcdb->get_calendars() as $calendar) {
                $title = $calendar->get_title();
                $cid = $calendar->get_cid();

                $tbody->add(tag('tr',
                                tag('th', $title),
                                tag('td',create_action_link(__("Edit"),
						"cadmin", array("phpcid" => $cid)),
					" ", create_action_link(__("Delete"),
						"calendar_delete",
						array("cid" => $cid)))));
        }

	$create_link = create_action_link(__('Create Calendar'),
			'calendar_form');
        return tag('table', attributes("class=\"phpc-container\""),
			tag('caption', __('Calendar List')), $tbody,
			tag('tfoot',
				tag('tr',
					tag('td', attributes('colspan="2"'),
						$create_link))));

}

function user_list()
{
	global $phpc_script, $phpcdb;

        $tbody = tag('tbody');

		$tbody->add(tag('tr', tag('th', __("Username")),
					tag('th', __("Group")),
					tag('th', __("Action"))));
        foreach($phpcdb->get_users() as $user) {
		$tbody->add(tag('tr', tag('th', $user->username),
					tag('td', $user->groups),
					tag('td', create_action_link("Delete",
							"user_delete",
							array("uid" => $user->uid)))));
	}

        return tag('table', attributes("class=\"phpc-container\""),
			tag('caption', __('User List')), $tbody);

}

?>
