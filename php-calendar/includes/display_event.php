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

/*
   This file has the functions for the main displays of the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// Full view for a single event
function display_event()
{
	global $phpcdb, $vars;

	if(!empty($vars['contentType']) && $vars['contentType'] == 'json')
		return display_event_json();

	if(!isset($vars['eid']))
		soft_error("Argument eid must be defined.");

	$eid = $vars['eid'];

	$event = $phpcdb->get_event_by_id($eid);

	if(!can_read_event($event)) {
		return tag('p', _("You do not have permission to read this event."));
	}

	$event_header = tag('div', attributes('class="phpc-event-header"'),
			tag('div', 'by ', tag('cite', $event->get_username())));
	// Add modify/delete links if this user has access to this event.
        if(can_modify_event($event)) {
		$event_header->add(tag('div',
					create_event_link(_('Modify'),
						'event_form', $eid), "\n",
					create_event_link(_('Delete'),
						'event_delete', $eid)));
	}
	$event_header->add(tag('',
				tag('div', _('When').": ".$event->get_date_string() . _(' at ').$event->get_time_span_string())));

	$event_tag = tag('div', attributes('class="phpc-event"'),
			$event_header,
			tag('p', attributes('class="phpc-desc"'),
				$event->get_desc()));

	return tag('div', attributes('class="phpc-main"'),
			tag('h2', $event->get_subject()), $event_tag);
}

// generates a JSON data structure for a particular event
function display_event_json()
{
	global $phpcdb, $vars;

	if(!isset($vars['eid']))
		return "";

	$event = $phpcdb->get_event_by_id($vars['eid']);

	if(!can_read_event($event))
		return "";

	$time_str = $event->get_time_span_string();
	$date_str = $event->get_date_string();

	return json_encode(array("title" => $event->get_subject(),
				"time" => "$date_str at $time_str",
				"body" => $event->get_desc()));
}

?>
