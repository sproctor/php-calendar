<?php
/*
 * Copyright 2011 Sean Proctor
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

require_once("$phpc_includes_path/calendar.php");

// Full display for a month
function display_month()
{
	global $phpcid, $month, $year;

	$heading_html = tag('tr');
	$heading_html->add(tag('th', 'W'));
	for($i = 0; $i < 7; $i++) {
		$d = ($i + day_of_week_start()) % 7;
		$heading_html->add(tag('th', day_name($d)));
	}

	$month_navbar = month_navbar($month, $year);
	return tag('div',
			tag("script", attributes('type="text/javascript"',
					'src="static/jquery.hoverIntent.minified.js"'), ''),
			tag("script", attributes('type="text/javascript"',
					'src="static/summary.js"'), ''),
			tag("div", attributes('id="phpc-summary-view"'), 
				tag("div", attributes('class="phpc-summary-head"'),
					tag("div", attributes('id="phpc-summary-title"'), ''),
					tag("div", attributes('id="phpc-summary-time"'), '')),
				tag("div", attributes('id="phpc-summary-body"'), '')),
                        $month_navbar,
                        tag('table',
				attributes('class="phpc-main phpc-calendar"'),
                                tag('caption', month_name($month)." $year"),
                                tag('colgroup', attributes('span="7"',
						'width="14%"'),
					tag('col', attributes('width="3%"'))),
                                tag('thead', $heading_html),
                                create_month($month, $year)),
			$month_navbar);
}

// creates a menu to navigate the month/year
// returns XHTML data for the menu
function month_navbar($month, $year)
{
	$html = tag('div', attributes('class="phpc-month-nav"'));
	$prev_month = $month - 1;
	$prev_year = $year;
	if($prev_month < 1) {
		$prev_month += 12;
		$prev_year--;
	}
	menu_item_append_with_date($html, _('last year'), 'display_month',
			$year - 1, $month);
	menu_item_append_with_date($html, _('last month'), 'display_month',
			$prev_year, $prev_month);

	for($i = 1; $i <= 12; $i++) {
		if($i < $month)
			$attribs = 'class="phpc-past"';
		elseif($i == $month)
			$attribs = 'class="phpc-present"';
		else
			$attribs = 'class="phpc-future"';

		menu_item_append_with_date($html, short_month_name($i),
				'display_month', $year, $i, false, $attribs);
	}

	$next_month = $month + 1;
	$next_year = $year;
	if($next_month > 12) {
		$next_month -= 12;
		$next_year++;
	}
	menu_item_append_with_date($html, _('next month'), 'display_month',
			$next_year, $next_month);
	menu_item_append_with_date($html, _('next year'), 'display_month',
			$year + 1, $month);

	return $html;
}

// creates a display for a particular month to be embedded in a full view
function create_month($month, $year)
{
	global $phpcdb, $phpcid;

	$wim = weeks_in_month($month, $year);

	$first_day = 1 - day_of_week($month, 1, $year);
	$from_stamp = mktime(0, 0, 0, $month, $first_day, $year);

	$last_day = $wim * 7 - day_of_week($month, 1, $year);
	$to_stamp = mktime(0, 0, 0, $month, $last_day, $year);

	$results = $phpcdb->get_occurrences_by_date_range($phpcid, $from_stamp,
			$to_stamp);
	$days_events = array();
	foreach($results as $event) {
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
			$days_events[$key][] = $event;
		}
	}

	$month_table = tag('tbody');
	for($week_of_month = 1; $week_of_month <= $wim; $week_of_month++) {
		$month_table->add(create_week($week_of_month, $month, $year,
					$days_events));
	}

	return $month_table;
}

// creates a display for a particular week to be embedded in a month table
function create_week($week_of_month, $month, $year, $days_events)
{
	$start_day = 1 + ($week_of_month - 1) * 7
		- day_of_week($month, 1, $year);
	$week_of_year = week_of_year($month, $start_day, $year);

	$week_html = tag('tr', tag('th', $week_of_year));
	
	for($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
		$day = $start_day + $day_of_week;
		$week_html->add(create_day($month, $day, $year, $days_events));
	}

        return $week_html;
}

// displays the day of the week and the following days of the week
function create_day($month, $day, $year, $days_events)
{
	global $phpc_script, $phpcid;

	if($day <= 0) {
		$month--;
		if($month < 1) {
			$month = 12;
			$year--;
		}
		$day += days_in_month($month, $year);
		$current_era = 'none';
	} elseif($day > days_in_month($month, $year)) {
		$day -= days_in_month($month, $year);
		$month++;
		if($month > 12) {
			$month = 1;
			$year++;
		}
		$current_era = 'none';
	} else {
		$currentday = date('j');
		$currentmonth = date('n');
		$currentyear = date('Y');

		// set whether the date is in the past or future/present
		if($currentyear == $year && $currentmonth == $month
				&& $currentday == $day) {
			$current_era = 'present';
		} elseif($currentyear > $year || $currentyear == $year
				&& ($currentmonth > $month
					|| $currentmonth == $month 
					&& $currentday > $day
				   )) {
			$current_era = 'past';
		} else {
			$current_era = 'future';
		}
	}

	$date_tag = tag('div', attributes('class="phpc-date"'));
	if(can_write($phpcid)) {
		$date_tag->add(create_action_link_with_date('+',
					'event_form', $year, $month,
					$day, array('class="phpc-add"')));
	}
	$date_tag->add(create_action_link_with_date($day, 'display_day', $year,
				$month, $day));

	$html_day = tag('td', attributes('valign="top"',
				"class=\"phpc-$current_era\""), $date_tag);

	$stamp = mktime(0, 0, 0, $month, $day, $year);

	$can_read = can_read($phpcid); 
	$key = date('Y-m-d', $stamp);
	if(!$can_read || !array_key_exists($key, $days_events))
		return $html_day;

	$results = $days_events[$key];
	if(empty($results))
		return $html_day;

	$html_events = tag('ul');
	$html_day->add($html_events);

	// Count the number of events
	$count = 0;
	foreach($results as $event) {
		if($count == get_config($phpcid, 'events_max', 8)) {
			$event_html = tag('li',
					create_action_link_with_date(_("View Additional Events"),
						'display_day', $year, $month,
						$day,
						array('class="phpc-date"')));
			$html_events->add($event_html);
			break;
		}

		$count++;
		// TODO - make sure we have permission to read the event

		$subject = $event->get_subject();
		$event_time = $event->get_time_string();
		if(!empty($event_time))
			$title = "$event_time - $subject";
		else
			$title = $subject;

		$style = "";
		if(!empty($event->text_color))
			$style .= "color: ".$event->get_text_color().";";
		if(!empty($event->bg_color))
			$style .= "background-color: ".$event->get_bg_color()
				.";";

		$event_html = tag('li', 
				create_occurrence_link($title, "display_event",
					$event->get_oid(),
					array("style=\"$style\"")));

		$html_events->add($event_html);
	}

	return $html_day;
}

?>
