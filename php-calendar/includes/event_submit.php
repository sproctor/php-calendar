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
			"title",
			"description",
			"eventid",
			"timetype",
			"durationhours",
			"durationminutes",
			"daysbetween",
			"calendarid",
			);

	$arguments = array();
	foreach($potential_fields as $field) {
		if(isset($calendar->vars[$field])) {
			$arguments[$field] = $calendar->vars[$field];
		}
	}

	$event_type_fields = array(
			"year",
			"month",
			"day",
			"hour",
			"minute",
			"meridiem",
			);
	$eventtype = $calendar->vars["eventtype"];
	foreach($event_type_fiels as $field) {
		if(isset($calendar->vars["{$
	//echo "<pre>"; print_r($arguments); echo "</pre>";
	$id = $calendar->db->submit_event($arguments);

	if($id !== false) {
		$calendar->redirect("action=display&eventid=$id");
	} else {
		return tag('div', attributes('class="phpc-error"'),
				_('Error submitting event.'));
	}
}
?>
