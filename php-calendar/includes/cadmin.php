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

function cadmin()
{
	global $phpc_cal;

        if(!$phpc_cal->can_admin()) {
                permission_error(_('You must be logged in as an admin.'));
        }

	return array(config_form(), user_list(), category_list());
}

function config_form()
{
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
		
        return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			$hidden_div,
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', _('Options')),
				tag('tfoot',
                                        tag('tr',
                                                tag('td', attributes('colspan="2"'),
							create_submit(_('Submit'))))),
				$tbody));

}

function user_list()
{
	global $phpc_script, $phpcid, $phpcdb, $vars;

	$users = $phpcdb->get_users_with_permissions($phpcid);

	$tbody = tag('tbody');

	foreach ($users as $user) {
		$tbody->add(tag('tr',
					tag('th', $user['username'],
						create_hidden('uid[]',
							$user['uid'])),
					tag('td', create_checkbox("read{$user['uid']}", "1", !empty($user['read']), _('Read'))),
					tag('td', create_checkbox("write{$user['uid']}", "1", !empty($user['write']), _('Write'))),
					tag('td', create_checkbox("readonly{$user['uid']}", "1", !empty($user['readonly']), _('Read-only'))),
					tag('td', create_checkbox("modify{$user['uid']}", "1", !empty($user['modify']), _('Modify'))),
					tag('td', create_checkbox("admin{$user['uid']}", "1", !empty($user['calendar_admin']), _('Admin')))
				   ));
	}

	$hidden_div = tag('div',
			create_hidden('action', 'user_permissions_submit'));
	if(isset($vars['phpcid']))
		$hidden_div->add(create_hidden('phpcid', $vars['phpcid']));
		
	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			$hidden_div,
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', _('User Permissions')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="6"'),
							create_submit(_('Submit'))))),
				tag('thead',
					tag('tr',
						tag('th', _('User Name')),
						tag('th', _('Read')),
						tag('th', _('Write')),
						tag('th', _('Can Create Read-Only')),
						tag('th', _('Modify')),
						tag('th', _('Admin')))),
				$tbody));
}

function category_list()
{
	global $phpc_script, $phpcid, $phpc_cal, $vars;

	$categories = $phpc_cal->get_categories();

	$tbody = tag('tbody');

	foreach ($categories as $category) {
		$name = empty($category['name']) ? _('No Name')
			: $category['name'];
		$catid = $category['catid'];
		$tbody->add(tag('tr',
					tag('th',$name),
					tag('td', htmlspecialchars($category['text_color'])),
					tag('td', htmlspecialchars($category['bg_color'])),
					tag('td', implode(',',$category['gid'])),
					tag('td', create_action_link(_('Edit'),
							'category_form',
							array('catid'
								=> $catid)),
						" ",
						create_action_link(_('Delete'),
							'category_delete',
							array('catid'
								=> $catid)))
				   ));
	}

	$create_link = create_action_link(_('Create category'), 'category_form',
			array('cid' => $phpcid));
	$table = tag('table', attributes("class=\"phpc-container\""),
			tag('caption', _('Calendar Categories')),
			tag('thead',
				tag('tr',
					tag('th', _('Name')),
					tag('th', _('Text Color')),
					tag('th', _('Background Color')),
					tag('th', _('Visible to User Group')),
					tag('th', _('Actions'))					
				   )),
			$tbody,
			tag('tfoot',
				tag('tr',
					tag('td', attributes('colspan="5"'),
						$create_link))));

	return tag('div', attributes('class="phpc-container"'), $table);
}

?>
