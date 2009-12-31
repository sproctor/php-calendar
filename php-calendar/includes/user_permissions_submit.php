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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

function user_permissions_submit()
{
	global $phpcid, $vars, $phpcdb;

        if(!can_admin_calendar($phpcid)) {
                return tag('div', _('Permission denied'));
        }

        if(empty($vars['uid'])) {
                return tag('div', _('No users'));
        }

	$users = array();

	foreach ($vars['uid'] as $uid) {
		$perm_names = array('read', 'write', 'readonly', 'modify',
				'admin');
		$old_perms = $phpcdb->get_permissions($phpcid, $uid);

		$new_perms = array();
		
		$different = false;
		foreach($perm_names as $perm_name) {
			$new_perms[$perm_name] =
				asbool(!empty($vars["$perm_name$uid"]));
			if(empty($old_perms[$perm_name]) !=
					empty($vars["$perm_name$uid"]))
				$different = true;
		}

		if ($different)
		{
			$users[] = $uid;
			$phpcdb->update_permissions($phpcid, $uid, $new_perms);
		}
	}
	if(sizeof($users) == 0)
		return tag("div", _('No changes to make.'));
	else
		return tag('div', _('Updated users:').' '
				.implode(', ', $users));
}

?>
