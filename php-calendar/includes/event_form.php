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
	global $BName, $vars, $day, $month, $year, $db, $config;

	if(isset($vars['id'])) {
		// modifying
		$id = $vars['id'];

		$title = sprintf(_('Modifying id #%d'), $id);

		$row = get_event_by_id($id);

		$subject = stripslashes($row['subject']);
		$desc = htmlspecialchars(stripslashes($row['description']));

		$year = $row['year'];
		$month = $row['month'];
		$day = $row['day'];

		$end_year = $row['end_year'];
		$end_month = $row['end_month'];
		$end_day = $row['end_day'];

		if($config['hours_24']) {
                        $hour = $row['hour'];
		} else {
                        $hour = $row['hour12'];
                        if($row['ampm'] == 'PM') {
                                $pm = true;
                        } else {
                                $pm = false;
                        }
                }
		$minute = $row['minute'];

		$durmin = $row['duration'] % 60;
		$durhr  = $row['duration'] / 60;

		$typeofevent = $row['eventtype'];

	} else {
		// case "add":
		$title = _('Adding event to calendar');

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

	$html_time = tag('td',
			create_select('hour', $config['hours_24'] ?
				'24hour' : '12hour', $hour),
			tag('b', ':'),
			create_select('minute', 'minute', $minute));

	if(!$config['hours_24']) {
		$attributes_am = attributes('value="0"');
		$attributes_pm = attributes('value="1"');
		if($pm) {
			$attributes_pm[] = 'selected="selected"';
		} else {
			$attributes_am[] = 'selected="selected"';
                }
		$html_time[] = tag('select',
			attributes('name="pm"', 'size="1"'),
			tag('option', $attributes_am, 'AM'),
			tag('option', $attributes_pm, 'PM'));
	}

	if(isset($id)) $input = create_hidden('id', $id);
	else $input = '';

	$attributes = attributes('class="phpc-main"');
	if($BName == 'MSIE') $attributes[] = 'cellspacing="0"';

	return tag('form', attributes("action=\"$_SERVER[SCRIPT_NAME]\""),
			tag('table', $attributes,
				tag('caption', $title),
				tag('tfoot',
					tag('tr',
						tag('td', attributes( 'colspan="2"'),
							$input,
							create_submit(_("Submit Event")),
							create_hidden('action', 'event_submit')))),
				tag('tbody',
					tag('tr',
						tag('th', _('Date of event')),
						tag('td',
							create_select('day', 'day', $day),
							create_select('month', 'month', $month),
							create_select('year', 'year', $year))),
					tag('tr',
						tag('th', _('Date multiple day event ends')),
						tag('td',
							create_select('endday', 'day', $end_day),
							create_select('endmonth', 'month', $end_month),
							create_select('endyear', 'year', $end_year))),
					tag('tr',
						tag('th', _('Event type')),
						tag('td',
							create_select('typeofevent',
								'event', $typeofevent))),
					tag('tr',
						tag('th',  _('Time')),
						$html_time),
					tag('tr',
						tag('th', _('Duration')),
						tag('td',
							create_select('durationhour', '24hour', $durhr),
							_('hours') . "\n",
							create_select('durationmin', 'minute', $durmin),
							_('minutes') . "\n")),
					tag('tr',
						tag('th', _('Subject').' ('.$config['subject_max'].' '._('chars max').')'),
						tag('td', tag('input', attributes('type="text"',
									'name="subject"',
									"value=\"$subject\"")))),
					tag('tr',
						tag('th',  _('Description')),
						tag('td',
							tag('textarea', attributes('rows="5"',
									'cols="50"',
									'name="description"'),
								$desc))))));
}

?>
