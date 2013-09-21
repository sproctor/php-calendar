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
 * 
 * File created by Christakis Mina
 * Date: 2013-SEP-14
 */

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

function user_edit() {
	global $vars;

        if(!is_admin()) {
                return tag('div', __('Permission denied'));
        }

	if(!empty($vars['submit_form']))
		process_form();

	return display_form();

}

function display_form() {
	global $phpc_script, $phpc_token, $phpcdb, $vars;
        $html = tag('div', attributes('class="phpc-container"'));
	
	$groups = array();
	foreach($phpcdb->get_groups() as $group) {
		$groups[$group['gid']] = $group['name'];
	}

        if(empty($vars["uid"])) {
	        $html->add(tag('p', __('No user selected.')));
		return $html;
	}

	if (is_array($vars["uid"])) {
		$ids = $vars["uid"];
	} else {
		$ids = array($vars["uid"]);
	}
        
        $user = $phpcdb->get_user($ids[0]);
        //print_r($user);
        //print_r($ids);
	$usergroup = $user->get_groups();
        
	$size = sizeof($groups);
	if($size > 6)
		$size = 6;

	return tag('form', attributes("action=\"$phpc_script\"",
                                'method="post"'),
			tag('table', attributes("class=\"phpc-container\""),
				tag('caption', __('Edit User')),
				tag('tfoot',
					tag('tr',
						tag('td', attributes('colspan="2"'),
							create_hidden('phpc_token', $phpc_token),												
							create_hidden('action', 'user_edit'),												
							create_hidden('uid', $vars['uid']),
							create_hidden('submit_form', 'submit_form'),
							create_submit(__('Submit'))))),
				tag('tbody',
					tag('tr',
						tag('th', __('User Name')),
						//tag('td', create_text('user_name',$user->get_username()))),
						tag('td', $user->get_username())),
					tag('tr',
						tag('th', __('Password')),
						tag('td', create_password('password1'))),
					tag('tr',
						tag('th', __('Confirm Password')),
						tag('td', create_password('password2'))),
					tag('tr',
						tag('th', __('Make Admin')),
						tag('td', create_checkbox('make_admin', '1', $user->get_admin(), __('Admin')))),
					tag('tr',
						tag('th', __('Groups')),
						tag('td', create_select('groups[]',
								$groups, $usergroup[0]['gid'], attrs('multiple', "size=\"$size\""))))
				   )));
}

function process_form()
{
	global $phpcid, $vars, $phpcdb, $phpc_script;

	verify_token();

	print_r($vars);
	
       /*
        if(empty($vars['user_name'])) {
                return message(__('You must specify a user name'));
        }
       */

       /*
       if(empty($vars['password1'])) {
                return message(__('You must specify a password'));
        }
       */

       /*
        if(empty($vars['password2'])
                || $vars['password1'] != $vars['password2']) {
       */

        if($vars['password1'] != $vars['password2']) {
                return message(__('Your passwords did not match'));
        }

	$make_admin = empty($vars['make_admin']) ? 0 : 1;

        if(!empty($vars['password1'])) {
	      $passwd = md5($vars['password1']);
	}
       else {
	      $passwd = null;
	}
	/*
	if($phpcdb->get_user_by_name($vars["user_name"]))
		return message(__('User already exists.'));
       */

       $test = $phpcdb->edit_user($vars["uid"], $passwd, $make_admin);

	foreach($vars['groups'] as $gid) {
		$phpcdb->user_add_group($vars['uid'], $gid);
	}

        return message(__('User info edited.'));
}

?>
