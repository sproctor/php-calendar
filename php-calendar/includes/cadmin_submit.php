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

function cadmin_submit() {
	global $phpcid, $phpc_cal, $vars, $phpcdb, $phpc_script;

        if(!$phpc_cal->can_admin()) {
                return tag('div', __('Permission denied'));
        }

	foreach(get_config_options() as $item) {
		if($item[2] == PHPC_CHECK) {
			if(isset($vars[$item[0]]))
				$value = "1";
			else
				$value = "0";
		} else {
			if(isset($vars[$item[0]])) {
				$value = $vars[$item[0]];
			} else {
				soft_error($item[0] . __(" was not set."));
			}
		}

		$phpcdb->update_config($phpcid, $item[0], $value);
	}

        return message_redirect(__('Updated options'),
			"$phpc_script?action=cadmin&phpcid=$phpcid");
}

?>
