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

// View for a single day
function display_day()
{
	global $phpcid, $phpc_cal, $phpc_user, $phpc_script, $phpcdb, $day,
	       $month, $year;

	$monthname = month_name($month);

	$results = $phpcdb->get_occurrences_by_date($phpcid, $year, $month,
			$day);

	$today_epoch = mktime(0, 0, 0, $month, $day, $year);

	$have_events = false;

	$html_table = tag('table', attributes('class="phpc-main"'),
			tag('caption', "$day $monthname $year"),
			tag('thead',
				tag('tr',
					tag('th', __('Title')),
					tag('th', __('Time')),
					tag('th', __('Description'))
				   )));
	if($phpc_cal->can_modify()) {
		$html_table->add(tag('tfoot',
					tag('tr',
						tag('td',
							attributes('colspan="4"'),
							create_hidden('action', 'event_delete'),
							create_hidden('day', $day),
							create_hidden('month', $month),
							create_hidden('year', $year),
							create_submit(__('Delete Selected'))))));
	}

	$html_body = tag('tbody');

	while($row = $results->fetch_assoc()) {
	
		$event = new PhpcOccurrence($row);

		if(!$event->can_read())
			continue;

		$have_events = true;

		$eid = $event->get_eid();
		$oid = $event->get_oid();

		$html_subject = tag('td');

		if($event->can_modify()) {
			$html_subject->add(create_checkbox('eid[]',
						$eid));
		}

		$html_subject->add(create_occurrence_link(tag('strong',
						$event->get_subject()),
					'display_event', $oid));

		if($event->can_modify()) {
			$html_subject->add(" (");
			$html_subject->add(create_event_link(
						__('Modify'), 'event_form',
						$eid));
			$html_subject->add(')');
		}

		$html_body->add(tag('tr',
					$html_subject,
					tag('td', $event->get_time_span_string()),
					tag('td', $event->get_desc())));
	}

	$html_table->add($html_body);

	if($phpc_cal->can_modify()) {
		$output = tag('form',
				attributes("action=\"$phpc_script\""),
				$html_table);
	} else {
		$output = $html_table;
	}

	if(!$have_events)
		$output = tag('h2', __('No events on this day.'));

	return tag('', create_day_menu(), $output);
}

function create_day_menu() {
	global $month, $day, $year;

	$html = tag('div', attrs('class="phpc-bar ui-widget-content"'));

	$monthname = month_name($month);

	$lasttime = mktime(0, 0, 0, $month, $day - 1, $year);
	$lastday = date('j', $lasttime);
	$lastmonth = date('n', $lasttime);
	$lastyear = date('Y', $lasttime);
	$lastmonthname = month_name($lastmonth);

	$last_args = array('year' => $lastyear, 'month' => $lastmonth,
			'day' => $lastday);

	menu_item_prepend($html, "$lastmonthname $lastday", 'display_day',
			$last_args);

	$nexttime = mktime(0, 0, 0, $month, $day + 1, $year);
	$nextday = date('j', $nexttime);
	$nextmonth = date('n', $nexttime);
	$nextyear = date('Y', $nexttime);
	$nextmonthname = month_name($nextmonth);

	$next_args = array('year' => $nextyear, 'month' => $nextmonth,
			'day' => $nextday);

	menu_item_append($html, "$nextmonthname $nextday", 'display_day',
			$next_args);

	return $html;
}

?>
