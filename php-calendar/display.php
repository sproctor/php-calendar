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

include_once("calendar.inc");
include_once("config.inc");

top();

if(!isset($_GET['day'])) $day = date("j");
else $day = $_GET['day'];

if(!isset($_GET['month'])) $month = date("n");
else $month = $_GET['month'];

if(!isset($_GET['year'])) $year = date("Y");
else $year = $_GET['year'];

$tablename = date('Fy', mktime(0,0,0,$month,1,$year));
$monthname = month_name($month);

$lasttime = mktime(0,0,0,$month,$day-1,$year);
$lastday = date("j", $lasttime);
$lastmonth = date("n", $lasttime);
$lastyear = date("Y", $lasttime);
$lastmonthname = month_name($lastmonth);

$nexttime = mktime(0,0,0,$month,$day+1,$year);
$nextday = date("j", $nexttime);
$nextmonth = date("n", $nexttime);
$nextyear = date("Y", $nexttime);
$nextmonthname = month_name($nextmonth);

echo <<<END
<table id="navbar">
  <thead>
  <tr>
    <th colspan="3">$day $monthname $year</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td>
      <a href="display.php?month=$lastmonth&amp;day=$lastday&amp;year=$lastyear">$lastmonthname $lastday</a>
    </td>
    <td>
      <a href="operate.php?month=$month&amp;year=$year&amp;day=$day&amp;action=add">
END;
echo _("Add Item");
echo <<<END
</a>
    </td>
    <td>
      <a href="display.php?month=$nextmonth&amp;day=$nextday&amp;year=$nextyear">$nextmonthname $nextday</a>
    </td>
  </tr>
  </tbody>
</table>
<form action="operate.php">
<table id="display">
  <colgroup>
    <col width="48" />
  </colgroup>
  <colgroup>
    <col width="96" />
    <col width="160" />
    <col width="160" />
    <col width="128" />
  </colgroup>
  <thead>
  <tr>
END;
echo "
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
      <input type=\"hidden\" name=\"action\" value=\"delete\" />
      <input type=\"submit\" value=\"" . 
_("Delete Selected")
 . "\" />
    </td>
  </tr>
  </tfoot>
  <tbody>";

$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password)
     or die("could not connect to database");
mysql_select_db($mysql_database)
     or die("could not select database");

$query = "SELECT * FROM $mysql_tablename WHERE stamp >= \"$year-$month-$day 00:00:00\" AND stamp <= \"$year-$month-$day 23:59:59\" ORDER BY stamp";
$result = mysql_query($query)
     or die("could not run query");

while ($row = mysql_fetch_array($result)) {
    $name = stripslashes($row['username']);
    $subject = stripslashes($row['subject']);
    $desc = nl2br(stripslashes($row['description']));
    $typeofevent = $row['eventtype'];
    $temp_time = strtotime($row['stamp']);
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

    $durtime = strtotime($row['duration']) - $temp_time;
    $durmin = ($durtime / 60) % 60;     //seconds per minute
    $durhr  = ($durtime / 3600) % 24;   //seconds per hour
    $durday = floor($durtime / 86400);  //seconds per day

    if($typeofevent == 2) $temp_dur = _("FULL DAY");
    else $temp_dur = "$durday days, $durhr hours, $durmin minutes";

  echo <<<END
  <tr>
    <td><input type="checkbox" name="delete" value="$row[id]" /></td>
    <td><a href="operate.php?action=modify&amp;id=$row[id]">
END;
  echo _("Modify");
  echo <<<END
</a></td>
    <td>$name</td>
    <td>$time</td>
    <td>$temp_dur</td>
    <td>$subject</td>
    <td class="description">$desc</td>
  </tr>
END;
  }

echo "
  </tbody>
</table>
<div>
  <a class=\"box\" href=\"index.php?month=$month&amp;day=$day&amp;year=$year\">
    " . 
_("Back to Calendar")
 . "
  </a>
</div>
</form>";

bottom();
?>
