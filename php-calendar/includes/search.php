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
	global $vars, $calendar_name, $day, $month, $year, $db;

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

	$keywords = explode(" ", $searchstring);
	$where = '';
	reset($keywords);
	while(list(,$keyword) = each($keywords)) {
		$where .= "subject LIKE '%$keyword%' "
			."OR description LIKE '%$keyword%'\n";
	}

	$query = 'SELECT * FROM '.SQL_PREFIX."events "
		."WHERE ($where) "
		."AND calendar = '$calendar_name' "
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
		$desc = parse_desc($desc);

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
		.create_select('fromday', 'day', $day)
		.create_select('frommonth', 'month', $month)
		.create_select('fromyear', 'year', $year)
		."</td></tr>\n" 
		."<tr><td>"._('To').": </td><td>\n"
		.create_select('today', 'day', $day)
		.create_select('tomonth', 'month', $month)
		.create_select('toyear', 'year', $year)
		."<tr><td>"._('Sort By').": </td>\n"
		."<td><select name=\"sort\">\n"
		."<option value=\"startdate\">"._('Start Date')."</option>\n"
		."<option value=\"subject\">"._('Subject')."</option>\n"
		."</select></td></tr>\n"
		."<tr><td>"._('Order').": </td>\n"
		."<td><select name=\"order\">"
		."<option value=\"\">"._('Ascending')."</option>\n"
		."<option value=\"DESC\">"._('Decending')."</option>\n"
		."</select></td></tr>\n"
		.'<tr><td colspan="2"><input type="submit" value="'._('Submit')
		.'" /></td></tr></table>'
		.'</form>';

	return $output;
}
?>
