<?php
/*
   Copyright 2002 Sean Proctor

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
	global $BName, $HTTP_GET_VARS, $day, $month, $year;

	$output = "<form action=\"index.php\">\n"
		.'<table class="phpc-main"';
	if($BName == 'MSIE') {
		$output .= ' cellspacing="0"';
	}

	$output .= ">\n"
		."<caption>\n";

	if($action == 'modify') {
		if (!isset($HTTP_GET_VARS['id'])) {
			return '<h2>'._('Nothing to modify.').'</h2>';
		} else {
			$id = $HTTP_GET_VARS['id'];
		}

		$output .= sprintf(_('Modifying id #%d'), $id);

		$result = get_event_by_id($id);

		$row = mysql_fetch_array($result);
		$username = stripslashes($row['username']);
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

		if(!HOURS_24) {
			if($hour >= 12) {
				$pm = 1;
				$hour = $hour - 12;
			} else $pm = 0;
		}

		$typeofevent = $row['eventtype'];

	} else {
		// case "add":
		$output .= _('Adding item to calendar');

		$username = '';
		$subject = '';
		$desc = '';
		if($day == date('j') && $month == date('n')
				&& $year == date('Y')) {
			$hour = date('G') + 1;
			if(!HOURS_24) {
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
		."<tr>\n"
		.'<td>'._('Name')."</td>\n"
		.'<td><input type="text" name="username" size="20" value="'
		.$username."\" /></td>\n"
		."</tr>\n"
		."<tr><td>"._('Event Date')."</td>\n"
		."<td>\n"
		."<select name=\"day\" size=\"1\">\n";

	for ($i = 1; $i <= 31; $i++){
		if ($i == $day) {
			$output .= "        <option value=\"$i\" "
				."selected=\"selected\">$i</option>\n";
		} else {
			$output .= "        <option value=\"$i\">$i</option>\n";
		}
	}

	$output .= "      </select>\n"
		."      <select size=\"1\" name=\"month\">\n";

	for ($i = 1; $i <= 12; $i++) {
		$nm = month_name($i);
		if ($i == $month) {
			$output .= "        <option value=\"$i\" "
				."selected=\"selected\">$nm</option>\n";
		} else {
			$output .= "        <option value=\"$i\">$nm</option>"
				."\n";
		}
	}

	$output .= "      </select>\n      <select size=\"1\" name=\"year\">";

	for ($i = $year - 2; $i < $year + 5; $i++) {
		if ($i == $year) {
			$output .= "        <option value=\"$i\" "
				."selected=\"selected\">$i</option>\n";
		} else {
			$output .= "        <option value=\"$i\">$i</option>\n";
		}
	}

	$output .= "      </select></td>\n"
		."</tr>\n"
		."<tr><td>"
		._('End Date (only for daily, weekly, and monthly event types)')
		."</td>\n"
		."<td>\n"
		."<select name=\"endday\" size=\"1\">\n";

	for ($i = 1; $i <= 31; $i++){
		if ($i == $end_day) {
			$output .= "        <option value=\"$i\" "
				."selected=\"selected\">$i</option>\n";
		} else {
			$output .= "        <option value=\"$i\">$i</option>\n";
		}
	}

	$output .= "      </select>\n"
		."      <select size=\"1\" name=\"endmonth\">\n";

	for ($i = 1; $i <= 12; $i++) {
		$nm = month_name($i);
		if ($i == $end_month) {
			$output .= "        <option value=\"$i\" "
				."selected=\"selected\">$nm</option>\n";
		} else {
			$output .= "        <option value=\"$i\">$nm</option>"
				."\n";
		}
	}

	$output .= "      </select>\n"
		."      <select size=\"1\" name=\"endyear\">";

	for ($i = $year - 2; $i < $year + 5; $i++) {
		if ($i == $end_year) {
			$output .= "        <option value=\"$i\" "
				."selected=\"selected\">$i</option>\n";
		} else {
			$output .= "        <option value=\"$i\">$i</option>\n";
		}
	}

	$output .= "      </select></td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<td>' . _('Event Type') . "</td>\n"
		."<td>\n"
		."<select name=\"typeofevent\" size=\"1\">\n";

	for($i = 1; $i <= 6; $i++) {
		$output .= "<option value=\"$i\"";

		if($typeofevent == $i) {
			$output .= ' selected="selected"';
		}

		$output .= '>'.event_type($i).' '._('Event')."</option>\n";
	}

	$output .= "</select>\n"
		."</td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<td>' .  _('Time') . "</td>\n"
		."<td>\n"
		."<select name=\"hour\" size=\"1\">\n";

	if(!HOURS_24) {
		for($i = 1; $i <= 12; $i++) {
			$output .= '<option value="' . $i % 12 . '"';
			if($hour == $i) {
				$output .= ' selected="selected"';
			}
			$output .= ">$i</option>\n";
		}
	} else {
		for($i = 0; $i < 24; $i++) {
			$output .= "<option value=\"$i\"";
			if($hour == $i) {
				$output .= ' selected="selected"';
			}
			$output .= '>' . $i . "</option>\n";
		}
	}

	$output .= "</select><b>:</b><select name=\"minute\" size=\"1\">\n";

	for($i = 0; $i <= 59; $i = $i + 5) {
		$output .= "<option value='$i'";
		if($minute >= $i && $i > $minute - 5) {
			$output .= ' selected="selected"';
		}
		$output .= sprintf(">%02d</option>\n", $i);
	}

	$output .= "</select>\n";

	if(!HOURS_24) {
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
		.'<td>'._('Duration')."</td>\n"
		."<td>\n"
		."\n<select name=\"durationhour\" size=\"1\">\n";
	for($i = 0; $i < 24; $i++) {
		$output .= "<option value='$i'";
		if($durhr == $i) {
			$output .= ' selected="selected"';
		}
		$output .= ">$i</option>\n";
	}
	$output .= "</select>\n"
		._('hours')
		."\n<select name=\"durationmin\" size=\"1\">\n";
	for($i = 0; $i <= 59; $i = $i + 5) {
		$output .= "<option value='$i'";
		if($durmin >= $i && $i > $durmin - 5) {
			$output .= ' selected="selected"';
		}
		$output .= sprintf(">%02d</option>\n", $i);
	}
	$output .= "</select>\n"
		._('minutes')
		."\n</td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<td>'._('Subject').' '._('(255 chars max)')."</td>\n"
		."<td><input type=\"text\" name=\"subject\" value=\"$subject\" "
		."/></td>\n"
		."</tr>\n"
		."<tr>\n"
		.'<td>' .  _('Description') . "</td>\n"
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
