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

function month_navbar($month, $year)
{
	global $SCRIPT_NAME;

	$html = array('div', attributes('class="phpc-navbar"'),
			array('a', attributes("href=\"$SCRIPT_NAME?month=$month&amp;year="
					. $year - 1 . '"'),
				_('last year')),
			array('a', attributes("href=\"$SCRIPT_NAME?month="
					. $month - 1 . "&amp;year=$year\""),
				_('last month')));
	for($i = 1; $i <= 12; $i++) {
		$html[] = array('a', attributes('class="phpc-month"',
					"href=\"$SCRIPT_NAME?month=$i&amp;year=$year\""),
				short_month_name($i));
	}
	$html[] = array('a', attributes("href=\"$SCRIPT_NAME?month=".$month + 1
				."&amp;year=$year\""), _('next month'));
	$html[] = array('a', attributes("href=\"$SCRIPT_NAME?month=$month&amp;year="
				.$year + 1 . '"'), _('next year'));

	return $html;
}

function display_month($month, $year)
{
	$days = array('tr');
	for($i = 0; $i < 7; $i++) {
		$days[] = array('th', day_name($i));
	}

	return array('table', attributes('class="phpc-main"',
				'id="calendar"'),
			array('caption', month_name($month)." $year"),
			array('colgroup', attributes('span="7"', 'width="1*"')),
			array('thead', $days),
			create_month($month, $year));
}

function create_month($month, $year)
{

	return array_cons('tbody', create_weeks(1, $month, $year));
}

function create_weeks($week_of_month, $month, $year)
{
	if($week_of_month > weeks_in_month($month, $year)) return array();

	return array_cons(array_cons('tr', display_days(1, $week_of_month,
					$month, $year)),
			create_weeks($week_of_month + 1, $month, $year));
}

function display_days($day_of_week, $week_of_month, $month, $year)
{
	global $db, $SCRIPT_NAME;

	if($day_of_week > 7) return array();

	$day_of_month = ($week_of_month - 1) * 7 + $day_of_week
		- day_of_first($month, $year);

	if($day_of_month <= 0 || $day_of_month > days_in_month($month, $year)) {
		$html_day = array('td', attributes('class="none"'));
	} else {
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

		$html_day = array('td', attributes('valign="top"',
					"class=\"$current_era\""),
				array('a',
					attributes("href=\"$SCRIPT_NAME?action=display&amp;day=$day_of_month&amp;month=$month&amp;year=$year&amp;display=day\"",
						'class="date"'),
					$day_of_month)

		$result = get_events_by_date($day_of_month, $month, $year);

		/* Start off knowing we don't need to close the event
		 *  list.  loop through each event for the day
		 */
		$html_events = array('ul');
		while($row = $db->sql_fetchrow($result)) {
			$subject = stripslashes($row['subject']);

			$event_time = formatted_time_string(
					$row['starttime'],
					$row['eventtype']);

			$html_events[] = array('li',
				array('a',
					attributes("href=\"$SCRIPT_NAME?action=display&amp;id=$row[id]\""),
				"$event_time - $subject");
		}
		if(sizeof($html_events) != 1) $html_day[] = $html_events;
	}

	return array_cons($html_day, display_days($day_of_week + 1,
				$week_of_month, $month, $year));
}

function get_duration($duration, $typeofevent)
{
	$dur_mins = $duration % 60;
	$dur_hrs  = $duration / 60;

	$dur_str = '';

	if($typeofevent == 2) $dur_str = _("FULL DAY");
	else {
		$comma = 0;
		if(!empty($dur_hrs)) {
			$comma = 1;
			$dur_str .= "$dur_hrs "._('hours');
		}
		if($dur_mins) {
			if($comma) $dur_str .= ', ';
			$dur_str .= "$dur_mins "._('minutes');
		}
	}

	if(empty($dur_str)) $dur_str = _('No duration');

	return $dur_str;
}

function display()
{
	global $vars, $day, $month, $year;

	if(empty($vars['display'])) {
		if(empty($vars['id'])) {
			return display_month($month, $year);
		}
		return display_id($vars['id']);
	}
	return display_day($day, $month, $year);
}

function display_day($day, $month, $year)
{
	global $user, $db, $config;

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	if(empty($user) && $config['anon_permission'] < 2) $admin = 0;
	else $admin = 1;

	$result = get_events_by_date($day, $month, $year);

	$today_epoch = mktime(0, 0, 0, $month, $day, $year);

	if($row = $db->sql_fetchrow($result)) {
		if($admin) $output = "<form action=\"index.php\">";
		else $output = '';

		$html_table = array('table', attributes('class="phpc-main"')
				array('caption', "$day $monthname $year"),
				array('thead',
					array('tr',
						array('th', _('Title')),
						array('th', _('Time')),
						array('th', _('Duration')),
						array('th', _('Description'))
					     )));
		if($admin) 
			$html_table[] = array('tfoot',
					array('tr',
						array('td',
							attributes('colspan="4"'),
							array('input',
								attributes('type="hidden"',
									'name="action"',
									'value="event_delete"')),
							array('input',
								attributes('type="hidden"',
								'name="day"',
								"value=\"$day\"")),
							array('input',
								attributes('type="hidden"',
									'name="month"',
									"value=\"$month\"")),
							array('input',
								attributes('type="hidden"',
									'name="year"',
									"value=\"$year\"")),
							array('input',
								attributes('type="submit"',
									'value="'
									._('Delete Selected').'"'))
								)));

		$ .= "<tbody>\n";

		for(; $row; $row = $db->sql_fetchrow($result)) {
			//$name = stripslashes($row['username']);
			$subject = stripslashes($row['subject']);
			$desc = parse_desc($row['description']);
			$time_str = formatted_time_string($row['starttime'],
					$row['eventtype']);
			$dur_str = get_duration($row['duration'],
					$row['eventtype']);
			$output .= "<tr>\n"
				."<td>\n";

			if($admin) $output .= "<input type=\"checkbox\""
				." name=\"id\" value=\"$row[id]\" />\n";

			$output .= "<a href=\"index.php?action=display&amp;id="
				."$row[id]\"><strong>$subject</strong></a>\n";

			if($admin) $output .= " (<a href=\"index.php?action="
				."event_form&amp;id=$row[id]\">"._('Modify')
					."</a>)\n";

			$output .= "</td>\n"
				."<td>$time_str</td>\n"
				."<td>$dur_str</td>\n"
				."<td>$desc</td>\n"
				."</tr>\n";
		}

		$output .= "</tbody>\n"
			."</table>\n";
		if($admin) $output .= "</form>\n";

	} else {
		$output = "<h2>"._('No events on this day.')."</h2>\n";
	}

	return $output;
}

function display_id($id)
{
	global $user, $db, $year, $month, $day, $config;

	$row = get_event_by_id($id);

	if(!empty($user) || $config['anon_permission'] >= 2) $admin = 1;
	else $admin = 0;

	$year = $row['year'];
	$month = $row['month'];
	$day = $row['day'];

	$time_str = formatted_time_string($row['starttime'], $row['eventtype'])
		.' '.$row['startdate'];
	$dur_str = get_duration($row['duration'], $row['eventtype']);
	$subject = stripslashes($row['subject']);
	$name = stripslashes($row['username']);
	$desc = parse_desc($row['description']);

	$output = "<div class=\"phpc-main\">\n"
		."<h2>$subject</h2>\n"
		."<div>by <cite>$name</cite></div>\n"
		."<div>\n"
		."<a href=\"index.php?action=event_form&amp;id=$id\">"
		._('Modify')."</a>\n"
		."<a href=\"index.php?action=event_delete&amp;id=$id\">"
		._('Delete')."</a>\n"
		."</div>\n"
		."<div>Time: $time_str<br />\n"
		."Duration: $dur_str</div>\n"
		."<p>$desc</p>\n"
		."</div>\n";

	return $output;
}
?>
