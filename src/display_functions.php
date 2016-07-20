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
 * @param \DateTimeImmutable $from
 * @param \DateTimeImmutable $to
 * @return Occurrence[][]
 */
function get_occurrences_by_day(Calendar $calendar, User $user, \DateTimeInterface $from, \DateTimeInterface $to) {
	//echo "<pre>$from_stamp $to_stamp\n";
	$all_occurrences = $calendar->get_occurrences_by_date_range($from, $to);
	$occurrences_by_day = array();
	//var_dump($results);
	foreach ($all_occurrences as $occurrence) {
		//var_dump($row);
		//echo "here\n";
		if(!$occurrence->can_read($user))
			continue;

		$end = $occurrence->getEnd();

		$start = $occurrence->getStart();

		// if the event started before the range we're showing
		$diff = $from->diff($start);
		if($diff < 0)
			$diff = new \DateInterval("P0D");

		// put the event in every day until the end
		for($date = $start->add($diff); $date < $to && $date < $end; $date = $date->add(new \DateInterval("P1D"))) {
			$key = index_of_date($date);
			if(!isset($occurrences_by_day[$key]))
				$days_events[$key] = array();
			if(sizeof($occurrences_by_day[$key]) == $calendar->events_max)
				$days_events[$key][] = null;
			if(sizeof($occurrences_by_day[$key]) > $calendar->events_max)
				continue;
			$days_events[$key][] = $occurrence;
		}
	}
	return $occurrences_by_day;
}

/**
 * creates a display for a particular week to be embedded in a month table
 * @param Context $context
 * @param int $from_stamp
 * @param array $days_events
 * @return string
 */
function create_week(Context $context, $from_stamp, $days_events) {
	$start_day = date("j", $from_stamp);
	$start_month = date("n", $from_stamp);
	$start_year = date("Y", $from_stamp);
	$week_start = $context->getCalendar()->week_start;
	list($week_number, $week_year) = week_of_year($start_month, $start_day, $start_year, $week_start);

	$week_html = tag('tr', tag('th',
				new AttributeList('class="phpc-date ui-state-default"'),
				create_action_link($context, new ActionItem($week_number,
					'display_week',
					array('week' => $week_number, 'year' => $week_year)))));
		
	for($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
		$day = $start_day + $day_of_week;
		$week_html->add(create_day($context, $start_month, $day, $start_year, $days_events));
	}

	return $week_html->toString();
}

/**
 * displays the day of the week and the following days of the week
 * @param Context $context
 * @param int $month
 * @param int $day
 * @param int $year
 * @param Occurrence[][] $days_events
 * @return Html
 */
function create_day(Context $context, $month, $day, $year, $days_events)
{
	$date_classes = 'phpc-date ui-state-default';
	normalize_date($month, $day, $year);

	// TODO: I hate this next section. Find a way to change it
	if($context->getAction() == "display_month" && $month != $context->getMonth()) {
		$date_classes .= ' phpc-shadow';
	}

	if(is_today($month, $day, $year)) {
		$date_classes .= ' ui-state-highlight';
	}

	$date_tag = tag('div', new AttributeList("class=\"$date_classes\""),
			create_action_link_with_date($context, new ActionItem($day, 'display_day'), $year, $month, $day));

	if($context->getCalendar()->can_write($context->getUser())) {
		$date_tag->add(create_action_link_with_date($context, new ActionItem('+', 'event_form', null,
			new AttributeList('class="phpc-add"')), $year, $month, $day));
	}

	$html_day = tag('td', $date_tag);

	$can_read = $context->getCalendar()->can_read($context->getUser());
	$key = index_of_date($month, $day, $year);
	if(!$can_read || empty($days_events[$key]))
		return $html_day;

	$html_events = tag('ul', new AttributeList('class="phpc-event-list"'));
	$html_day->add($html_events);

	// Count the number of events
	foreach($days_events[$key] as $event) {
		if($event == null) {
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
