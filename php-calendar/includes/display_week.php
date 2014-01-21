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
function display_week()
{
	global $vars, $phpc_home_url, $phpcid;

	if(!isset($vars['week']) || !isset($vars['year']))
		soft_error(__('Invalid date.'));

	$week_of_year = $vars['week'];
	$year = $vars['year'];

	$day_of_year = 1 + ($week_of_year - 1) * 7 - day_of_week(1, 1, $year);
	$from_stamp = mktime(0, 0, 0, 1, $day_of_year, $year);
	$start_day = date("j", $from_stamp);
	$start_month = date("n", $from_stamp);
	$start_year = date("Y", $from_stamp);

	$last_day = $day_of_year + 6;
	$to_stamp = mktime(0, 0, 0, 1, $last_day, $year);
	$end_day = date("j", $to_stamp);
	$end_month = date("n", $to_stamp);
	$end_year = date("Y", $to_stamp);

	$title = month_name($start_month) .  " $start_year";
	if($end_month != $start_month)
		$title .= " - " . month_name($end_month) . " $end_year";

	$prev_week = $week_of_year - 1;
	$prev_year = $year;
	if($prev_week < 1) {
		$prev_year--;
		$prev_week = week_of_year($start_month, $start_day - 7,
				$start_year);
	}

	$next_week = $week_of_year + 1;
	$next_year = $year;
	if($next_week > weeks_in_year($year)) {
		$next_week = week_of_year($end_month, $end_day + 1, $end_year);
		$next_year++;
	}

	$heading = tag('',
			tag('a', attrs('class="phpc-icon-link"',
					"href=\"$phpc_home_url?action=display_week&amp;phpcid=$phpcid&amp;week=$prev_week&amp;year=$prev_year\""),
				tag('span', attrs('class="fa fa-chevron-left"'), '')),
			$title,
			tag('a', attrs('class="phpc-icon-link"',
					"href=\"$phpc_home_url?action=display_week&amp;phpcid=$phpcid&amp;week=$next_week&amp;year=$next_year\""),
				tag('span', attrs('class="fa fa-chevron-right"'), '')));

	return create_display_table($heading, create_week($from_stamp, $year,
				get_events($from_stamp, $to_stamp)));
}

?>
