<?php
include_once("calendar.inc");
include_once("config.inc");

function navbar($year, $month, $day)
{
  global $BName;

  $nextmonth = $month + 1;
  $lastmonth = $month - 1;
  $nextyear = $year + 1;
  $lastyear = $year - 1;

  echo "<table id=\"navbar\"";
  if($BName == "MSIE") { echo " cellspacing=1"; }

  echo ">
  <colgroup><col /></colgroup>
  <colgroup span=\"12\" width=\"30\" />
  <colgroup><col /></colgroup>
<thead>
  <tr>
    <th colspan=\"14\">" . month_name($month) . " $year</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>
      <a href=\"?month=$lastmonth&amp;year=$year\">" . 
_("last month")
. "</a>
    </td>
    <td>
      <a href=\"?month=1&amp;year=$year\">" . 
_("Jan")
. "</a>
    </td>
    <td>
      <a href=\"?month=2&amp;year=$year\">" . 
_("Feb") 
. "</a>
    </td>
    <td>
      <a href=\"?month=3&amp;year=$year\">" . 
_("Mar") 
. "</a>
    </td>
    <td>
      <a href=\"?month=4&amp;year=$year\">" . 
_("Apr") 
. "</a>
    </td>
    <td>
      <a href=\"?month=5&amp;year=$year\">" . 
_("May") 
. "</a>
    </td>
    <td>
      <a href=\"?month=6&amp;year=$year\">" . 
_("Jun") 
. "</a>
    </td>
    <td>
      <a href=\"?month=7&amp;year=$year\">" . 
_("Jul") 
. "</a>
    </td>
    <td>
      <a href=\"?month=8&amp;year=$year\">" . 
_("Aug") 
. "</a>
    </td>
    <td>
      <a href=\"?month=9&amp;year=$year\">" . 
_("Sep") 
. "</a>
    </td>
    <td>
      <a href=\"?month=10&amp;year=$year\">" . 
_("Oct") 
. "</a>
    </td>
    <td>
      <a href=\"?month=11&amp;year=$year\">" . 
_("Nov") 
. "</a>
    </td>
    <td>
      <a href=\"?month=12&amp;year=$year\">" . 
_("Dec") 
. "</a>
    </td>
    <td>
      <a href=\"?month=$nextmonth&amp;year=$year\">" . 
_("next month") 
. "</a>
    </td>
  </tr>
  <tr>
    <td>
      <a href=\"?month=$month&amp;year=$lastyear\">" . 
_("last year") 
. "</a>
    </td> 
    <td colspan=\"12\">
      <a href=\"operate.php?action=add&amp;month=$month&amp;year=$year&amp;day=$day\">" . 
_("Add Item") 
. "</a>
    </td>
    <td>
      <a href=\"?month=$month&amp;year=$nextyear\">" . 
_("next year") 
. "</a>
    </td>
  </tr>
</tbody>
</table>";
}

function calendar($year, $month, $day, $database, $mysql_tablename)
{
  global $start_monday, $BName;
  $currentday = date("j");
  $currentmonth = date("n");
  $currentyear = date("Y");

  $firstday = date("w", mktime(0,0,0,$month,1,$year));
  $lastday = date("t", mktime(0,0,0,$month,1,$year));

  echo "<table id=\"calendar\">
  <colgroup span=\"7\" width=\"1*\" />
  <thead>
  <tr>\n";

  if(!$start_monday) {
    echo "    <th>" . 
_("Sunday") 
. "</th>\n";
  }
  
  echo "\n    <th>" . 
_("Monday") 
. "</th>
    <th>" . 
_("Tuesday") 
. "</th>
    <th>" . 
_("Wednesday") 
. "</th>
    <th>" . 
_("Thursday") 
. "</th>
    <th>" . 
_("Friday") 
. "</th>
    <th>" . 
_("Saturday") 
. "</th>";
  if($start_monday) {
    echo "    <th>" . 
_("Sunday") 
. "</th>\n";
  }
  echo "  </tr>
  </thead>
  <tbody>";

// Loop to render the calendar
for ($week_index = 0;; $week_index++) {
  echo "  <tr>\n";

  for ($day_of_week = 0; $day_of_week < 7; $day_of_week++) {
    // If we want to start on monday, then start on monday!
    if($start_monday) {
      $day_of_week = ($day_of_week + 1) % 7;
    }
    $i = $week_index * 7 + $day_of_week;
    $day_of_month = $i - $firstday + 1;

    if($i < $firstday || $day_of_month > $lastday) {
      echo "    <td class=\"none\"></td>";
      continue;
    }

    // set whether the date is in the past or future/present
    if($currentyear > $year || $currentyear == $year
       && ($currentmonth > $month || $currentmonth == $month 
           && $currentday > $day_of_month)) {
      $current_era = "past";
    } else {
      $current_era = "future";
    }

    echo <<<END
    <td valign="top" class="$current_era">
      <a href="display.php?day=$day_of_month&amp;month=$month&amp;year=$year" 
        class="date">$day_of_month</a>
END;

    $result = mysql_query("SELECT subject, stamp, eventtype 
        FROM $mysql_tablename 
        WHERE stamp >= \"$year-$month-$day_of_month 00:00:00\" 
        AND stamp <= \"$year-$month-$day_of_month 23:59:59\" ORDER BY stamp")
      or die("couldn't select item");
    

    /* Start off knowing we don't need to close the event table
       loop through each event for the day
     */
    $tabling = 0;
    while($row = mysql_fetch_array($result)) {
      // if we didn't start the event table yet, do so
      if($tabling == 0) {
        if($BName == "MSIE") { 
          echo "\n<table cellspacing=\"1\">\n";
        } else {
          echo "\n<table>\n";
        }
        $tabling = 1;
      }
            
      $subject = stripslashes($row['subject']);
      $typeofevent = $row['eventtype'];

      if($typeofevent == 3) {
        $event_time = "??:??";
      } elseif($typeofevent == 2) {
        $event_time = _("FULL DAY");
      } else {
        $event_time = date("g:i A", strtotime($row['stamp']));
      }
            
      echo <<<END
        <tr>
          <td>
            <a href="display.php?day=$day_of_month&amp;month=$month&amp;year=$year">
              $event_time - $subject
            </a>
          </td>
        </tr>
END;
    }
        
    // If we opened the event table, close it
    if($tabling == 1) {
      echo "      </table>";
    }

    echo "    </td>";
  }
  echo "  </tr>\n";

  // If it's the last day, we're done
  if($day_of_month >= $lastday) {
    break;
  }
}

echo "  </tbody>
</table>\n";
}
?>
