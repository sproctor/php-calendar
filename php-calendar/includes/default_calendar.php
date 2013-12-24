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

function default_calendar()
{
	global $vars, $phpcdb, $phpc_script, $phpc_user;

	$html = tag('div', attributes('class="phpc-container"'));

	if(empty($vars["cid"])) {
		$html->add(tag('p', __('No calendar selected.')));
		return $html;
	}

	if ($phpc_user->is_admin()) {
		$phpcdb->set_default_cid($vars['cid']);
		$html->add(tag('p', __('Default calendar set to: ')
					. $vars['cid']));
	}

        return message_redirect($html, "$phpc_script?action=admin");
}

?>
