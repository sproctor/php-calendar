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

function event_form($action)
{
	global $BName, $vars, $day, $month, $year, $db, $config;

	$output = "<form action=\"index.php\">\n"
		.'<table class="phpc-main"';
	if($BName == 'MSIE') {
		$output .= ' cellspacing="0"';
	}

	$output .= ">\n"
		."<caption>\n";

	if($action == 'modify') {
		if(!isset($vars['id'])) {
			return '<h2>'._('Nothing to modify.').'</h2>';
		} else {
			$id = $vars['id'];
		}

		$output .= sprintf(_('Modifying id #%d'), $id);

		$row = get_event_by_id($id);

		$subject = stripslashes($row['subject']);
		$desc = htmlspecialchars(stripslashes($row['description']));

		$dateformat = '/(\d+)-(\d+)-(\d+)/';
		preg_match($dateformat, $row['startdate'], $matches);
		$year = $matches[1];
		$month = $matches[2];
		$day = $matches[3];

		preg_match($dateformat, $row['enddate'], $matches);
		$end_year = $matches[1];
		$end_month = $matches[2];
		$end_day = $matches[3];

		preg_match('/(\d+):(\d+):\d+/', $row['starttime'], $matches);
		$hour = $matches[1];
		$minute = $matches[2];

		$durmin = $row['duration'] % 60;
		$durhr  = $row['duration'] / 60;

		if(!$config['hours_24']) {
			if($hour >= 12) {
				$pm = 1;
				$hour = $hour - 12;
			} else $pm = 0;
		}

		$typeofevent = $row['eventtype'];

	} else {
		// case "add":
		$output .= _('Adding item to calendar');

		$subject = '';
		$desc = '';
		if($day == date('j') && $month == date('n')
				&& $year == date('Y')) {
			$hour = date('G') + 1;
			if(!$config['hours_24']) {
				if($hour >= 12) {
					$hour = $hour - 12;
					$pm = 1;
				} else $pm = 0;
			}
		} else { $hour = 6; $pm = 1; }
		$minute = 0;
		$end_day = $day;
		$end_month = $month;
		$end_year = $year;
		$durhr = 1;
		$durmin = 0;
		$typeofevent = 1;
	}

	$output .= "</caption>\n"
		."<tfoot>\n"
		."<tr>\n"
		."<td colspan=\"2\">\n";

	if($action == 'modify') {
		$output .= '<input type="hidden" name="modify" value="1" />'
			."\n<input type=\"hidden\" name=\"id\" value=\"$id\" />"
			."\n";
	}

	$output .= '<input type="submit" value="'._("Submit Item")."\" />\n"
		."<input type=\"hidden\" name=\"action\" value=\"submit\" />\n"
		."</td>\n"
		."</tr>\n"
		."</tfoot>\n"
		."<tbody>\n"
		."<tr><th>"._('Event Date')."</th>\n"
		."<td>"
		.create_select('day', 'day', $day)
		.create_select('month', 'month', $month)
		.create_select('year', 'year', $year)
		."</td>\n"
		."</tr>\n"
		."<tr><th>"
		._('End Date (only for daily, weekly, and monthly event types)')
		."</th>\n"
		."<td>\n"
		.create_select('endday', 'day', $end_day)
		.create_select('endmonth', 'month', $end_month)
		.create_select('endyear', 'year', $end_year)
		."</td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<th>' . _('Event Type') . "</th>\n"
		."<td>\n"
		.create_select('typeofevent', 'event', $typeofevent)
		."</td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<th>' .  _('Time') . "</th>\n"
		."<td>\n"
		.create_select('hour',
				$config['hours_24'] ? '24hour' : '12hour',
				$hour)
		."<b>:</b>\n";
		.create_select('minute', 'minute', $minute);

	if(!$config['hours_24']) {
		$output .= "<select name=\"pm\" size=\"1\">\n"
			.'<option value="0"';
		if(empty($pm)) {
			$output .= ' selected="selected"';
		}
		$output .= ">AM</option>\n"
			.'<option value="1"';
		if($pm) {
			$output .= ' selected="selected"';
		}
		$output .= ">PM</option>\n"
			."</select>\n";
	}

	$output .= "</td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<th>'._('Duration')."</th>\n"
		."<td>\n"
		.create_select('durationhour', '24hour', $durhr)
		._('hours')."\n"
		.create_select('durationmin', 'minute', $durmin)
		._('minutes')
		."\n</td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<th>'._('Subject').' '._('(255 chars max)')."</th>\n"
		."<td><input type=\"text\" name=\"subject\" value=\"$subject\" "
		."/></td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<th>' .  _('Description') . "</th>\n"
		."<td>\n"
		.'<textarea rows="5" cols="50" name="description">'
		."$desc</textarea>\n"
		."</td>\n"
		."</tr>\n"
		."</tbody>\n"
		."</table>\n"
		."</form>\n";
	return $output;
}
?>
