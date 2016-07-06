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

/*
   This file has the functions for the main displays of the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// Full view for a single event
function display_event(Context $context)
{
	if(!empty($_REQUEST['content']) && $_REQUEST['content'] == 'json')
		return display_event_json($context);
	
	if(isset($_REQUEST['oid'])) {
		$event = $context->db->get_event_by_oid($_REQUEST['oid']);
		if(!$event) {
			return tag('p', __('There is no event for that OID.'));
		}
	} elseif(isset($_REQUEST['eid'])) {
		$event = $context->db->get_event_by_eid($_REQUEST['eid']);
		if(!$event) {
			return tag('p', __('There is no event with that EID.'));
		}
	} else {
		soft_error(__("Invalid arguments."));
	}

	if(!$event->can_read($context->user)) {
		return tag('p', __("You do not have permission to read this event."));
	}

	$event_header = tag('div', attributes('class="phpc-event-header"'),
			tag('div', __('created by').' ',
				tag('cite', $event->get_author()),
				' ' . __('on') . ' ' . $event->get_ctime_string()));

	if(!empty($event->mtime))
		$event_header->add(tag('div', __('Last modified on '),
				$event->get_mtime_string()));

	$category = $event->get_category();
	if(!empty($category))
		$event_header->add(tag('div', __('Category') . ': '
					. $category));

	// Add modify/delete links if this user has access to this event.
	$event_menu = '';
        if($event->can_modify()) {
		$event_menu = tag('div',
				attrs('class="phpc-bar ui-widget-content"'),
				create_event_link(__('Modify'),
					'event_form', $event->get_eid()), "\n",
				create_event_link(__('Delete'),
					'event_delete', $event->get_eid(), attrs('class="phpc-confirm"')));
	}

	$desc_tag = tag('div', attributes('class="phpc-desc"'),
			tag('h3', __("Description")),
			tag('p', $event->get_desc()));

	$occurrences_tag = tag('ul');
	$occurrences = $context->db->get_occurrences_by_eid($event->get_eid());
	$set_date = false;
	foreach($occurrences as $occurrence) {
		if(!$set_date) {
			$context->year = $occurrence->get_start_year();
			$context->month = $occurrence->get_start_month();
			$context->day = $occurrence->get_start_day();
		}
		$oid = $occurrence->get_oid();
		$occ_tag = tag('li', attrs('class="ui-widget-content"'),
				$occurrence->get_date_string() . ' ' . __('at')
				. ' ' . $occurrence->get_time_span_string());
		if($event->can_modify()) {
			$occ_tag->add(" ",
					create_occurrence_link(__('Edit'), 'occur_form', $oid), " ",
					create_occurrence_link(__('Remove'), 'occurrence_delete', $oid,
						attrs('class="phpc-confirm-occ"')));
		}
		$occurrences_tag->add($occ_tag);
	}

	// Add occurrence link if this user has access to this event.
	$occurrences_menu = '';
        if($event->can_modify()) {
		$occurrences_menu = tag('div',
				attrs('class="phpc-bar ui-widget-content"'),
				create_event_link(__('Add Occurrence'),
					'occur_form', $event->get_eid()));
	}

	foreach($event->get_fields() as $field) {
		$def = $context->calendar->get_field($field['fid']);
		$event_header->add(tag('div', $def['name'] . ": " . $field['value']));
	}

	$dialog = tag('div', attrs('id="phpc-dialog"', 'title="' . __("Confirmation required") . '"'),
			__("Permanently delete this event?"));
	$dialog2 = tag('div', attrs('id="phpc-dialog-occ"', 'title="' . __("Confirmation required") . '"'),
			__("Permanently delete this occurrence?"));

	return tag('div', attributes('class="phpc-main phpc-event"'), $dialog, $dialog2,
			$event_menu, tag('h2', $event->get_subject()),
			$event_header, $desc_tag,
			tag('div', attrs('class="phpc-occ"'),
				tag('h3', __('Occurrences')),
				$occurrences_menu,
				$occurrences_tag));
}

// generates a JSON data structure for a particular event
function display_event_json(Context $context)
{
	if(!isset($_REQUEST['oid']))
		return "";

	$event = $context->db->get_occurrence_by_oid($_REQUEST['oid']);

	if(!$event->can_read())
		return "";

	$author = __("by") . " " . $event->get_author();
	$time_str = $event->get_time_span_string();
	$date_str = $event->get_date_string();

	$category = $event->get_category();
	if(empty($category))
		$category_text = '';
	else
		$category_text = __('Category') . ': ' . $event->get_category();

		if ($time_str!="") $time="$date_str " . __("from") . " $time_str";
		else $time="$date_str ";
		
	return json_encode(array("title" => $event->get_subject(),
				"author" => $author,
				"time" => $time,
				"category" => $category_text,
				"body" => $event->get_desc()));
}

?>
