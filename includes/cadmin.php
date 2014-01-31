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

function cadmin() {
	global $phpc_cal;

        if(!$phpc_cal->can_admin()) {
                permission_error(__('You must be logged in as an admin.'));
        }

	$index = tag('ul',
			tag('li', tag('a', attrs('href="#phpc-config"'),
					__('Calendar Configuration'))),
			tag('li', tag('a', attrs('href="#phpc-users"'),
					__('Users'))),
			tag('li', tag('a', attrs('href="#phpc-categories"'),
					__('Categories'))),
			tag('li', tag('a', attrs('href="#phpc-groups"'),
					__('Groups'))));

	return tag('div', attrs("class=\"phpc-tabs\""), $index, config_form(),
			user_list(), category_list(), group_list());
}

function config_form() {
	global $phpc_cal, $phpc_script, $vars;

        $tbody = tag('tbody');

        foreach(get_config_options() as $element) {
                $name = $element[0];
                $text = $element[1];
		$default = $phpc_cal->$name;
		$input = create_config_input($element, $default);

                $tbody->add(tag('tr',
                                tag('th', $text),
                                tag('td', $input)));
        }

	$hidden_div = tag('div',
			create_hidden('action', 'cadmin_submit'));
	if(isset($vars['phpcid']))
		$hidden_div->add(create_hidden('phpcid', $vars['phpcid']));
		
	return tag('div', attrs('id="phpc-config"'),
			tag('form', attributes("action=\"$phpc_script\"",
					'method="post"'),
				$hidden_div,
				tag('table',
					attrs('class="phpc-container form ui-widget"'),
					tag('tfoot',
						tag('tr',
							tag('td', ''),
							tag('td',
								create_submit(__('Submit'))),
							$tbody)))));
}

function user_list()
{
	global $phpc_script, $phpcid, $phpcdb, $vars;

	$users = $phpcdb->get_users_with_permissions($phpcid);

	$tbody = tag('tbody');

	foreach ($users as $user) {
		$phpc_user = new PhpcUser($user);
		$group_list = array();
		foreach($phpc_user->get_groups() as $group) {
			if($group['cid'] == $phpcid)
				$group_list[] = $group['name'];
		}
		$groups = implode(', ', $group_list);
		$tbody->add(tag('tr',
					tag('td', $user['username'],
						create_hidden('uid[]',
							$user['uid'])),
					tag('td', create_checkbox("read{$user['uid']}", "1", !empty($user['read']), __('Read'))),
					tag('td', create_checkbox("write{$user['uid']}", "1", !empty($user['write']), __('Write'))),
					tag('td', create_checkbox("readonly{$user['uid']}", "1", !empty($user['readonly']), __('Read-only'))),
					tag('td', create_checkbox("modify{$user['uid']}", "1", !empty($user['modify']), __('Modify'))),
					tag('td', create_checkbox("admin{$user['uid']}", "1", !empty($user['calendar_admin']), __('Admin'))),
					tag('td', $groups),
					tag('td', create_action_link(__("Edit Groups"), "user_groups", array("uid" => $user["uid"])))
				   ));
	}

	$hidden_div = tag('div',
			create_hidden('action', 'user_permissions_submit'));
	if(isset($vars['phpcid']))
		$hidden_div->add(create_hidden('phpcid', $vars['phpcid']));
		
	$form = tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			$hidden_div,
			tag('div', attrs('class="phpc-sub-title"'),
				__('User Permissions')),
			tag('table', attributes("class=\"phpc-container\""),
				tag('thead',
					tag('tr', attrs('class="ui-widget-header"'),
						tag('th', __('User Name')),
						tag('th', __('Read')),
						tag('th', __('Write')),
						tag('th', __('Can Create Read-Only')),
						tag('th', __('Modify')),
						tag('th', __('Admin')),
						tag('th', __('Groups')),
						tag('th', __('Edit Groups'))
					   )), $tbody),
			create_submit(__('Submit')));

	return tag('div', attrs('id="phpc-users"'), $form);
}

function category_list()
{
	global $phpc_script, $phpcid, $phpc_cal, $vars;

	$categories = $phpc_cal->get_categories();

	$tbody = tag('tbody');

	$have_contents = false;
	foreach ($categories as $category) {
		$have_contents = true;
		$name = empty($category['name']) ? __('No Name')
			: $category['name'];
		$catid = $category['catid'];
		$group = empty($category['group_name']) ? __('Any')
			: $category['group_name'];
		$tbody->add(tag('tr',
					tag('td', $name),
					tag('td', htmlspecialchars($category['text_color'])),
					tag('td', htmlspecialchars($category['bg_color'])),
					tag('td', htmlspecialchars($group)),
					tag('td', create_action_link(__('Edit'),
							'category_form',
							array('catid'
								=> $catid)),
						" ",
						create_action_link(__('Delete'),
							'category_delete',
							array('catid'
								=> $catid)))
				   ));
	}

	if (!$have_contents) {
		$tbody->add(tag('tr', tag('td', attrs('colspan=5'),
						__('No Categories.'))));
	}

	$table = tag('table', attrs('class="phpc-container"'),
			tag('thead',
				tag('tr', attrs('class="ui-widget-header"'),
					tag('th', __('Name')),
					tag('th', __('Text Color')),
					tag('th', __('Background Color')),
					tag('th', __('Accessible to Group')),
					tag('th', __('Actions'))
				   )),
			$tbody);

	return tag('div', attrs('id="phpc-categories"'),
			tag('div', attrs('class="phpc-sub-title"'),
				__('Calendar Categories')),
			$table,
			create_action_link(__('Create category'),
				'category_form', array('cid' => $phpcid),
				attrs('class="phpc-button"')));
}

function group_list() {
	global $phpc_script, $phpcid, $phpc_cal, $vars;

	$groups = $phpc_cal->get_groups();

	$tbody = tag('tbody');

	$have_contents = false;
	foreach ($groups as $group) {
		$have_contents = true;
		$name = empty($group['name']) ? __('No Name')
			: $group['name'];
		$id = $group['gid'];
		$tbody->add(tag('tr',
					tag('td', $name),
					tag('td', create_action_link(__('Edit'),
							'group_form',
							array('gid' => $id)),
						" ",
						create_action_link(__('Delete'),
							'group_delete',
							array('gid' => $id)))
				   ));
	}

	if (!$have_contents) {
		$tbody->add(tag('tr', tag('td', attrs('colspan=2'),
						__('No Groups'))));
	}

	$table = tag('table', attrs('class="phpc-container"'),
			tag('thead',
				tag('tr', attrs('class="ui-widget-header"'),
					tag('th', __('Name')),
					tag('th', __('Actions'))
				   )),
			$tbody);

	return tag('div', attrs('id="phpc-groups"'),
			tag('div', attrs('class="phpc-sub-title"'),
				__('Calendar Groups')),
			$table,
			create_action_link(__('Create group'), 'group_form',
				array('cid' => $phpcid),
				attrs('class="phpc-button"')));
}

?>
