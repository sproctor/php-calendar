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

function admin()
{
        if(!is_admin()) {
                permission_error(_('You must be logged in as an admin.'));
        }

	return tag('div', new_user_form(), create_calendar_form(),
			calendar_list(), user_list());
}

function new_user_form()
{
	global $phpc_script;

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', _('Create User')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('action', 'user_create'),
							create_submit(_('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', _('User Name')),
						tag('td', create_text('user_name'))),
					tag('tr',
						tag('th', _('Password')),
						tag('td', create_password('password1'))),
					tag('tr',
						tag('th', _('Confirm Password')),
						tag('td', create_password('password2'))),
					tag('tr',
						tag('th', _('Make Admin')),
						tag('td', create_checkbox('make_admin', '1'))),
					tag('tr',
						tag('th', _('Group')),
						tag('td', create_text('group')))
				   )));
}

function create_calendar_form()
{
	global $phpc_script;

        $tbody = tag('tbody');

        foreach(get_config_options() as $element) {
                $text = $element[1];
		$input = create_config_input($element);

                $tbody->add(tag('tr',
                                tag('th', $text),
                                tag('td', $input)));
        }

        return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', _('Create Calendar')),
				tag('tfoot',
                                        tag('tr',
                                                tag('td', attributes('colspan="2"'),
							create_hidden('action', 'create_calendar'),
							create_submit(_('Submit'))))),
				$tbody));

}

function calendar_list()
{
	global $phpc_script, $phpcdb;

        $tbody = tag('tbody');

		$tbody->add(tag('tr', tag('th', _("Calendar")),
					tag('th', _("Action"))));
        foreach($phpcdb->get_calendars() as $calendar) {
                $title = $calendar->get_title();
                $cid = $calendar->get_cid();

                $tbody->add(tag('tr',
                                tag('th', $title),
                                tag('td',create_action_link("Edit", "cadmin",
						array("phpcid" => $cid)),"   ", create_action_link("Delete",
						"calendar_delete",
						array("cid" => $cid)))));
        }

        return tag('table', attributes("class=\"phpc-container\""),
			tag('caption', _('Calendar List')), $tbody);

}

function user_list()
{
	global $phpc_script, $phpcdb;

        $tbody = tag('tbody');

		$tbody->add(tag('tr', tag('th', _("Username")),
					tag('th', _("Group")),
					tag('th', _("Action"))));
        foreach($phpcdb->get_users() as $user) {
		$tbody->add(tag('tr', tag('th', $user->username),
					tag('td', $user->group),
					tag('td', create_action_link("Delete",
							"user_delete",
							array("uid" => $user->uid)))));
	}

        return tag('table', attributes("class=\"phpc-container\""),
			tag('caption', _('User List')), $tbody);

}

?>
