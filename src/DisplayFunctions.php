<?php
/*
 * Copyright 2014 Sean Proctor
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

namespace PhpCalendar;

use PhpCalendar\Occurrence;
use PhpCalendar\Event;

class DisplayFunctions
{

function get_events($from_stamp, $to_stamp) {
	global $phpc_cal, $phpcdb, $phpcid;

	//echo "<pre>$from_stamp $to_stamp\n";
	$results = $phpcdb->get_occurrences_by_date_range($phpcid, $from_stamp,
			$to_stamp);
	$days_events = array();
	//var_dump($results);
	while($row = $results->fetch_assoc()) {
		//var_dump($row);
		//echo "here\n";
		$event = new Occurrence($row);
		if(!$event->can_read())
			continue;

		$end_stamp = mktime(0, 0, 0, $event->get_end_month(),
				$event->get_end_day(), $event->get_end_year());

		$start_stamp = mktime(0, 0, 0, $event->get_start_month(),
				$event->get_start_day(),
				$event->get_start_year());

		$diff = $from_stamp - $start_stamp;
		if($diff > 0)
			$add_days = floor($diff / 86400);
		else
			$add_days = 0;

		// put the event in every day until the end
		for(; ; $add_days++) {
			$stamp = mktime(0, 0, 0, $event->get_start_month(),
					$event->get_start_day() + $add_days,
					$event->get_start_year());

			if($stamp > $end_stamp || $stamp > $to_stamp)
				break;

			$key = date('Y-m-d', $stamp);
			if(!isset($days_events[$key]))
				$days_events[$key] = array();
			if(sizeof($days_events[$key]) == $phpc_cal->events_max)
				$days_events[$key][] = false;
			if(sizeof($days_events[$key]) > $phpc_cal->events_max)
				continue;
			$days_events[$key][] = $event;
		}
	}
	return $days_events;
}

// creates a display for a particular week to be embedded in a month table
function create_week($from_stamp, $year, $days_events) {
	$start_day = date("j", $from_stamp);
	$start_month = date("n", $from_stamp);
	$start_year = date("Y", $from_stamp);
	$week_of_year = week_of_year($start_month, $start_day, $start_year);

	// Non ISO, the week should be of this year.
	if(day_of_week_start() != 1) {
		if($start_year < $year) {
			$week_of_year = 1;
		}
	} else {
		// Use week's year as year for ISO
		$year = $start_year;
	}
	

	$week_html = tag('tr', tag('th',
				attrs('class="phpc-date ui-state-default"'),
				create_action_link($week_of_year,
					'display_week',
					array('week' => $week_of_year,
						'year' => $year))));
		
	for($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
		$day = $start_day + $day_of_week;
		$week_html->add(DisplayFunctions::create_day($start_month, $day, $start_year,
					$days_events));
	}

	return $week_html;
}

// displays the day of the week and the following days of the week
function create_day($month, $day, $year, $days_events) {
	global $phpc_script, $phpc_cal, $action, $phpc_month;

	$date_class = 'ui-state-default';
	if($day <= 0) {
		$month--;
		if($month < 1) {
			$month = 12;
			$year--;
		}
		$day += days_in_month($month, $year);
	} elseif($day > days_in_month($month, $year)) {
		$day -= days_in_month($month, $year);
		$month++;
		if($month > 12) {
			$month = 1;
			$year++;
		}
	}

	if($action == "display_month" && $month != $phpc_month) {
		$date_class .= ' phpc-shadow';
	}

	$currentday = date('j');
	$currentmonth = date('n');
	$currentyear = date('Y');

	// set whether the date is in the past or future/present
	if($currentyear == $year && $currentmonth == $month
			&& $currentday == $day) {
		$date_class .= ' ui-state-highlight';
	}

	$date_tag = tag('div', attributes("class=\"phpc-date $date_class\""),
			create_action_link_with_date($day,
				'display_day', $year, $month, $day));

	if($phpc_cal->can_write()) {
		$date_tag->add(create_action_link_with_date('+',
					'event_form', $year, $month,
					$day,
					attrs('class="phpc-add"')));
	}

	$html_day = tag('td', $date_tag);

	$stamp = mktime(0, 0, 0, $month, $day, $year);

	$can_read = $phpc_cal->can_read(); 
	$key = date('Y-m-d', $stamp);
	if(!$can_read || !array_key_exists($key, $days_events))
		return $html_day;

	$results = $days_events[$key];
	if(empty($results))
		return $html_day;

	$html_events = tag('ul', attrs('class="phpc-event-list"'));
	$html_day->add($html_events);

	// Count the number of events
	foreach($results as $event) {
		if($event == false) {
			$event_html = tag('li',
					create_action_link_with_date(__("View Additional Events"),
						'display_day', $year, $month,
						$day,
						attrs('class="phpc-date"')));
			$html_events->add($event_html);
			break;
		}

		// TODO - make sure we have permission to read the event

		$subject = $event->get_subject();
		if($event->get_start_timestamp() >= $stamp) {
			$event_time = $event->get_time_string();
			if(!empty($event_time))
				$event_time = tag('span', attrs('class="phpc-event-time ui-corner-all"'), $event_time);
		} else {
			$event_time = tag('span', attrs('class="fa fa-share"'), '');
		}

		if(!empty($event_time))
			$title = tag('span', $event_time, $subject);
		else
			$title = tag('span', $subject);

		$style = "";
		if(!empty($event->text_color))
			$style .= "color: {$event->get_text_color()} !important;";
		if(!empty($event->bg_color))
			$style .= "background: ".$event->get_bg_color()
				." !important;";

		$event_html = tag('li',
				create_occurrence_link($title, "display_event",
					$event->get_oid(),
					attrs("style=\"$style\"")));

		$html_events->add($event_html);
	}

	return $html_day;
}

function create_display_table($heading, $contents) {


	$heading_html = tag('tr', attrs('class="ui-widget-header"'));
	$heading_html->add(tag('th', __p('Week', 'W')));
	for($i = 0; $i < 7; $i++) {
		$d = ($i + day_of_week_start()) % 7;
		$heading_html->add(tag('th', day_name($d)));
	}

	return tag('div',
			tag("div", attributes('id="phpc-summary-view"'), 
				tag("div", attributes('id="phpc-summary-head"'),
					tag("div", attributes('id="phpc-summary-title"'), ''),
					tag("div", attributes('id="phpc-summary-author"'), ''),
					tag("div", attributes('id="phpc-summary-category"'), ''),
					tag("div", attributes('id="phpc-summary-time"'), '')),
				tag("div", attributes('id="phpc-summary-body"'), '')),
			tag('div', attrs('class="phpc-sub-title phpc-month-title ui-widget-content"'), $heading),
                        tag('table', attrs('class="phpc-month-view"'),
                                tag('colgroup',
					tag('col', attributes('class="phpc-week"')),
					tag('col', attributes('class="phpc-day"')),
					tag('col', attributes('class="phpc-day"')),
					tag('col', attributes('class="phpc-day"')),
					tag('col', attributes('class="phpc-day"')),
					tag('col', attributes('class="phpc-day"')),
					tag('col', attributes('class="phpc-day"')),
					tag('col', attributes('class="phpc-day"'))
				   ),
				tag('thead', $heading_html),
                                tag('tbody', $contents)));
}

}
?>
