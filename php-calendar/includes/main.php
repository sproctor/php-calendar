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
	global $BName, $day, $month, $year, $db, $config;

	$currentday = date('j');
	$currentmonth = date('n');
	$currentyear = date('Y');

	if(!$config['start_monday']) $firstday = date('w', mktime(0, 0, 0, $month, 1, $year));
	else $firstday = (date('w', mktime(0, 0, 0, $month, 1, $year)) + 6) % 7;
	$lastday = date('t', mktime(0, 0, 0, $month, 1, $year));

	$output = "<table class=\"phpc-main\" id=\"calendar\">\n"
		.'<caption>'.month_name($month)." $year</caption>\n"
		."<colgroup span=\"7\" width=\"1*\" />\n"
		."<thead>\n"
		."<tr>\n";

	if(!$config['start_monday']) $output .= "<th>" .  _('Sunday') . "</th>\n";

	$output .= '<th>' .  _('Monday') . "</th>\n"
		.'<th>' .  _('Tuesday') . "</th>\n"
		.'<th>' .  _('Wednesday') . "</th>\n"
		.'<th>' .  _('Thursday') . "</th>\n"
		.'<th>' .  _('Friday') . "</th>\n"
		.'<th>' .  _('Saturday') . "</th>\n";

	if($config['start_monday']) $output .= '<th>' .  _('Sunday') . "</th>\n";

	$output .= "</tr>\n"
		."</thead>\n"
		."<tbody>\n";

	// Loop to render the calendar
	//FIXME: this needs to be made much less messy
	for ($week_index = 0;; $week_index++) {
		$output .= "  <tr>\n";

		for ($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
			$i = $week_index * 7 + $day_of_week;
			$day = $i - $firstday + 1;

			if($i < $firstday || $day > $lastday) {
				$output .= "<td class=\"none\"></td>\n";
				continue;
			}

			// set whether the date is in the past or future/present
			if($currentyear > $year || $currentyear == $year
					&& ($currentmonth > $month || $currentmonth == $month 
						&& $currentday > $day)) {
				$current_era = 'past';
			} else {
				$current_era = 'future';
			}

			$output .= "<td valign=\"top\" class=\"$current_era\">\n"
				."<a href=\"index.php?action=display&amp;"
				."day=$day&amp;month=$month&amp;year=$year"
				."&amp;display=day\" class=\"date\">"
				."$day</a>\n";

			$result = get_events_by_date($day, $month, $year);

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
		}
		$output .= "</tr>\n";

		// If it's the last day, we're done
		if($day >= $lastday) {
			break;
		}
	}

	$output .= "</tbody>\n"
		."</table>\n";

	return $output;
}
?>
