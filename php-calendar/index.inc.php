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

function navbar($year, $month, $day)
{
  global $BName;

  $nextmonth = $month + 1;
  $lastmonth = $month - 1;
  $nextyear = $year + 1;
  $lastyear = $year - 1;

  $output = "<div id=\"navbar\">
  <a href=\"?month=$month&amp;year=$lastyear\">" .  _('last year') . "</a>
  <a href=\"?month=$lastmonth&amp;year=$year\">" . _('last month') . '</a>
  ';
  for($i = 1; $i <= 12; $i++) {
    $output .= "<a class=\"month\" href=\"?month=$i&amp;year=$year\">"
      . short_month_name($i) . "</a>\n  ";
  }
  $output .= "<a href=\"?month=$nextmonth&amp;year=$year\">" .  _('next month')
    . "</a>
  <a href=\"?month=$month&amp;year=$nextyear\">" . _('next year') . "</a>
</div>
<div>
  <a class=\"box\" href=\"add.php?month=$month&amp;year=$year&amp;day=$day\">" .
_('Add Item') . '</a>
</div>';
  return $output;
}

function calendar($year, $month, $day)
{
  global $BName;

  $database = connect_to_database();
  $currentday = date('j');
  $currentmonth = date('n');
  $currentyear = date('Y');

  if(!START_MONDAY) $firstday = date('w', mktime(0, 0, 0, $month, 1, $year));
  else $firstday = (date('w', mktime(0, 0, 0, $month, 1, $year)) + 6) % 7;
  $lastday = date('t', mktime(0, 0, 0, $month, 1, $year));

  $output = '<table id="calendar">
  <caption>' . month_name($month) . " $year</caption>
  <colgroup span=\"7\" width=\"1*\" />
  <thead>
  <tr>\n";

  if(!START_MONDAY) $output .= "    <th>" .  _('Sunday') . "</th>\n";
  
  $output .= '    <th>' .  _('Monday') . '</th>
    <th>' .  _('Tuesday') . '</th>
    <th>' .  _('Wednesday') . '</th>
    <th>' .  _('Thursday') . '</th>
    <th>' .  _('Friday') . '</th>
    <th>' .  _('Saturday') . '</th>';

  if(START_MONDAY) $output .= '    <th>' .  _('Sunday') . "</th>\n";

  $output .= '  </tr>
  </thead>
  <tbody>';

  // Loop to render the calendar
  for ($week_index = 0;; $week_index++) {
    $output .= "  <tr>\n";

    for ($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
      $i = $week_index * 7 + $day_of_week;
      $day_of_month = $i - $firstday + 1;

      if($i < $firstday || $day_of_month > $lastday) {
        $output .= '    <td class="none"></td>';
        continue;
      }

      // set whether the date is in the past or future/present
      if($currentyear > $year || $currentyear == $year
          && ($currentmonth > $month || $currentmonth == $month 
          && $currentday > $day_of_month)) {
        $current_era = 'past';
      } else {
        $current_era = 'future';
      }

      $output .= "
    <td valign=\"top\" class=\"$current_era\">
      <a href=\"display.php?day=$day_of_month&amp;month=$month&amp;year=$year\" 
        class=\"date\">$day_of_month</a>";

      $result = get_events_by_date($day_of_month, $month, $year);

      /* Start off knowing we don't need to close the event table
       loop through each event for the day
      */
      $tabling = 0;
      while($row = mysql_fetch_array($result)) {
        // if we didn't start the event table yet, do so
        if($tabling == 0) {
          if($BName == 'MSIE') { 
            $output .= "\n<table cellspacing=\"1\">\n";
          } else {
            $output .= "\n<table>\n";
          }
          $tabling = 1;
        }

        $subject = stripslashes($row['subject']);
        $typeofevent = $row['eventtype'];

        switch($typeofevent) {
         case 1:
          if(!HOURS_24) $timeformat = 'g:iA';
          else $timeformat = 'G:i';
          $event_time = date($timeformat, $row['start_since_epoch']);
          break;
         case 2:
          $event_time = _('FULL DAY');
          break;
         case 3:
          $event_time = '??:??';
          break;
         default:
          $event_time = 'BROKEN';
        }

        if($row['start_since_epoch'] < gmmktime(0, 0, 0, $month, $day_of_month,
            $year))
          $event_time = '<<<';

        $output .= "
        <tr>
          <td>
            <a href=\"display.php?day=$day_of_month&amp;month=$month&amp;year=$year\"><span class=\"event-time\">$event_time</span> $subject</a>
          </td>
        </tr>";
      }
        
      // If we opened the event table, close it
      if($tabling == 1) {
        $output .= '      </table>';
      }

      $output .= '    </td>';
    }
    $output .= "\n  </tr>\n";

    // If it's the last day, we're done
    if($day_of_month >= $lastday) {
      break;
    }
  }

  return $output . '  </tbody>
</table>
';
}
?>
