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

UPDATE:  Nate - 11/07/02 - Constrict what information is displayed based on
whether user is an administrator
- Changed the table layout of the tables a little
UPDATE:  Nate - 12/03/02 - Added Functionality.  Script will now display either
a whole day or a single event depending on wether 
event_id has been passed in the query string

 */

function display()
{
	global $HTTP_GET_VARS, $day, $month, $year, $user;

	/* FIXME: this function needs a big rewrite. showing the items in
	a table is wicked lame. do something better */

	//Nate added this code to get just one event by ID
	$eventid = $HTTP_GET_VARS['event_id']; 

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	// -Nate- Output an alternate display if not administrative user
	if(empty($user) && ANON_PERMISSIONS < 2) {
		$admin = 0;
		$num_cols = 3;
		$output .= "<table class=\"phpc-main\">\n"
			."<caption>$day $monthname $year</caption>\n"
			."<colgroup>\n"
			."<col width=\"96\" />\n"
			."<col width=\"50%\" />\n"
			."</colgroup>\n"
			."<thead>\n"
			."<tr>\n"
			.'<th>'._('Author')."</th>\n"
			.'<th>'._('Time')."</th>\n"
			.'<th>'._('Duration')."</th>\n"
			."</tr>\n"
			."</thead>\n"
			."<tbody>\n";
	}else{ //This is the ouput for administrators
		$num_cols = 5;
		$admin = 1;
		$output .= "<form action=\"index.php\">"
			."<table class=\"phpc-main\">\n"
			."<caption>$day $monthname $year</caption>\n"
			."<colgroup>\n"
			."<col width=\"48\" />\n"
			."<col width=\"96\" />\n"
			."<col width=\"160\" />\n"
			."<col width=\"160\" />\n"
			."<col width=\"128\" />\n"
			."</colgroup>\n"
			."<thead>\n"
			."<tr>\n"
			.'<th>'._('Select')."</th>\n"
			.'<th>'._('Modify')."</th>\n"
			.'<th>'._('Username')."</th>\n"
			.'<th>'._('Time')."</th>\n"
			.'<th>'._('Duration')."</th>\n"
			."</tr>\n"
			."</thead>\n"
			."<tfoot>\n"
			."<tr>\n"
			."<td colspan=\"$num_cols\">\n"
			."<input type=\"hidden\" name=\"action\""
			." value=\"delete\" />\n"
			."<input type=\"hidden\" name=\"day\" value=\"$day\""
			." />\n"
			."<input type=\"hidden\" name=\"month\""
			." value=\"$month\" />\n"
			."<input type=\"hidden\" name=\"year\" value=\"$year\""
			." />\n"
			.'<input type="submit" value="'._('Delete Selected')
			."\" />\n"
			."</td>\n"
			."</tr>\n"
			."</tfoot>\n"
			."<tbody>\n";
	}

	// Nate - determine if the whole day or just a single event should
	// be displayed
	if(!empty($eventid)) $result = get_event_by_id($eventid);
	else $result = get_events_by_date($day, $month, $year);

	$i = 0;
	while ($row = mysql_fetch_array($result)) {
		$i++;
		$name = stripslashes($row['username']);
		$subject = stripslashes($row['subject']);
		$desc = nl2br(stripslashes($row['description']));
		$desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]",
				"<a href=\"\\0\">\\0</a>", $desc);
		$typeofevent = $row['eventtype'];
		$temp_time = $row['start_since_epoch'];
		switch($typeofevent) {
			case 1:
				if(empty($hours_24)) $timeformat = 'j F Y, g:i A';
				else $timeformat = 'j F Y, G:i';
				$time = date($timeformat, $temp_time);
				break;
			case 2:
				$time = date('j F Y, ', $temp_time) . _('FULL DAY');
				break;
			case 3:
				$time = date('j F Y, ', $temp_time) . _('??:??');
				break;
			default:
				$time = "????: $typeofevent";
		}

		$durtime = $row['end_since_epoch'] - $temp_time;
		$durmin = ($durtime / 60) % 60;     //minute per 60 seconds, 60 per hour
		$durhr  = ($durtime / 3600) % 24;   //hour per 3600 seconds, 24 per day
		$durday = floor($durtime / 86400);  //day per 86400 seconds

		if($typeofevent == 2) $temp_dur = _("FULL DAY");
		else $temp_dur = "$durday days, $durhr hours, $durmin minutes";

		$output .= "<tr>\n";
		if($admin) {
			$output .= "<td><input type=\"checkbox\""
				." name=\"delete\" value=\"".$eventid."\""
				." /></td>\n"
				."<td><a href=\"index.php?action=modify"
				."&amp;id=$eventid\">"._('Modify')
				."</a></td>\n";
		}

		$num_body_cols = $num_cols - 1;
		$output .= "<td>$name</td>\n"
			."<td>$time</td>\n"
			."<td>$temp_dur</td>\n"
			."</tr>\n<tr>\n"
			.'<th>'._('Subject')."</th>\n"
			."<td colspan=\"$num_body_cols\"><strong>$subject"
			."</strong></td></tr>\n"
			."<tr>\n"
			.'<th>'._('Description')."</th>\n"
			."<td colspan=\"$num_body_cols\" class=\"description\">"
			."$desc</td></tr>\n";
	}

	if($i == 0) {
		$output .= "<tr><td colspan=\"$num_cols\"><strong>"
			._('No events on this day.')."</strong></td></tr>\n";
	}

	return $output . "</tbody>
		</table>";
		if($admin) $output .= "</form>\n";
		$output .= "<div><a class=\"box\" href=\"index.php?month=$month"
			."&amp;day=$day&amp;year=$year\">"._('Back to Calendar')
			."</a></div>\n";
}
?>
