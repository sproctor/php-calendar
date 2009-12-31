<?php
/*
   Copyright 2002, 2005 Sean Proctor, Nathan Poiro

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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_submit($calendar)
{
	$potential_fields = array(
			"subject",
			"description",
			"eventid",
			"time_type",
			"event_type",
			"calendarid",
			);

	$arguments = array();
	foreach($potential_fields as $field) {
		if(isset($calendar->vars[$field])) {
			$arguments[$field] = $calendar->vars[$field];
		}
	}

	$time_fields = array(
			"hour",
			"minute",
			"meridiem",
			);

	$have_start_date = array(
			"once",
			"multi",
			);
	$have_end_date = array(
			"multi",
			);

	$event_type = $calendar->vars["event_type"];

	switch($event_type) {
		case "once":
			$date = get_date($calendar, "once_date");
			add_start_date($arguments, $date);
			add_end_date($arguments, $date);
			break;
		default:
			soft_error(_("Invalid event type."));
	}

	echo "<pre>arguments:\n"; print_r($arguments); echo "</pre>";
	$id = $calendar->db->submit_event($calendar, $arguments);

	if($id !== false) {
		$calendar->redirect("action=display&eventid=$id");
	} else {
		return tag('div', attributes('class="phpc-error"'),
				_('Error submitting event.'));
	}
}

function get_date($calendar, $prefix)
{
	$date_fields = array(
			"year",
			"month",
			"day",
			);

	$date = array();
	foreach($date_fields as $field) {
		if(isset($calendar->vars["{$prefix}-{$field}"])) {
			$date[$field] = $calendar->vars["{$prefix}-{$field}"];
		} else {
			soft_error(_("Date ({$prefix}) field {$field} not set."));
		}
	}

	return $date;
}

function add_start_date(&$arguments, $date)
{
	$arguments["startyear"] = $date["year"];
	$arguments["startmonth"] = $date["month"];
	$arguments["startday"] = $date["day"];
}

function add_end_date(&$arguments, $date)
{
	$arguments["endyear"] = $date["year"];
	$arguments["endmonth"] = $date["month"];
	$arguments["endday"] = $date["day"];
}
?>
