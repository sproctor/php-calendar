<?php
/*
 * Copyright 2010 Sean Proctor
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

// View for a single day
function display_day()
{
	global $phpcid, $phpc_script, $phpcdb, $day, $month, $year;

	$monthname = month_name($month);

	$results = $phpcdb->get_occurrences_by_date($phpcid, $year, $month,
			$day);

	$today_epoch = mktime(0, 0, 0, $month, $day, $year);

	$have_events = false;

	$html_table = tag('table', attributes('class="phpc-main"'),
			tag('caption', "$day $monthname $year"),
			tag('thead',
				tag('tr',
					tag('th', _('Title')),
					tag('th', _('Time')),
					tag('th', _('Description'))
				   )));
	if(can_modify($phpcid)) {
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

	foreach($results as $event) {
		if(!can_read_event($event))
			continue;

		$have_events = true;

		$eid = $event->get_eid();
		$oid = $event->get_oid();

		$html_subject = tag('td');

		if(can_modify_event($event)) {
			$html_subject->add(create_checkbox('eid[]',
						$eid));
		}

		$html_subject->add(create_occurrence_link(tag('strong',
						$event->get_subject()),
					'display_event', $oid));

		if(can_modify_event($event)) {
			$html_subject->add(" (");
			$html_subject->add(create_event_link(
						_('Modify'), 'event_form',
						$eid));
			$html_subject->add(')');
		}

		$html_body->add(tag('tr',
					$html_subject,
					tag('td', $event->get_time_span_string()),
					tag('td', $event->get_desc())));
	}

	$html_table->add($html_body);

	if(can_modify($phpcid)) {
		$output = tag('form',
				attributes("action=\"$phpc_script\""),
				$html_table);
	} else {
		$output = $html_table;
	}

	if(!$have_events)
		$output = tag('h2', _('No events on this day.'));

	return $output;
}

?>
