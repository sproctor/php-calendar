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

/*
   This file has the functions for the main displays of the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// Full display for a month
function display_week()
{
	global $vars;

	$heading_html = tag('tr');
	$heading_html->add(tag('th', __p('Week', 'W')));
	for($i = 0; $i < 7; $i++) {
		$d = ($i + day_of_week_start()) % 7;
		$heading_html->add(tag('th', day_name($d)));
	}

	if(!isset($vars['week']) || !isset($vars['year']))
		soft_error(__('Invalid date.'));

	$week_of_year = $vars['week'];
	$year = $vars['year'];

	$day_of_year = 1 + ($week_of_year - 1) * 7 - day_of_week(1, 1, $year);
	$from_stamp = mktime(0, 0, 0, 1, $day_of_year, $year);
	$start_month = date("n", $from_stamp);
	$start_year = date("Y", $from_stamp);

	$last_day = $day_of_year + 6;
	$to_stamp = mktime(0, 0, 0, 1, $last_day, $year);
	$end_month = date("n", $to_stamp);
	$end_year = date("Y", $to_stamp);

	$heading = month_name($start_month) .  " $start_year";
	if($end_month != $start_month)
		$heading .= " - " . month_name($end_month) . " $end_year";

	return tag('',
			tag("div", attributes('id="phpc-summary-view"'), 
				tag("div", attributes('id="phpc-summary-head"'),
					tag("div", attributes('id="phpc-summary-title"'), ''),
					tag("div", attributes('id="phpc-summary-author"'), ''),
					tag("div", attributes('id="phpc-summary-category"'), ''),
					tag("div", attributes('id="phpc-summary-time"'), '')),
				tag("div", attributes('id="phpc-summary-body"'), '')),
                        tag('table',
				attributes('class="phpc-main phpc-calendar"'),
                                tag('caption', $heading),
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
				create_week($week_of_year, $from_stamp,
					$to_stamp, $year)));
}

// creates a display for a particular week to be embedded in a month table
function create_week($week_of_year, $from_stamp, $to_stamp, $year) {
	global $phpcdb, $phpcid, $phpc_cal;

	$start_day = date("j", $from_stamp);
	$start_month = date("n", $from_stamp);
	$start_year = date("Y", $from_stamp);

	$max_events = $phpc_cal->events_max;

	$results = $phpcdb->get_occurrences_by_date_range($phpcid, $from_stamp,
			$to_stamp);
	$days_events = array();
	while($row = $results->fetch_assoc()) {
		$event = new PhpcOccurrence($row);

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
			if(sizeof($days_events[$key]) == $max_events)
				$days_events[$key][] = false;
			if(sizeof($days_events[$key]) > $max_events)
				continue;
			$days_events[$key][] = $event;
		}
	}
	$week_table = tag('tbody');
	$week_html = tag('tr', tag('th', $week_of_year));
	$week_table->add($week_html);
		
	for($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
		$day = $start_day + $day_of_week;
		$week_html->add(create_day($start_month, $day, $start_year,
					$days_events));
	}

	return $week_table;
}

// displays the day of the week and the following days of the week
function create_day($month, $day, $year, $days_events)
{
	global $phpc_script, $phpc_cal;

	$date_class = 'ui-state-default';
	if($day <= 0) {
		$month--;
		if($month < 1) {
			$month = 12;
			$year--;
		}
		$day += days_in_month($month, $year);
		$date_class .= ' phpc-shadow';
	} elseif($day > days_in_month($month, $year)) {
		$day -= days_in_month($month, $year);
		$month++;
		if($month > 12) {
			$month = 1;
			$year++;
		}
	} else {
		$currentday = date('j');
		$currentmonth = date('n');
		$currentyear = date('Y');

		// set whether the date is in the past or future/present
		if($currentyear == $year && $currentmonth == $month
				&& $currentday == $day) {
			$date_class .= ' ui-state-highlight';
		}
	}

	$click = create_plain_link($day, 'display_day', $year, $month,
			$day);
	$date_tag = tag('div', attributes("class=\"phpc-date $date_class\"",
				"onclick=\"window.location.href='$click'\""),
			create_action_link_with_date($day,
				'display_day', $year, $month, $day));

	if($phpc_cal->can_write()) {
		$date_tag->add(create_action_link_with_date('+',
					'event_form', $year, $month,
					$day,
					array('class="phpc-add"')));
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

	$html_events = tag('ul');
	$html_day->add($html_events);

	// Count the number of events
	foreach($results as $event) {
		if($event == false) {
			$event_html = tag('li',
					create_action_link_with_date(__("View Additional Events"),
						'display_day', $year, $month,
						$day,
						array('class="phpc-date"')));
			$html_events->add($event_html);
			break;
		}

		// TODO - make sure we have permission to read the event

		$subject = $event->get_subject();
		if($event->get_start_timestamp() >= $stamp)
			$event_time = $event->get_time_string();
		else
			$event_time = '(' . __('continued') . ')';
		if(!empty($event_time))
			$title = "$event_time - $subject";
		else
			$title = $subject;

		$style = "";
		if(!empty($event->text_color))
			$style .= "color: {$event->get_text_color()} !important;";
		if(!empty($event->bg_color))
			$style .= "background: ".$event->get_bg_color()
				." !important;";

		$event_html = tag('li',
				create_occurrence_link($title, "display_event",
					$event->get_oid(),
					array("style=\"$style\"")));

		$html_events->add($event_html);
	}

	return $html_day;
}

?>
