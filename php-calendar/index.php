<?php
include_once("calendar.inc");
include_once("config.inc");

top();

$currentday = date("j");
$currentmonth = date("n");
$currentyear = date("Y");

$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password)
     or die("couldn't connect to database");
mysql_select_db($mysql_database)
     or die("Couldn't select database");


if (empty($_GET['month'])) {
    $month = $currentmonth;
} else {
    $month = $_GET['month'];
}

if(empty($_GET['day'])) {
    if($month == $currentmonth) $day = $currentday;
    else $day = 1;
} else {
    $day = $_GET['day'];
}

if(empty($_GET['year'])) {
    $year = $currentyear;
} else {
    $year = $_GET['year'];
}

$firstday = date("w", mktime(0,0,0,$month,1,$year));
$lastday = date("t", mktime(0,0,0,$month,$day,$year));
	
$nextyear = $year + 1;
$prevyear = $year - 1;

echo "<table class=\"nav\"";
if($BName == "MSIE") { echo " cellspacing=1"; }

echo <<<END
>
  <colgroup><col /></colgroup>
  <colgroup span="12" width="30" />
  <colgroup><col /></colgroup>
<thead>
  <tr>
    <th colspan="14">
END;
echo date('F', mktime(0,0,0,$month,1,$year));
echo <<<END
      $year
    </th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>
      <a href="?month=$month&amp;year=$prevyear">prev year</a>
    </td>
	  <td>
      <a href="?month=1&amp;year=$year">Jan</a>
    </td>
	  <td>
      <a href="?month=2&amp;year=$year">Feb</a>
    </td>
	  <td>
      <a href="?month=3&amp;year=$year">Mar</a>
    </td>
	  <td>
      <a href="?month=4&amp;year=$year">Apr</a>
    </td>
	  <td>
      <a href="?month=5&amp;year=$year">May</a>
    </td>
	  <td>
      <a href="?month=6&amp;year=$year">Jun</a>
    </td>
	  <td>
      <a href="?month=7&amp;year=$year">Jul</a>
    </td>
	  <td>
      <a href="?month=8&amp;year=$year">Aug</a>
    </td>
	  <td>
      <a href="?month=9&amp;year=$year">Sep</a>
    </td>
	  <td>
      <a href="?month=10&amp;year=$year">Oct</a>
    </td>
	  <td>
      <a href="?month=11&amp;year=$year">Nov</a>
    </td>
	  <td>
      <a href="?month=12&amp;year=$year">Dec</a>
    </td>
	  <td>
      <a href="?month=$month&amp;year=$nextyear">next year</a>
    </td>
  </tr>
  <tr>
	  <td colspan="14">
      <a href="operate.php?action=Add+Item&amp;month=$month&amp;year=$year&amp;day=$day">Add Item</a>
    </td>
  </tr>
</tbody>
</table>\n
END;

echo <<<END
<table class="calendar">
  <colgroup span="7" width="1*" />
  <thead>
  <tr>
    <th>Sunday</th>
    <th>Monday</th>
    <th>Tuesday</th>
	  <th>Wednesday</th>
    <th>Thursday</th>
    <th>Friday</th>
    <th>Saturday</th>
  </tr>
  </thead>
  <tbody>
END;

for ($j = 0;; $j++) {
  echo "  <tr>\n";
  for ($k = 0; $k<7; $k++) {
    $i = $j * 7 + $k;
    $nextday = $i - $firstday + 1;
    if($i < $firstday || $nextday > $lastday) {
      echo "    <td class=\"none\"></td>";
      continue;
    }
    if($currentyear > $year || $currentyear == $year
       && ($currentmonth > $month || $currentmonth == $month 
           && $currentday > $nextday)) {
      $pastorfuture = "past";
    } else {
      $pastorfuture = "future";
    }
    echo <<<END
    <td valign="top" class="$pastorfuture">
      <a href="display.php?day=$nextday&amp;month=$month&amp;year=$year" 
        class="date">$nextday</a>
END;
    $query = "SELECT subject, stamp, eventtype FROM $mysql_tablename WHERE stamp >= \"$year-$month-$nextday 00:00:00\" AND stamp <= \"$year-$month-$nextday 23:59:59\" ORDER BY stamp";
    $result = mysql_query($query)
      or die("couldn't select item");
    $tabling = 0;

    while($row = mysql_fetch_array($result)) {
      if($tabling == 0) {
        if($BName == "MSIE") { 
          echo "\n<table class=\"$pastorfuture\" cellspacing=\"1\">\n";
        } else {
          echo "\n<table class=\"$pastorfuture\">\n";
        }
        $tabling = 1;
      }
            
      $subject = stripslashes($row['subject']);
      $typeofevent = $row['eventtype'];

      if($typeofevent == 3) {
        $temp_time = "??:??";
      } elseif($typeofevent == 2) {
        $temp_time = "FULL DAY";
      } else {
        $temp_time = date("g:i A", strtotime($row['stamp']));
      }
            
      echo <<<END
        <tr>
          <td>
            <a href="display.php?day=$nextday&amp;month=$month&amp;year=$year">
              $temp_time - $subject
            </a>
          </td>
        </tr>
END;
    }
        
        
    if($tabling == 1) {
      echo "      </table>";
    }
    echo "    </td>";
  }
  echo "  </tr>\n";
  if($nextday >= $lastday) {
    break;
  }
}

echo "  </tbody>
</table>\n";

bottom();
?>
