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


function search()
{
	return search_results() . search_form();
}

function search_results()
{
	global $vars, $calno, $database, $day, $month, $year, $db_events;

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	if(isset($vars['submit'])) {
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
			."AND enddate >= '$fromyear-$frommonth-$fromday' "
			."AND startdate <= '$toyear-$tomonth-$today'"
			."ORDER BY $sort $order";

		$result = $db_events->sql_query($query)
			or soft_error($db_events->sql_error($result));

		$output = "<a href=\"index.php?action=search&amp;month=$month&amp;year=$year&amp;day=$day\">" . _('New Search') . 
			'</a><table class="phpc-main"><caption>Search Results</caption>';

		while ($row = $db_events->sql_fetchrow($result)) {
			$i++;
			$name = stripslashes($row['username']);
			$subject = stripslashes($row['subject']);
			$desc = nl2br(stripslashes($row['description']));
			$desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]",
					"<a href=\"\\0\">\\0</a>", $desc);

			$output .= "<tr><td><strong>$subject</strong></td>\n"
				."<td>$row[startdate] ".formatted_time_string($row['starttime'], $row['eventtype'])."</td>\n"
				."<td>$desc</td>
				</tr>\n";
		}

		if(empty($i)) {
			$output .= "<tr>\n"
				.'<td colspan="3"><strong>'._('No events.')
				."</strong></td>\n"
				."</tr>\n";
		}	
		return $output . "</table>
			</form>\n";
	}
}

function search_form()
{
	global $vars, $day, $month, $year;

	if(!isset($vars['submit'])){

		$optdayfrom = "<select name=\"fromday\" size=\"1\">\n";
		$optdayto  = "<select name=\"today\" size=\"1\">\n";

		for ($i = 1; $i <= 31; $i++){
			if ($i == $day) {
				$optday .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			} else {
				$optday .= "<option value=\"$i\">$i</option>\n";
			}
		}

		$optmonthfrom = "</select>\n<select size=\"1\" name=\"frommonth\">\n";
		$optmonthto = "</select>\n<select size=\"1\" name=\"tomonth\">\n";
		for ($i = 1; $i <= 12; $i++) {
			$nm = month_name($i);
			if ($i == $month) {
				$optmonth .= "<option value=\"$i\" selected=\"selected\">$nm</option>\n";
			} else {
				$optmonth .= "<option value=\"$i\">$nm</option>\n";
			}
		}

		$optyearfrom = "</select>\n<select size=\"1\" name=\"fromyear\">";
		$optyearto = "</select>\n<select size=\"1\" name=\"toyear\">";

		for ($i=$year-2; $i<$year+5; $i++) {
			if ($i == $year) {
				$optyear .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
			} else {
				$optyear .= "<option value=\"$i\">$i</option>\n";
			}
		}
		$optyear .= "   </select>\n";

		$output = "<form action=\"index.php\" method=\"post\">"
			."<table class=\"phpc-main\">\n"
			."<tr>\n"
			."<td>"._('Phrase').":</td>\n"
			."<td>\n"
			."<input type=\"text\" name=\"searchstring\""
			." size=\"32\" />\n"
			."<input type=\"hidden\" name=\"action\""
			." value=\"search\" />\n"
			."</td>\n"
			."</tr>\n"
			."<tr><td>"._('From').": </td><td>\n"
			."$optdayfrom$optday $optmonthfrom$optmonth $optyearfrom$optyear</td></tr>" 
			."<tr><td>"._('To').": </td><td>\n"
			."$optdayto$optday $optmonthto$optmonth $optyearto$optyear</td></tr>"
			."<tr><td>"._('Sort By').": </td>\n"
			."<td><select name=\"sort\"><option value=\"startdate\">Start Date</option><option value=\"subject\">Subject</option></select></td></tr>"
			."<tr><td>"._('Order').": </td><td><select name=\"order\"><option value=\"\">Ascending</option><option value=\"DESC\">Decending</option></select></td></tr>" .
			'<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="Submit"/></td></tr></table>' . 
			'</form>';

		$output .= '<p>&nbsp;</p></td></tr></table>';
	}
	return $output;
}
?>
