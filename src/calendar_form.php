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

function calendar_form($context) {
        if(!is_admin()) {
                return tag('div', __('Permission denied'));
        }

	if(!empty($_REQUEST['submit_form']))
		process_form($context);

	return display_form($context);

}

function process_form($context)
{
	verify_token();

	$cid = $context->db->create_calendar();

	foreach(get_config_options() as $item) {
		$name = $item[0];
		$type = $item[2];

		if($type == PHPC_CHECK) {
			if(isset($_REQUEST[$name]))
				$value = "1";
			else
				$value = "0";
		} else {
			if(isset($_REQUEST[$name])) {
				$value = $vars[$name];
			} else {
				soft_error(__("$name was not set."));
			}
		}

		$context->db->set_calendar_config($cid, $name, $value);
	}

        $context->add_message(__('Calendar created.'));
}

function display_form($context)
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
			tag('div', attrs('class="phpc-sub-title"'),
				__('Create Calendar')),
			tag('table', attributes('class="phpc-container form"'),
				tag('tfoot',
                                        tag('tr',
						tag('td', ''),
                                                tag('td',
							create_hidden('phpc_token', $phpc_token),
							create_hidden('action', 'calendar_form'),
							create_hidden('submit_form', 'submit_form'),
							create_submit(__('Submit'))))),
				$tbody));

}


?>
