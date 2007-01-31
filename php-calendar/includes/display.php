<?php
/*
   Copyright 2005 Sean Proctor

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

/*
   This file has the functions for the main displays of the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// picks which view to show based on what data is given
// returns the appropriate view
function display(&$calendar)
{
        $vars = $calendar->get_vars();

	if(isset($vars['eventid'])) return display_event($calendar);
	if(isset($vars['day'])) return display_day($calendar);
	if(isset($vars['month'])) return display_month($calendar);
	if(isset($vars['year'])) soft_error('year view not yet implemented');
	return display_month($calendar);
}

// creates a menu to navigate the month/year
// returns XHTML data for the menu
function month_navbar(&$calendar)
{
        $year = $calendar->get_year();
        $month = $calendar->get_month();

	$html = tag('div', attributes('class="phpc-navbar"'));
	$html->add($calendar->create_date_link(_('last year'), 'display',
                                $year - 1, $month), "\n");
	$html->add($calendar->create_date_link(_('last month'), 'display',
                                $year, $month - 1), "\n");

	for($i = 1; $i <= 12; $i++) {
		$html->add($calendar->create_date_link(short_month_name($i),
                                        'display', $year, $i), "\n");
	}
	$html->add($calendar->create_date_link(_('next month'), 'display',
                                $year, $month + 1), "\n");
	$html->add($calendar->create_date_link(_('next year'), 'display',
                                $year + 1, $month), "\n");

	return $html;
}

// creates a tables of the days in the month
// returns XHTML data for the month
function display_month(&$calendar)
{
	$days = tag('tr');
	for($i = 0; $i < 7; $i++) {
		$days->add(tag('th', day_name($calendar->get_config(
                                                        'start_monday')
                                                ? $i + 1 : $i)));
	}

	return tag('div',
                        month_navbar($calendar),
                        tag('table', attributes('class="phpc-main"',
                                        'id="php-calendar"'),
                                tag('caption',
                                        month_name($calendar->get_month())
                                        .' ' . $calendar->get_year()),
                                tag('colgroup', attributes('span="7"', 'width="1*"')),
                                tag('thead', $days),
                                tag('tbody', create_month($calendar))));
}

// creates a display for a particular week and the rest of the weeks until the
// end of the month
// returns HTML data for the weeks
function create_month(&$calendar, $week_of_month = 1)
{
        $month = $calendar->get_month();
        $year = $calendar->get_year();

	if($week_of_month > weeks_in_month($month, $year)) return array();

	$html_weeks = tag('tr', display_days($calendar, $week_of_month));

        return array_merge(array($html_weeks), create_month($calendar,
                                $week_of_month + 1));
}

// displays the day of the week and the following days of the week
// return HTML data for the days
function display_days(&$calendar, $week_of_month, $day_of_week = 1)
{
        $month = $calendar->get_month();
        $year = $calendar->get_year();

	if($day_of_week > 7) return array();

	$day_of_month = ($week_of_month - 1) * 7 + $day_of_week
		- day_of_first($month, $year);

	if($day_of_month <= 0 || $day_of_month > days_in_month($month, $year)) {
		$html_day = tag('td', attributes('class="none"'));
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

                //if(can_add_event()) {
		        $html_day = tag('td', attributes('valign="top"',
                                                "class=\"phpc-$current_era\""),
                                        $calendar->create_date_link('+',
                                                'event_form', $year, $month,
                                                $day_of_month,
                                                array('class="phpc-add"')),
                                        $calendar->create_date_link(
                                                $day_of_month, 'display', $year,
                                                $month, $day_of_month,
                                                array('class="date"')));
                /*} else {
		        $html_day = tag('td', attributes('valign="top"',
                                                "class=\"$current_era\""),
                                        create_date_link($day_of_month,
                                                'display', $year, $month,
                                                $day_of_month,
                                                array('class="date"')));
                }*/

		$db = phpc_get_db();
		$user = phpc_get_user();

		$result = $db->get_events_by_date($day_of_month, $month,
                                $year, $calendar->get_config('id'), $user->id);

		/* loop through each event for the day */
                $have_events = false;
		$html_events = tag('ul');
		while($row = $result->FetchRow($result)) {
			$subject = stripslashes($row['title']);

			$event_time = formatted_time_string(
					$row['starttime'],
					$row['timetype']);

			$event = tag('li',
					$calendar->create_event_link(
						($event_time ? "$event_time - " 
						 : '') . $subject, 'display',
						$row['eventid']));
                        $html_events->add($event);
                        $have_events = true;
		}
		if($have_events) $html_day->add($html_events);
	}

	return array_merge(array($html_day), display_days($calendar,
                                $week_of_month, $day_of_week + 1));
}

// returns a string representation of $duration for $typeofevent
function get_duration($duration, $typeofevent)
{
	$dur_mins = $duration % 60;
	$dur_hrs  = floor($duration / 60);

	$dur_str = '';

	if($typeofevent == 2) $dur_str = _("FULL DAY");
	else {
		$comma = 0;
		if(!empty($dur_hrs)) {
			$comma = 1;
			$dur_str .= "$dur_hrs "._('hour(s)');
		}
		if($dur_mins) {
			if($comma) $dur_str .= ', ';
			$dur_str .= "$dur_mins "._('minutes');
		}
	}

	if(empty($dur_str)) $dur_str = _('No duration');

	return $dur_str;
}

// displays a single day in a verbose way to be shown singly
// returns the HTML data for the day
function display_day(&$calendar)
{
        $day = $calendar->get_day();
        $month = $calendar->get_month();
        $year = $calendar->get_year();

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	$db = phpc_get_db();
	$user = phpc_get_user();

	$result = $db->get_events_by_date($day, $month, $year,
			$calendar->get_config('id'), $user->id);

	$today_epoch = mktime(0, 0, 0, $month, $day, $year);

	if($row = $result->FetchRow()) {

		$html_table = tag('table', attributes('class="phpc-main"'),
				tag('caption', "$day $monthname $year"),
				tag('thead',
					tag('tr',
						tag('th', _('Title')),
						tag('th', _('Time')),
						tag('th', _('Duration')),
						tag('th', _('Description'))
					     )));
		if(true) {
			$html_table->add(tag('tfoot',
                                                tag('tr',
                                                        tag('td',
                                                                attributes('colspan="4"'),
                                                                create_hidden('action', 'event_delete'),
                                                                create_hidden('day', $day),
                                                                create_hidden('month', $month),
                                                                create_hidden('year', $year),
                                                                create_submit(_('Delete Selected'))))));
                }

		$html_body = tag('tbody');

		for(; $row; $row = $result->FetchRow()) {
			//$name = stripslashes($row['username']);
			$subject = stripslashes($row['title']);
			if(empty($subject)) $subject = _('(No title)');
			$desc = parse_desc($row['description']);
			$time_str = formatted_time_string($row['starttime'],
					$row['eventtype']);
			$dur_str = get_duration($row['duration'],
					$row['eventtype']);

			$html_subject = tag('td',
                                        attributes('class="phpc-list"'));

			if(true) {
                                $html_subject->add(create_checkbox
						("delete{$row['eventid']}",
						 'y'));
                        }

			$html_subject->add($calendar->create_event_link(tag(
                                                        'strong', $subject),
                                                'display', $row['eventid']));

			if(true) {
				$html_subject->add(' (');
				$html_subject->add($calendar->create_event_link(
						_('Modify'), 'event_form',
						$row['eventid']));
				$html_subject->add(')');
			}

			$html_body->add(tag('tr',
                                        $html_subject,
                                        tag('td',
                                                attributes('class="phpc-list"'),
                                                $time_str),
                                        tag('td',
                                                attributes('class="phpc-list"'),
                                                $dur_str),
                                        tag('td',
                                                attributes('class="phpc-list"'),
                                                $desc)));
		}

		$html_table->add($html_body);

		if(true) $output = tag('form',
			attributes("action=\"{$calendar->script}\""),
                        $html_table);
		else $output = $html_table;

	} else {
		$output = tag('h2', _('No events on this day.'));
	}

	return $output;
}

// displays a particular event to be show singly
// returns HTML data for the event
function display_event(&$calendar)
{
	$row = $calendar->get_current_event();

	$year = $row['year'];
	$month = $row['month'];
	$day = $row['day'];

	$time_str = formatted_time_string($row['starttime'], $row['eventtype'])
		.' '.$row['startdate'];
	$dur_str = get_duration($row['duration'], $row['eventtype']);
	$subject = stripslashes($row['title']);
	if(empty($subject)) $subject = _('(No title)');
	$name = stripslashes($row['username']);
	$desc = parse_desc($row['description']);
	$id = $row["eventid"];

        if(true) {
                return tag('div', attributes('class="phpc-main"'),
				tag('h2', $subject),
				tag('div', 'by ', tag('cite', $name)),
				tag('div', $calendar->create_event_link(
						_('Modify'), 'event_form', $id),
					"\n", $calendar->create_link(
						_('Delete'),
						array('action'=>'event_delete',
							"delete$id"=>'y'))),
				tag('div', tag('div', _('Time').": $time_str"),
					tag('div', _('Duration').": $dur_str")),
				tag('p', $desc));
        } else {
                return tag('div', attributes('class="phpc-main"'),
                                tag('h2', $subject),
                                tag('div', 'by ', tag('cite', $name)),
                                tag('div', tag('div', _('Time').": $time_str"),
                                        tag('div', _('Duration').": $dur_str")),
                                tag('p', $desc));
        }
}

?>
