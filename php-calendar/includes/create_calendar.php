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

function create_calendar()
{
	global $vars, $phpcdb;

        if(!is_admin()) {
                return tag('div', _('Permission denied'));
        }

	verify_token();

	$cid = $phpcdb->create_calendar();

	foreach(get_config_options() as $item) {
		$name = $item[0];
		$type = $item[2];

		if($type == PHPC_CHECK) {
			if(isset($vars[$name]))
				$value = "1";
			else
				$value = "0";
		} else {
			if(isset($vars[$name])) {
				$value = $vars[$name];
			} else {
				soft_error(_("$name was not set."));
			}
		}

		$phpcdb->create_config($cid, $name, $value);
	}

        return tag('div', _('Calendar created.'));
}

?>
