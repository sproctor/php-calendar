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

/**
 * @param Context $context
 * @param $from_stamp
 * @param $to_stamp
 * @return array
 */
function get_events(Context $context, $from_stamp, $to_stamp) {
	//echo "<pre>$from_stamp $to_stamp\n";
	$results = $context->db->get_occurrences_by_date_range($context->getCalendar()->cid, $from_stamp, $to_stamp);
	$days_events = array();
	//var_dump($results);
	foreach ($results as $event) {
		//var_dump($row);
		//echo "here\n";
		if(!$event->can_read($context->getUser()))
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
			if(sizeof($days_events[$key]) == $context->calendar->events_max)
				$days_events[$key][] = false;
			if(sizeof($days_events[$key]) > $context->calendar->events_max)
				continue;
			$days_events[$key][] = $event;
		}
	}
	return $days_events;
}

/**
 * creates a display for a particular week to be embedded in a month table
 * @param Context $context
 * @param $from_stamp
 * @param $year
 * @param $days_events
 * @return Html
 */
function create_week(Context $context, $from_stamp, $year, $days_events) {
	$start_day = date("j", $from_stamp);
	$start_month = date("n", $from_stamp);
	$start_year = date("Y", $from_stamp);
	$week_start = $context->getCalendar()->week_start;
	$week_of_year = week_of_year($start_month, $start_day, $start_year, $week_start);

	// Non ISO, the week should be of this year.
	if($week_start != 1) {
		if($start_year < $year) {
			$week_of_year = 1;
		}
	} else {
		// Use week's year as year for ISO
		$year = $start_year;
	}
	

	$week_html = tag('tr', tag('th',
				new AttributeList('class="phpc-date ui-state-default"'),
				create_action_link($context, new ActionItem($week_of_year,
					'display_week',
					array('week' => $week_of_year, 'year' => $year)))));
		
	for($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
		$day = $start_day + $day_of_week;
		$week_html->add(create_day($context, $start_month, $day, $start_year, $days_events));
	}

	return $week_html;
}

/**
 * displays the day of the week and the following days of the week
 * @param Context $context
 * @param $month
 * @param $day
 * @param $year
 * @param Event[] $days_events
 * @return Html
 */
function create_day(Context $context, $month, $day, $year, $days_events)
{
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

	if($context->getAction() == "display_month" && $month != $context->getMonth()) {
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

	$date_tag = tag('div', new AttributeList("class=\"phpc-date $date_class\""),
			create_action_link_with_date($context, new ActionItem($day, 'display_day'), $year, $month, $day));

	if($context->getCalendar()->can_write($context->getUser())) {
		$date_tag->add(create_action_link_with_date($context, new ActionItem('+', 'event_form', null,
			new AttributeList('class="phpc-add"')), $year, $month, $day));
	}

	$html_day = tag('td', $date_tag);

	$stamp = mktime(0, 0, 0, $month, $day, $year);

	$can_read = $context->getCalendar()->can_read($context->getUser());
	$key = date('Y-m-d', $stamp);
	if(!$can_read || !array_key_exists($key, $days_events))
		return $html_day;

	if(empty($days_events[$key]))
		return $html_day;

	$html_events = tag('ul', new AttributeList('class="phpc-event-list"'));
	$html_day->add($html_events);

	// Count the number of events
	foreach($days_events[$key] as $event) {
		if($event == false) {
			$event_html = tag('li',
					create_action_link_with_date(__("View Additional Events"),
						new ActionItem('display_day', $year, $month, new AttributeList('class="phpc-date"')),
						$day));
			$html_events->add($event_html);
			break;
		}

		// TODO - make sure we have permission to read the event

		$subject = $event->get_subject();
		if($event->get_start_timestamp() >= $stamp) {
			$event_time = $event->get_time_string();
			if(!empty($event_time))
				$event_time = tag('span', new AttributeList('class="phpc-event-time ui-corner-all"'), $event_time);
		} else {
			$event_time = tag('span', new AttributeList('class="fa fa-share"'), '');
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
					new AttributeList("style=\"$style\"")));

		$html_events->add($event_html);
	}

	return $html_day;
}

function create_display_table(Context $context, $heading, $contents)
{
	$heading_html = tag('tr', new AttributeList('class="ui-widget-header"'));
	$heading_html->add(tag('th', __p('Week', 'W')));
	for($i = 0; $i < 7; $i++) {
		$d = ($i + $context->getCalendar()->week_start) % 7;
		$heading_html->add(tag('th', day_name($d)));
	}

	return tag('div',
			tag("div", new AttributeList('id="phpc-summary-view"'),
				tag("div", new AttributeList('id="phpc-summary-head"'),
					tag("div", new AttributeList('id="phpc-summary-title"'), ''),
					tag("div", new AttributeList('id="phpc-summary-author"'), ''),
					tag("div", new AttributeList('id="phpc-summary-category"'), ''),
					tag("div", new AttributeList('id="phpc-summary-time"'), '')),
				tag("div", new AttributeList('id="phpc-summary-body"'), '')),
			tag('div', new AttributeList('class="phpc-sub-title phpc-month-title ui-widget-content"'), $heading),
                        tag('table', new AttributeList('class="phpc-month-view"'),
                                tag('colgroup',
					tag('col', new AttributeList('class="phpc-week"')),
					tag('col', new AttributeList('class="phpc-day"')),
					tag('col', new AttributeList('class="phpc-day"')),
					tag('col', new AttributeList('class="phpc-day"')),
					tag('col', new AttributeList('class="phpc-day"')),
					tag('col', new AttributeList('class="phpc-day"')),
					tag('col', new AttributeList('class="phpc-day"')),
					tag('col', new AttributeList('class="phpc-day"'))
				   ),
				tag('thead', $heading_html),
                                tag('tbody', $contents)));
}
?>
