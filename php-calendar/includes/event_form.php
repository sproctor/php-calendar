<?php
/*
   Copyright 2002 - 2005 Sean Proctor, Nathan Poiro

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

function event_form()
{
	global $vars, $day, $month, $year, $db, $config, $phpc_script,
               $month_names, $event_types;

	if(isset($vars['id'])) {
		// modifying
		$id = $vars['id'];

		$heading = sprintf(_('Editing Event #%d'), $id);

		$row = get_event_by_id($id);

		$title = $row['title'];
		$desc = htmlspecialchars($row['description']);

		$year = $row['year'];
		$month = $row['month'];
		$day = $row['day'];

                $hour = date('H', strtotime($row['starttime']));
		$minute = date('i', strtotime($row['starttime']));

		$end_year = $row['end_year'];
		$end_month = $row['end_month'];
		$end_day = $row['end_day'];

		$durmin = $row['duration'] % 60;
		$durhr  = floor($row['duration'] / 60);

		if(!$config['hours_24']) {
			if($hour > 12) {
				$pm = true;
				$hour = $hour - 12;
			} elseif($hour == 12) {
                                $pm = true;
                        } else {
                                $pm = false;
                        }
                }

		$typeofevent = $row['eventtype'];

	} else {
		// case "add":
		$heading = _('Adding event to calendar');

		$subject = '';
                $desc = '';
                if($day == date('j') && $month == date('n')
                                && $year == date('Y')) {
                        if($config['hours_24']) {
                                $hour = date('G');
                        } else {
                                $hour = date('g');
                                if(date('a') == 'pm') {
                                        $pm = true;
                                } else {
                                        $pm = false;
                                }
                        }
                } else {
                        $hour = 6;
                        $pm = true;
                }

                $minute = 0;
		$end_day = $day;
		$end_month = $month;
		$end_year = $year;
		$durhr = 1;
		$durmin = 0;
		$typeofevent = 1;
	}

        if($config['hours_24']) {
                $hour_sequence = create_sequence(0, 23);
        } else {
                $hour_sequence = create_sequence(1, 12);
        }
        $minute_sequence = create_sequence(0, 59, 5, 'minute_pad');
        $year_sequence = create_sequence(1970, 2037);

	$html_time = array(create_select('hour', $hour_sequence, $hour,
                                attributes('class="phpc-time"')),
                        tag('b', ':'),
                        create_select('minute', $minute_sequence, $minute,
                                attributes('class="phpc-time"')));

	if(!$config['hours_24']) {
		if($pm) {
                        $value = 1;
                } else {
                        $value = 0;
                }
                $html_time[] = create_select('pm', array(_('AM'), _('PM')),
                                $value, attributes('class="phpc-time"'));
	}

        $hidden_fields = array();

        $hidden_fields[] = create_hidden('action', 'event_submit');
	if(isset($id)) $hidden_fields[] = create_hidden('id', $id);

        $day_of_month_sequence = get_day_of_month_sequence($month, $year);

        $general_info = tag('div', attributes('class="phpc-general-info"'),
                        tag('h3', _('General Information')),
                        tag('h4', _('Title') . sprintf
                                (' ('._('%d character maximum').'): ',
                                 $config['subject_max'])),
                        tag('input', attributes('type="text"',
                                        "maxlength=\"$config[subject_max]\"",
                                        "size=\"$config[subject_max]\"",
                                        'name="title"',
                                        "value=\"$title\"")),
                        tag('h4', _('Description')),
                        tag('div',
                                attributes('style="border: 1px solid black"'),
                                tag('textarea', attributes(
                                                'rows="8"',
                                                'cols="80"',
                                                'class="phpc-description"',
                                                'name="description"'),
                                                $desc)));

        $time_info = tag('div', attributes('class="phpc-time-info"'),
                        tag('h3',  _('Time Information')),
                        tag('h4', _('Event type')),
                        create_select('type', $event_types,
                                $typeofevent),
                        tag('h4', _('Start')),
                        $html_time,
                        tag('h4', _('Duration')),
                        create_select('durationhour', create_sequence(0, 23),
                                $durhr, attributes('class="phpc-time"')),
                        _('hour(s)')."\n",
                        create_select('durationmin', $minute_sequence, $durmin,
                                attributes('class="phpc-time"')),
                        _('minutes')."\n");

        $date_info = tag('div', attributes('class="phpc-date-info"'),
                        tag('h3', _('Date Information')),
                        tag('h4', _('Date of event')),
                        tag('div',
                                create_select('day', $day_of_month_sequence,
                                        $day),
                                create_select('month', $month_names, $month),
                                create_select('year', $year_sequence, $year)),
                        tag('h4', _('Date multiple day event ends')),
                        tag('div',
                                create_select('endday', $day_of_month_sequence,
                                        $end_day),
                                create_select('endmonth', $month_names,
                                        $end_month),
                                create_select('endyear', $year_sequence,
                                        $end_year)));

	return tag('form', attributes("action=\"$phpc_script\""),
                        tag('h2', $heading),
                        tag('div', attributes('class="phpc-misc-info"'),
                                create_submit(_("Submit Event"))),
                        $general_info,
                        $time_info,
                        $date_info,
                        tag('div', attributes('class="phpc-misc-info"'),
                                $hidden_fields,
                                create_submit(_("Submit Event"))));
}

?>
