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


function search_results()
{
	global $vars, $calno, $day, $month, $year, $db;

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	$searchstring = $vars['searchstring'];
	$fromday = $vars['fromday'];
	$frommonth = $vars['frommonth'];
	$fromyear = $vars['fromyear'];
	$today = $vars['today'];
	$tomonth = $vars['tomonth'];
	$toyear = $vars['toyear'];
	$sort = $vars['sort'];
	$order = $vars['order'];

	$keywords = explode(" ",$searchstring);
	$where = '';
	reset($keywords);
	while(list(,$keyword) = each($keywords)) {
		$where .= "subject LIKE '%$keyword%' "
			."OR description LIKE '%$keyword%'\n";
	}

	$query = 'SELECT * FROM '.SQL_PREFIX."events "
		."WHERE ($where) "
		."AND calno = '$calno' "
		."AND enddate >= DATE '$fromyear-$frommonth-$fromday' "
		."AND startdate <= DATE '$toyear-$tomonth-$today'"
		."ORDER BY $sort $order";

	$result = $db->sql_query($query);
	if(!$result) {
		$error = $db->sql_error();
		soft_error("$error[code]: $error[message]");
	}

	$output = "<table class=\"phpc-main\"><caption>Search Results</caption>\n";

	$i = 0;
	while ($row = $db->sql_fetchrow($result)) {
		$i++;
		$name = stripslashes($row['username']);
		$subject = stripslashes($row['subject']);
		$desc = nl2br(stripslashes($row['description']));
		$desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]",
				"<a href=\"\\0\">\\0</a>", $desc);

		$output .= "<tr><td><strong><a href=\"index.php?action=display"
			."&amp;id=$row[id]\">$subject</a></strong></td>\n"
			."<td>$row[startdate] "
			.formatted_time_string($row['starttime'],
					$row['eventtype'])."</td>\n"
			."<td>$desc</td>
			</tr>\n";
	}

	if(empty($i)) {
		$output .= "<tr>\n"
			.'<td colspan="3"><strong>'._('No events.')
			."</strong></td>\n"
			."</tr>\n";
	}	

	$output .= "</table>\n"
		."</form>\n";

	return $output;
}

function search_form()
{
	global $day, $month, $year;

	$day_options = '';
	for ($i = 1; $i <= 31; $i++){
		if ($i == $day) {
			$day_options .= "<option value=\"$i\" selected="
			."\"selected\">$i</option>\n";
		} else {
			$day_options .= "<option value=\"$i\">$i</option>\n";
		}
	}

	$month_options = '';
	for ($i = 1; $i <= 12; $i++) {
		$nm = month_name($i);
		if ($i == $month) {
			$month_options .= "<option value=\"$i\" selected=\"selected\">$nm</option>\n";
		} else {
			$month_options .= "<option value=\"$i\">$nm</option>\n";
		}
	}

	$year_options = '';
	for ($i=$year-2; $i<$year+5; $i++) {
		if ($i == $year) {
			$year_options .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		} else {
			$year_options .= "<option value=\"$i\">$i</option>\n";
		}
	}

	$output = "<form action=\"index.php\" method=\"post\">"
		."<table class=\"phpc-main\">\n"
		."<tr>\n"
		."<td>"._('Phrase').":</td>\n"
		."<td>\n"
		."<input type=\"text\" name=\"searchstring\""
		." size=\"32\" />\n"
		."<input type=\"hidden\" name=\"action\""
		." value=\"search_results\" />\n"
		."</td>\n"
		."</tr>\n"
		."<tr><td>"._('From').": </td><td>\n"
		."<select name=\"fromday\" size=\"1\">\n"
		.$day_options
		."</select>\n"
		."<select size=\"1\" name=\"frommonth\">\n"
		.$month_options
		."</select>\n"
		."<select size=\"1\" name=\"fromyear\">"
		.$year_options
		."</td></tr>\n" 
		."<tr><td>"._('To').": </td><td>\n"
		."<select name=\"today\" size=\"1\">\n"
		.$day_options
		."</select>\n"
		."<select size=\"1\" name=\"tomonth\">\n"
		.$month_options
		."</select>\n"
		."<select size=\"1\" name=\"toyear\">"
		.$year_options
		."</select>\n"
		."<tr><td>"._('Sort By').": </td>\n"
		."<td><select name=\"sort\"><option value=\"startdate\">Start Date</option><option value=\"subject\">Subject</option></select></td></tr>\n"
		."<tr><td>"._('Order').": </td><td><select name=\"order\"><option value=\"\">Ascending</option><option value=\"DESC\">Decending</option></select></td></tr>\n"
		.'<tr><td colspan="2"><input type="submit" value="'._('Submit').'" /></td></tr></table>'
		.'</form>';

	return $output;
}
?>
