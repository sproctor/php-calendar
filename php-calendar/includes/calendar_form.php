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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

function calendar_form() {
	global $vars;

        if(!is_admin()) {
                return tag('div', __('Permission denied'));
        }

	if(!empty($vars['submit_form']))
		process_form();

	return display_form();

}

function process_form()
{
	global $vars, $phpcdb, $phpc_script;

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
				soft_error(__("$name was not set."));
			}
		}

		$phpcdb->create_config($cid, $name, $value);
	}

        message(__('Calendar created.'));
}

function display_form()
{
	global $phpc_script, $phpc_token;

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
				tag('caption', __('Create Calendar')),
				tag('tfoot',
                                        tag('tr',
                                                tag('td', attributes('colspan="2"'),
							create_hidden('phpc_token', $phpc_token),
							create_hidden('action', 'calendar_form'),
							create_hidden('submit_form', 'submit_form'),
							create_submit(__('Submit'))))),
				$tbody));

}


?>
