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

   THIS PAGE ADDED BY NATHAN CHARLES POIRO
   -IMPLEMENTS A SEARCH FUNCTION FOR PHP CALENDAR

 */

#############################################################
#
# -=[ MySQL Search Class ]=-
#
#      version 1.3
#
# (c) 2002 Stephen Bartholomew
#
# Functionality to search through a MySQL database, across
# all columns, for multiple keywords
#
# Usage:
#
#    Required:
#        $mysearch = new MysqlSearch;
#        $mysearch->setidentifier("MyPrimaryKey");
#        $mysearch->settable("MyTable");
#        $results_array = $mysearch->find($mysearchterms);
#
#    Optional:
#        This will force the columns that are searched
#        $mysearch->setsearchcolumns("Name, Description");
#
#             Set the ORDER BY attribute for SQL query
#            $mysearch->setorderby("Name"); 
#
##############################################################

class MysqlSearch
{
	function find($keywords)
	{
# Create a keywords array
		$keywords_array = explode(" ",$keywords);

# Select data query
		if(!$this->searchcolumns)
		{
			$this->searchcolumns = "*";
		}

		$search_data_sql = "SELECT ".$this->entry_identifier.",".$this->searchcolumns." FROM ".$this->table;
# Run query, assigning ref
		$search_data_ref = mysql_query($search_data_sql);

# Define $search_results_array, ready for population
# with refined results
		$search_results_array = array();
		if($search_data_ref)
		{
			while($all_data_array = mysql_fetch_array($search_data_ref))
			{
# Get an entry indentifier
				$my_ident = $all_data_array[$this->entry_identifier];

# Cycle each value in the product entry
				foreach($all_data_array as $entry_key=>$entry_value)
				{
# Cycle each keyword in the keywords_array
					foreach($keywords_array as $keyword)
					{
# If the keyword exists...
						if($keyword)
						{
# Check if the entry_value contains the keyword
							if(stristr($entry_value,$keyword))
							{
# If it does, increment the keywords_found_[keyword] array value
# This array can also be used for relevence results
								$keywords_found_array[$keyword]++;
							}
						}
						else
						{
# This is a fix for when a user enters a keyword with a space
# after it.  The trailing space will cause a NULL value to
# be entered into the array and will not be found.  If there
# is a NULL value, we increment the keywords_found value anyway.
							$keywords_found_array[$keyword]++;
						}
						unset($keyword);
					}

# Now we compare the value of $keywords_found against
# the number of elements in the keywords array.
# If the values do not match, then the entry does not
# contain all keywords so do not show it.
					if(sizeof($keywords_found_array) == sizeof($keywords_array))
					{
# If the entry contains the keywords, push the identifier onto an
# results array, then break out of the loop.  We're not searching for relevence,
# only the existence of the keywords, therefore we no longer need to continue searching
						array_push($search_results_array,"$my_ident");
						break;
					}
				}
				unset($keywords_found_array);
				unset($entry_key);
				unset($entry_value);
			}
		}

		$this->numresults = sizeof($search_results_array);
# Return the results array
		return $search_results_array;
	}

	function setidentifier($entry_identifier)
	{
# Set the db entry identifier
# This is the column that the user wants returned in
# their results array.  Generally this should be the
# primary key of the table.
		$this->entry_identifier = $entry_identifier;
	}

	function settable($table)
	{
# Set which table we are searching
		$this->table = $table;
	}

	function setsearchcolumns($columns)
	{
		$this->searchcolumns = $columns;
	}
}

function search()
{
	return search_results() . search_form();
}

function search_results()
{
	global $HTTP_POST_VARS, $calno, $database, $HTTP_GET_VARS, $day, $month,
	$year;

	$tablename = date('Fy', mktime(0, 0, 0, $month, 1, $year));
	$monthname = month_name($month);

	if(isset($HTTP_POST_VARS['submit']) && $HTTP_POST_VARS['submit'] == 'Submit'){
		$searchstring = $HTTP_POST_VARS['searchstring'];
		$fromday = $HTTP_POST_VARS['fromday'];
		$frommonth = $HTTP_POST_VARS['frommonth'];
		$fromyear = $HTTP_POST_VARS['fromyear'];
		$today = $HTTP_POST_VARS['today'];
		$tomonth = $HTTP_POST_VARS['tomonth'];
		$toyear = $HTTP_POST_VARS['toyear'];
		$sort = $HTTP_POST_VARS['sort'];
		$order = $HTTP_POST_VARS['order'];
		connect_to_database();

		$eventsearch = new MysqlSearch;
		$eventsearch->setidentifier('id');
		$eventsearch->settable( SQL_PREFIX . 'events');
		$eventsearch->setsearchcolumns('subject, description');
		$searchresults = $eventsearch->find($searchstring);

		$where = join($searchresults,"' OR id = '");
		$where = " WHERE (id = '".$where."')";
		$sqlquery = 'SELECT UNIX_TIMESTAMP(stamp) as start_since_epoch,
		UNIX_TIMESTAMP(duration) as end_since_epoch, username, subject,
		description, eventtype, id FROM '.SQL_PREFIX."events " .
			$where . ' AND calno = ' . $calno . 
			" AND stamp >= \"$fromyear-$frommonth-$fromday 00:00:01\"
			AND stamp <= \"$toyear-$tomonth-$today 23:59:59\" ";
		$sqlquery.= "ORDER BY $sort $order";

		$rsEvents = mysql_query($sqlquery) or die(mysql_error());
		$totalRows_rsEvents = mysql_num_rows($rsEvents);

		$output = "<a class=\"box\" href=\"index.php?actionpage=search&month=$month&year=$year&day=$day\">" . _('New Search') . 
			'</a><table id="display"><caption>Search Results</caption>';

		while ($row = mysql_fetch_array($rsEvents)) {
			$i++;
			$name = stripslashes($row['username']);
			$subject = stripslashes($row['subject']);
			$desc = nl2br(stripslashes($row['description']));
			$desc = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]",
					"<a href=\"\\0\">\\0</a>", $desc);
			$temp_time = $row['start_since_epoch'];

			$typeofevent = $row['eventtype'];
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

			$output .= "<tr><td width=\"75%\" align=\"left\"><strong><font size=\"+1\">$subject</font></strong></td>
				<td>$time</td></tr>
				<tr><td colspan=\"2\" class=\"description\">$desc</td>
				</tr>\n";
		}

		if(empty($i)) {
			$output .= '  <tr>
				<td colspan="3"><h2>' . _('No events.') . "</h2></td>
				</tr>\n";
		}	
		return $output . "</table>
			</form>\n";
	}
}

function search_form()
{
	global $HTTP_GET_VARS, $HTTP_POST_VARS, $day, $month, $year;

	if(!isset($HTTP_POST_VARS['submit'])){

		$optdayfrom = "<select name=\"fromday\" size=\"1\">\n";
		$optdayto  = "<select name=\"today\" size=\"1\">\n";

		$lastday = date('t', mktime(0, 0, 0, $month, 1, $year));
		for ($i = 1; $i <= $lastday; $i++){
			if ($i == $day) {
				$optday .= "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
			} else {
				$optday .= "        <option value=\"$i\">$i</option>\n";
			}
		}

		$optmonthfrom = "      </select>\n      <select size=\"1\" name=\"frommonth\">\n";
		$optmonthto = "    </select>\n     <select size=\"1\" name=\"tomonth\">\n";
		for ($i = 1; $i <= 12; $i++) {
			$nm = month_name($i);
			if ($i == $month) {
				$optmonth .= "        <option value=\"$i\" selected=\"selected\">$nm</option>\n";
			} else {
				$optmonth .= "        <option value=\"$i\">$nm</option>\n";
			}
		}

		$optyearfrom = "      </select>\n      <select size=\"1\" name=\"fromyear\">";
		$optyearto = "      </select>\n      <select size=\"1\" name=\"toyear\">";

		for ($i=$year-2; $i<$year+5; $i++) {
			if ($i == $year) {
				$optyear .= "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
			} else {
				$optyear .= "        <option value=\"$i\">$i</option>\n";
			}
		}
		$optyear .= "   </select>\n";

		$output = '<form action="'.$HTTP_SERVER_VARS['PHP_SELF'].'" method="post">' . 
			'<center><table class="box"><tr><td align="right">Phrase: </td>' .
			'<td align="left"><input type="text" name="searchstring" size="32" id="searchstring" value=""/></td></tr>' .
			'<tr><td align="right">From: </td><td>' . 
			"$optdayfrom$optday $optmonthfrom$optmonth $optyearfrom$optyear</td></tr>" . 
			'<tr><td align="right">To: </td><td>' . 
			"$optdayto$optday $optmonthto$optmonth $optyearto$optyear</td></tr>".
			"<tr><td align=\"right\">Sort By: </td><td><select name=\"sort\"><option value=\"stamp\">Date</option><option value=\"subject\">Subject</option></select></td></tr>" .
			"<tr><td align=\"right\">Order: <td><select name=\"order\"><option value=\"\">Ascending</option><option value=\"DESC\">Decending</option></select></td></tr>" .
			'<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="Submit"/></td></tr></table>' . 
			'</center></form>';

		$output .= '<p>&nbsp;</p></center></td></tr></table>';
	}
	return $output;
}
?>
