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

require_once("$phpc_includes_path/display_functions.php");

// Full display for a month
function display_month()
{
	global $phpc_month, $phpc_year, $phpc_home_url, $phpcid;

	$months = array();
	for($i = 1; $i <= 12; $i++) {
		$m = month_name($i);
		$months["$phpc_home_url?action=display_month&amp;phpcid=$phpcid&amp;month=$i&amp;year=$phpc_year"] = $m;
	}
	$years = array();
	for($i = $phpc_year - 5; $i <= $phpc_year + 5; $i++) {
		$years["$phpc_home_url?action=display_month&amp;phpcid=$phpcid&amp;month=$phpc_month&amp;year=$i"] = $i;
	}
	$next_month = $phpc_month + 1;
	$next_year = $phpc_year;
	if($next_month > 12) {
		$next_month -= 12;
		$next_year++;
	}
	$prev_month = $phpc_month - 1;
	$prev_year = $phpc_year;
	if($prev_month < 1) {
		$prev_month += 12;
		$prev_year--;
	}

	$heading = tag('',
			tag('a', attrs('class="phpc-icon-link"',
					"href=\"$phpc_home_url?action=display_month&amp;phpcid=$phpcid&amp;month=$prev_month&amp;year=$prev_year\""),
				tag('span', attrs('class="fa fa-chevron-left"'), '')),
			create_dropdown_list(month_name($phpc_month), $months),
			create_dropdown_list($phpc_year, $years),
			tag('a', attrs('class="phpc-icon-link"',
					"href=\"$phpc_home_url?action=display_month&amp;phpcid=$phpcid&amp;month=$next_month&amp;year=$next_year\""),
				tag('span', attrs('class="fa fa-chevron-right"'), '')));
	return create_display_table($heading, create_month($phpc_month,
				$phpc_year));
}

// creates a display for a particular month to be embedded in a full view
function create_month($month, $year)
{
	global $phpcdb, $phpc_cal, $phpcid;

	$weeks = weeks_in_month($month, $year);

	$first_day = 1 - day_of_week($month, 1, $year);
	$from_stamp = mktime(0, 0, 0, $month, $first_day, $year);

	$last_day = $weeks * 7 - day_of_week($month, 1, $year);
	$to_stamp = mktime(0, 0, 0, $month, $last_day, $year);

	$days_events = get_events($from_stamp, $to_stamp);
	$week_list = array();
	for($week_of_month = 1; $week_of_month <= $weeks; $week_of_month++) {
		// We could be showing a week from the previous or next year
		$days = ($week_of_month - 1) * 7;
		$start_stamp = strtotime("+$days day", $from_stamp);
		$week_list[] = create_week($start_stamp, $year, $days_events);
	}

	return $week_list;
}
?>
