<?php
/*
   Copyright 2002 Sean Proctor, Nathan Poiro

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

function month_navbar()
{
	global $day, $month, $year;

	$nextmonth = $month + 1;
	$lastmonth = $month - 1;
	$nextyear = $year + 1;
	$lastyear = $year - 1;

	$output = "<div class=\"phpc-navbar\">\n"
		."<a href=\"?month=$month&amp;year=$lastyear\">"
		._('last year')."</a>\n"
		."<a href=\"?month=$lastmonth&amp;year=$year\">"
		._('last month')."</a>\n";
	for($i = 1; $i <= 12; $i++) {
		$output .= "<a class=\"phpc-month\" href=\"?month=$i&amp;"
			."year=$year\">".short_month_name($i)."</a>\n";
	}
	$output .= "<a href=\"?month=$nextmonth&amp;year=$year\">"
		._('next month')."</a>\n"
		."<a href=\"?month=$month&amp;year=$nextyear\">"
		._('next year')."</a>\n"
		."</div>\n";

	return $output;
}

function calendar()
{
	global $month, $year;

	$output = "<table class=\"phpc-main\" id=\"calendar\">\n"
		.'<caption>'.month_name($month)." $year</caption>\n"
		."<colgroup span=\"7\" width=\"1*\" />\n"
		."<thead>\n"
		."<tr>\n";

	for($i = 0; $i < 7; $i++) {
		$output .= '<th>' . day_name($i) . "</th>\n";
	}

	$output .= "</tr>\n"
		."</thead>\n"
		."<tbody>\n"
		.display_month($month, $year)
		."</tbody>\n"
		."</table>\n";

	return $output;
}

function display_month($month, $year)
{

	return display_weeks(1, $month, $year);
}

function display_weeks($week_of_month, $month, $year)
{
	$output = "<tr>\n".display_days(1, $week_of_month, $month, $year)
		."</tr>\n";
	if($week_of_month < weeks_in_month($month, $year)) {
		$output .= display_weeks($week_of_month + 1, $month, $year);
	}
	return $output;
}

function display_days($day_of_week, $week_of_month, $month, $year)
{
	global $db;

	if($day_of_week > 7) return '';

	$day_of_month = ($week_of_month - 1) * 7 + $day_of_week
		- day_of_first($month, $year);

	if($day_of_month <= 0 || $day_of_month > days_in_month($month, $year)) {
		return "<td class=\"none\"></td>\n"
		.display_days($day_of_week + 1, $week_of_month, $month, $year);
	}

	$currentday = date('j');
	$currentmonth = date('n');
	$currentyear = date('Y');

	// set whether the date is in the past or future/present
	if($currentyear > $year || $currentyear == $year
			&& ($currentmonth > $month
				|| $currentmonth == $month 
				&& $currentday > $day_of_month
			   )) {
		$current_era = 'past';
	} else {
		$current_era = 'future';
	}

	$output .= "<td valign=\"top\" class=\"$current_era\">\n"
		."<a href=\"index.php?action=display&amp;"
		."day=$day_of_month&amp;month=$month&amp;"
		."year=$year&amp;display=day\" class=\"date\">"
		."$day_of_month</a>\n";

	$result = get_events_by_date($day_of_month, $month, $year);

	/* Start off knowing we don't need to close the event
	 *  list.  loop through each event for the day
	 */
	$have_events = 0;
	while($row = $db->sql_fetchrow($result)) {
		// if we didn't start the event table yet, do so
		if($have_events == 0) {
			$output .= "<ul>\n";
			$have_events = 1;
		}

		$subject = stripslashes($row['subject']);

		$event_time = formatted_time_string(
				$row['starttime'],
				$row['eventtype']);

		$output .= "<li>\n"
			."<a href=\"index.php?action=display&amp;id=$row[id]\">$event_time - $subject</a>\n"
			."</li>";
	}

	// If we opened the event table, close it
	if($have_events == 1) {
		$output .= "</ul>\n";
	}

	$output .= "</td>\n";

	return $output . display_days($day_of_week + 1, $week_of_month, $month,
			$year);
}

?>
