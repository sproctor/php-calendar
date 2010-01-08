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
					'src="static/jquery-1.3.2.min.js"'), ''),
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
	menu_item_append_with_date($html, _('last year'), 'display_month',
			$year - 1, $month);
	menu_item_append_with_date($html, _('last month'), 'display_month',
			$year, $month - 1);

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
	menu_item_append_with_date($html, _('next month'), 'display_month',
			$year, $month + 1);
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
		$end_stamp = mktime(0, 0, 0, $event->get_endmonth(),
				$event->get_endday(), $event->get_endyear());

		// put the event in every day until the end
		for($add_days = 0; ; $add_days++) {
			$stamp = mktime(0, 0, 0, $event->get_month(),
					$event->get_day() + $add_days,
					$event->get_year());

			if($stamp > $end_stamp || $stamp > $to_stamp)
				break;

			if($stamp < $from_stamp)
				continue;

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
	$week_of_year = week_of_year($month, 1, $year) + $week_of_month - 1;

	$week_html = tag('tr', tag('th', $week_of_year));
	
	for($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
		$day = ($week_of_month - 1) * 7 - day_of_week($month, 1, $year)
			+ $day_of_week + 1;
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

	if(can_write($phpcid)) {
		$html_day = tag('td', attributes('valign="top"',
					"class=\"phpc-$current_era\""),
				create_action_link_with_date('+',
					'event_form', $year, $month,
					$day, array('class="phpc-add"')),
				create_action_link_with_date($day,
					'display_day', $year, $month, $day,
					array('class="phpc-date"')));
	} else {
		$html_day = tag('td', attributes('valign="top"',
					"class=\"phpc-$current_era\""),
				create_action_link_with_date($day,
					'display_day', $year, $month, $day,
					array('class="phpc-date"')));
	}

	$stamp = mktime(0, 0, 0, $month, $day, $year);
	$results = $days_events[date('Y-m-d', $stamp)];

	$can_read = can_read($phpcid);
	if(!$can_read || empty ($results))
		return $html_day;

	$html_events = tag('ul');
	$html_day->add($html_events);

	foreach($results as $event) {
		// TODO - make sure we have permission to read the event

		$subject = $event->get_subject();
		$event_time = $event->get_time_string();

		$event_html = tag('li',
				create_occurrence_link($event_time ?
					"$event_time - $subject" : $subject,
					"display_event", $event->get_oid()));
		$html_events->add($event_html);
	}

	return $html_day;
}

?>
