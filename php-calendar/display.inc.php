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

include('calendar.inc.php');

function display()
{
  global $sql_tableprefix, $HTTP_GET_VARS;

  if(!isset($HTTP_GET_VARS['day'])) $day = date('j');
  else $day = $HTTP_GET_VARS['day'];

  if(!isset($HTTP_GET_VARS['month'])) $month = date('n');
  else $month = $HTTP_GET_VARS['month'];

  if(!isset($HTTP_GET_VARS['year'])) $year = date('Y');
  else $year = $HTTP_GET_VARS['year'];

  $tablename = date('Fy', mktime(0,0,0,$month,1,$year));
  $monthname = month_name($month);

  $lasttime = mktime(0,0,0,$month,$day-1,$year);
  $lastday = date('j', $lasttime);
  $lastmonth = date('n', $lasttime);
  $lastyear = date('Y', $lasttime);
  $lastmonthname = month_name($lastmonth);

  $nexttime = mktime(0,0,0,$month,$day+1,$year);
  $nextday = date("j", $nexttime);
  $nextmonth = date("n", $nexttime);
  $nextyear = date("Y", $nexttime);
  $nextmonthname = month_name($nextmonth);

  $output = "<div id=\"navbar\">
      <a href=\"display.php?month=$lastmonth&amp;day=$lastday&amp;year=$lastyear\">$lastmonthname $lastday</a>
      <a href=\"display.php?month=$nextmonth&amp;day=$nextday&amp;year=$nextyear\">$nextmonthname $nextday</a>
</div>

<a class=\"box\" href=\"add.php?month=$month&amp;year=$year&amp;day=$day\">" .
  _("Add Item") . "</a>

<form action=\"delete.php\">
<table id=\"display\">
  <caption>$day $monthname $year</caption>
  <colgroup>
    <col width=\"48\" />
  </colgroup>
  <colgroup>
    <col width=\"96\" />
    <col width=\"160\" />
    <col width=\"160\" />
    <col width=\"128\" />
  </colgroup>
  <thead>
  <tr>
    <th>" . 
_("Select")
. "</th>
    <th>" . 
_("Modify")
. "</th>
    <th>" . 
_("Username")
    . "</th>
    <th>" . 
_("Time")
        . "</th>
    <th>" . 
_("Duration")
    . "</th>
    <th>" . 
_("Subject")
     . "</th>
    <th>" . 
_("Description")
 . "</th>
  </tr>
  </thead>
  <tfoot>
  <tr>
    <td colspan=\"7\">
      <input type=\"submit\" value=\"" . 
_("Delete Selected")
 . "\" />
    </td>
  </tr>
  </tfoot>
  <tbody>";

  $result = get_events_by_date($day, $month, $year);

  $i = 0;
  while ($row = mysql_fetch_array($result)) {
    $i++;
    $name = stripslashes($row['username']);
    $subject = stripslashes($row['subject']);
    $desc = nl2br(stripslashes($row['description']));
    $typeofevent = $row['eventtype'];
    $temp_time = $row['start_since_epoch'];
    switch($typeofevent) {
     case 1:
      if(empty($hours_24)) $timeformat = "j F Y, g:i A";
      else $timeformat = "j F Y, G:i";
      $time = date($timeformat, $temp_time);
      break;
     case 2:
      $time = date("j F Y, ", $temp_time) . _("FULL DAY");
      break;
     case 3:
      $time = date("j F Y, ", $temp_time) . _("??:??");
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

    $output .= "<tr>
    <td><input type=\"checkbox\" name=\"delete\" value=\"$row[id]\" /></td>
    <td><a href=\"modify.php?id=$row[id]\">"
    . _("Modify")
    . "</a></td>
    <td>$name</td>
    <td>$time</td>
    <td>$temp_dur</td>
    <td>$subject</td>
    <td class=\"description\">$desc</td>
  </tr>\n";
  }

  if(empty($i)) {
    $output .= "<tr><td colspan=\"7\"><h2>" . _("No events on this day.")
      . "</h2></td></tr>\n";
  }

  $output .= "</tbody>
</table>
<input type=\"hidden\" name=\"day\" value=\"$day\" />
<input type=\"hidden\" name=\"month\" value=\"$month\" />
<input type=\"hidden\" name=\"year\" value=\"$year\" />
</form>
<div>
  <a class=\"box\" href=\"index.php?month=$month&amp;day=$day&amp;year=$year\">
    " . 
_("Back to Calendar")
 . "
  </a>
</div>";
  return $output;
}
?>
