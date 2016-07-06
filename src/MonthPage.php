<?php
/*
 * Copyright 2016 Sean Proctor
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

require_once __DIR__ . '/display_functions.php';


class MonthPage extends Page
{
	// Full display for a month
	function display(Context $context)
	{
		$cid = $context->getCalendar()->cid;
		$month = $context->getMonth();
		$year = $context->getYear();

		$months = array();
		for($i = 1; $i <= 12; $i++) {
			$months["{$context->script}?action=display_month&amp;phpcid=$cid&amp;month=$i&amp;year=$year"] =
				month_name($i);
		}
		$years = array();
		for($i = $year - 5; $i <= $year + 5; $i++) {
			$years["{$context->script}?action=display_month&amp;phpcid=$cid&amp;month=$month&amp;year=$i"] = $i;
		}
		$next_month = $month + 1;
		$next_year = $year;
		if($next_month > 12) {
			$next_month -= 12;
			$next_year++;
		}
		$prev_month = $month - 1;
		$prev_year = $year;
		if($prev_month < 1) {
			$prev_month += 12;
			$prev_year--;
		}

		$heading = tag('',
				tag('a', new AttributeList('class="phpc-icon-link"',
						"href=\"{$context->script}?action=display_month&amp;phpcid=$cid&amp;month=$prev_month&amp;year=$prev_year\""),
					tag('span', new AttributeList('class="fa fa-chevron-left"'), '')),
				create_dropdown_list(month_name($month), $months),
				create_dropdown_list($year, $years),
				tag('a', new AttributeList('class="phpc-icon-link"',
						"href=\"{$context->script}?action=display_month&amp;phpcid=$cid&amp;month=$next_month&amp;year=$next_year\""),
					tag('span', new AttributeList('class="fa fa-chevron-right"'), '')));
		return create_display_table($context, $heading, create_month($context, $month, $year));
	}
}

// creates a display for a particular month to be embedded in a full view
function create_month(Context $context, $month, $year)
{
	$week_start = $context->getCalendar()->week_start;
	$weeks = weeks_in_month($month, $year, $week_start);

	$first_day = 1 - day_of_week($month, 1, $year, $week_start);
	$from_stamp = mktime(0, 0, 0, $month, $first_day, $year);

	$last_day = $weeks * 7 - day_of_week($month, 1, $year, $week_start);
	$to_stamp = mktime(23, 59, 59, $month, $last_day, $year);

	$days_events = get_events($context, $from_stamp, $to_stamp);
	$week_list = array();
	for($week_of_month = 1; $week_of_month <= $weeks; $week_of_month++) {
		// We could be showing a week from the previous or next year
		$days = ($week_of_month - 1) * 7;
		$start_stamp = strtotime("+$days day", $from_stamp);
		$week_list[] = create_week($context, $start_stamp, $year, $days_events);
	}

	return $week_list;
}
?>
