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

function get_duration($duration)
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
	global $HTTP_GET_VARS;

	if(empty($HTTP_GET_VARS['id'])) return display_date();

	return display_id($HTTP_GET_VARS['id']);
}

function display_date()
{
	global $day, $month, $year, $user, $db_events;

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	if(empty($user) && ANON_PERMISSIONS < 2) $admin = 0;
	else $admin = 1;

	if($admin) $output .= "<form action=\"index.php\">";
	$output .= "<table class=\"phpc-main\">\n"
		."<caption>$day $monthname $year</caption>\n"
		."<thead>\n"
		."<tr>\n"
		.'<th>'._('Title')."</th>\n"
		.'<th>'._('Time')."</th>\n"
		.'<th>'._('Duration')."</th>\n"
		//.'<th>'._('Description')."</th>\n"
		."</tr>\n"
		."</thead>\n";
	if($admin) 
		$output .= "<tfoot>\n"
			."<tr>\n"
			."<td colspan=\"3\">\n"
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
			."</tfoot>\n";

	$output .= "<tbody>\n";

	$result = get_events_by_date($day, $month, $year);

	$today_epoch = mktime(0, 0, 0, $month, $day, $year);

	$i = 0;
	while($row = $db_events->sql_fetchrow($result)) {
		$i++;
		//$name = stripslashes($row['username']);
		$subject = stripslashes($row['subject']);
		$desc = nl2br(stripslashes($row['description']));
		$desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]",
				"<a href=\"\\0\">\\0</a>", $desc);

		$time_str = formatted_time_string($row['starttime'],
				$row['eventtype']);

		$dur_str = get_duration($row['duration']);
		$output .= "<tr>\n"
			."<th>\n";

		if($admin) $output .= "<input type=\"checkbox\" name=\"delete\""
			." value=\"$row[id]\" />\n";

		$output .= "<a href=\"index.php?action=display&amp;"
			."id=$row[id]\">$subject</a>\n";

		if($admin) $output .= " (<a href=\"index.php?action=modify&amp;"
				."id=$row[id]\">"._('Modify')."</a>)\n";

		$output .= "</th>\n"
			."<td>$time_str</td>\n"
			."<td>$dur_str</td>\n"
			//."<td>$desc</td>\n"
			."</tr>\n";
	}

	if($i == 0) {
		$output .= "<tr><td colspan=\"3\"><strong>"
			._('No events on this day.')."</strong></td></tr>\n";
	}

	$output .= "</tbody>
		</table>";
	if($admin) $output .= "</form>\n";

	return $output;
}

function display_id($id)
{
	global $user, $db_events;

	$result = get_event_by_id($id);

	if(!empty($user) || ANON_PERMISSIONS >= 2) $admin = 1;
	else $admin = 0;

	$row = $db_events->sql_fetchrow($result);

	$time_str = formatted_time_string($row['starttime'], $row['eventtype'])
		.' '.$row['startdate'];
	$dur_str = get_duration($row['duration']);
	$subject = stripslashes($row['subject']);
	$name = stripslashes($row['username']);
	$desc = stripslashes($row['description']);

	$output = "<h2>$subject</h2>\n"
		."<div>\n"
		."<a href=\"index.php?action=modify&amp;id=$id\">"._('Modify')
		."</a>\n"
		."<a href=\"index.php?action=delete&amp;id=$id\">"._('Delete')
		."</a>\n"
		."</div>\n"
		."<p>by $name</p>\n"
		."<pre>Time: $time_str\n"
		."Duration: $dur_str</pre>\n"
		."<p>$desc</p>";

	return $output;
}
?>
