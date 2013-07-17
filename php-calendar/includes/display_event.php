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
function display_event()
{
	global $vars;

	if(!empty($vars['contentType']) && $vars['contentType'] == 'json')
		return display_event_json();
	
	if(isset($vars['oid']))
		return display_event_by_oid($vars['oid']);
	
	if(isset($vars['eid']))
		return display_event_by_eid($vars['eid']);

	// If we get here, we did something wrong
	soft_error(__("Invalid arguments."));
}

function display_event_by_oid($oid)
{
	global $phpcdb, $year, $month, $day;

	$event = $phpcdb->get_occurrence_by_oid($oid);

	$eid = $event->get_eid();

	if(!$event->can_read()) {
		return tag('p', __("You do not have permission to read this event."));
	}

	$event_header = tag('div', attributes('class="phpc-event-header"'),
			tag('div',attributes('class="phpc-event-creator"'), __('by').' ',
				tag('cite', $event->get_author())));

	$category = $event->get_category();
	if(!empty($category))
		$event_header->add(tag('div',attributes('class="phpc-event-cats"'), __('Category') . ': '
					. $category));

	$event_header->add(tag('div',attributes('class="phpc-event-time"'),
				__('When').": ".$event->get_datetime_string()));
	
	$event_header->add(tag('div', __('Created at: '), $event->ctime));
	if(!empty($event->mtime))
		$event_header->add(tag('div', __('Last modified at: '),
				$event->mtime));
				
	$menu_tag = tag('div', attrs('class="phpc-bar ui-widget-content"')); 
	// Add modify/delete links if this user has access to this event.
        if($event->can_modify()) {
		$menu_tag->add(array(create_event_link(__('Modify'),
						'event_form', $eid), "\n",
					create_event_link(__('Delete'),
						'event_delete', $eid), "\n",
					create_occurrence_link(__('Modify Occurrence'),
						'occur_form', $oid), "\n",
					create_occurrence_link(__('Remove Occurrence'),
						'occurrence_delete', $oid),
					"\n"));
	}


	$occurrences = $phpcdb->get_occurrences_by_eid($eid);
		//$occurrence_div = tag('div', );
	$i = 0;
	while($i < sizeof($occurrences)) {
		if($occurrences[$i]->get_oid() == $oid)
			break;
		$i++;
	}
	// if we have a previous event
	$prev = $i - 1;
	if($prev >= 0) {
		$prev_occur = $occurrences[$prev];
		$menu_tag->add(create_occurrence_link(
					__('Previous occurrence on') . " " .
					$prev_occur->get_date_string(),
					'display_event',
					$prev_occur->get_oid()), ' ');
	}
	// if we have a future event
	$next = $i + 1;
	if($next < sizeof($occurrences)) {
		$next_occur = $occurrences[$next];
		$menu_tag->add(create_occurrence_link(
					__('Next occurrence on') . " " .
					$next_occur->get_date_string(),
					'display_event',
					$next_occur->get_oid()), ' ');
	}

	$menu_tag->add(create_event_link(__('View All Occurrences'),
				'display_event', $eid));

	$event_header->add($menu_tag);

	$year = $event->get_start_year();
	$month = $event->get_start_month();
	$day = $event->get_start_day();

	$desc_tag = tag('div', attributes('class="phpc-desc"'),
			tag('h3', __("Description")),
			tag('p', $event->get_desc()));

	return tag('div', attributes('class="phpc-main phpc-event"'),
			tag('h2', $event->get_subject()), $event_header,
			$desc_tag);
}

function display_event_by_eid($eid)
{
	global $phpcdb, $year, $month, $day;

	$event = new PhpcEvent($phpcdb->get_event_by_eid($eid));

	if(!$event->can_read()) {
		return tag('p', __("You do not have permission to read this event."));
	}

	$event_header = tag('div', attributes('class="phpc-event-header"'),
			tag('div', __('by').' ',
				tag('cite', $event->get_author())));

	$event_header->add(tag('div', __('Created at: '), $event->ctime));
	if(!empty($event->mtime))
		$event_header->add(tag('div', __('Last modified at: '),
				$event->mtime));

	$category = $event->get_category();
	if(!empty($category))
		$event_header->add(tag('div', __('Category') . ': '
					. $category));

	// Add modify/delete links if this user has access to this event.
        if($event->can_modify()) {
		$event_header->add(tag('div', attrs('class="phpc-bar ui-widget-content"'),
					create_event_link(__('Modify'),
						'event_form', $eid), "\n",
					create_event_link(__('Add Occurrence'),
						'occur_form', $eid), "\n",
					create_event_link(__('Delete'),
						'event_delete', $eid)));
	}

	$desc_tag = tag('div', attributes('class="phpc-desc"'),
			tag('h3', __("Description")),
			tag('p', $event->get_desc()));

	$occurrences_tag = tag('ul');
	$occurrences = $phpcdb->get_occurrences_by_eid($eid);
	$set_date = false;
	foreach($occurrences as $occurrence) {
		if(!$set_date) {
			$year = $occurrence->get_start_year();
			$month = $occurrence->get_start_month();
			$day = $occurrence->get_start_day();
		}
		$oid = $occurrence->get_oid();
		$occ_tag = tag('li', attrs('class="ui-widget-content"'),
				create_occurrence_link(
					$occurrence->get_date_string()
					. ' ' . __('at') . ' '
					. $occurrence->get_time_span_string(),
					'display_event',
					$oid));
		if($event->can_modify()) {
			$occ_tag->add(" ",
					create_occurrence_link(__('Edit'), 'occur_form', $oid), " ",
					create_occurrence_link(__('Remove'), 'occurrence_delete', $oid));
		}
		$occurrences_tag->add($occ_tag);
	}

	return tag('div', attributes('class="phpc-main phpc-event"'),
			tag('h2', $event->get_subject()), $event_header,
			$desc_tag, tag ('div',attributes('class="phpc-occ"'),tag('h3', __('Occurrences')),
			$occurrences_tag));
}

// generates a JSON data structure for a particular event
function display_event_json()
{
	global $phpcdb, $vars;

	if(!isset($vars['oid']))
		return "";

	$event = $phpcdb->get_occurrence_by_oid($vars['oid']);

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
