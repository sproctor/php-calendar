<?php
include_once("config.php");
include("header.php");

$currentday = date("j");
$currentmonth = date("n");
$currentyear = date("Y");

$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password)
     or die("couldn't connect to database");
mysql_select_db($mysql_database)
     or die("Couldn't select database");

if (empty($month)) {
    $month = $currentmonth;
}

if(empty($day)) {
    if($month == $currentmonth) $day = $currentday;
    else $day = 1;
}

if(empty($year)) {
    $year = $currentyear;
}

$firstday = date("w", mktime(0,0,0,$month,1,$year));
$lastday = date("t", mktime(0,0,0,$month,$day,$year));
	
$nextyear = $year + 1;
$prevyear = $year - 1;

if(isold()) { 
    echo <<<END
<table border="0" cellspacing="0" cellpadding="0" width="96%">
  <tr>
    <td bgcolor="$bordercolor">
<table width="100%" border="0" cellspacing="1" cellpadding="2"
bgcolor="$tablebgcolor"
END;
}
else {
    echo "<table class=\"nav\"";
    if($BName == "MSIE") { echo " cellspacing=1"; }
}
echo <<<END
>
  <colgroup><col></colgroup>
  <colgroup span="12" width="30">
  <colgroup><col></colgroup>
<thead>
  <tr>
    <th colspan="14">
END;
echo date('F', mktime(0,0,0,$month,1,$year));
echo " $year
    </th>
  </tr>
</thead>
  <tr>
    <td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=$month&amp;year=$prevyear\">prev year</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=1&amp;year=$year\">Jan</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=2&amp;year=$year\">Feb</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=3&amp;year=$year\">Mar</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=4&amp;year=$year\">Apr</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=5&amp;year=$year\">May</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=6&amp;year=$year\">Jun</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=7&amp;year=$year\">Jul</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=8&amp;year=$year\">Aug</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=9&amp;year=$year\">Sep</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=10&amp;year=$year\">Oct</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=11&amp;year=$year\">Nov</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=12&amp;year=$year\">Dec</a>
    </td>
	<td", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"?month=$month&amp;year=$nextyear\">next year</a>
    </td>
  </tr>
  <tr>
	<td colspan=\"14\"", ifold(" align=\"center\">
      <a style=\"text-decoration:none;color:$headercolor\"", ">
      <a"), " href=\"operate.php?action=Add+Item&amp;month=$month&amp;year=$year&amp;day=$day\">Add Item</a>
    </td>
  </tr>
</table>";

if(isold()) { 
    echo <<<END
    </td>
  </tr>
</table>
<br>
<table width="96%" cellspacing="0" cellpadding="0" border="0">
  <tr>
    <td bgcolor="$bordercolor">
<table width="100%" cellspacing="2" cellpadding="2" border="0">
END;
} else {
    echo "<table class=\"calendar\">";
}

echo "
  <colgroup span=\"7\" width=\"1*\">
  <thead>
  <tr", ifold(" bgcolor=\"$headerbgcolor\"", ""), ">
    <th>", ifold("<font color=\"$headercolor\">Sunday</font>", "Sunday"), "</th>
    <th>", ifold("<font color=\"$headercolor\">Monday</font>", "Monday"), "</th>
    <th>", ifold("<font color=\"$headercolor\">Tuesday</font>", "Tuesday"), "</th>
	<th>", ifold("<font color=\"$headercolor\">Wednesday</font>", "Wednesday"), "</th>
    <th>", ifold("<font color=\"$headercolor\">Thursday</font>", "Thursday"), "</th>
    <th>", ifold("<font color=\"$headercolor\">Friday</font>", "Friday"), "</th>
    <th>", ifold("<font color=\"$headercolor\">Saturday</font>", "Saturday"), "</th>
  </tr>
  </thead>
  <tbody>\n";

for ($j = 0;; $j++) {
    echo "  <tr>\n";
    for ($k = 0; $k<7; $k++) {
        $i = $j * 7 + $k;
        $nextday = $i - $firstday + 1;
        if($i < $firstday || $nextday > $lastday) {
            echo "    <td class=\"none\">" . ifold("&nbsp;", "") . "</td>";
            continue;
        }
        if ($currentyear > $year || ($currentmonth > $month 
                                     || $currentmonth == $month 
                                     && $currentday > $nextday) 
            && $currentyear == $year) {
            $pastorfuture = "past";
        } else {
            $pastorfuture = "future";
        }
        echo "    <td valign=\"top\"" . ifold(" height=\"80\" bgcolor=\"$tablebgcolor\">", " class=\"$pastorfuture\">");
        echo "      <a href=\"display.php?day=$nextday&amp;month=$month&amp;year=$year\" 
        class=\"date\">", ifold("<b>$nextday</b></a>", "$nextday</a>");
        $query = "SELECT subject, stamp, eventtype FROM $mysql_tablename WHERE stamp >= \"$year-$month-$nextday 00:00:00\" AND stamp <= \"$year-$month-$nextday 23:59:59\" ORDER BY stamp";
        $result = mysql_query($query)
            or die("couldn't select item");
        $tabling = 0;

        while($row = mysql_fetch_array($result)) {
            if($tabling == 0) {
                if(isold()) { 
                    echo "
      <table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">
      <tr><td bgcolor=\"$bordercolor\">
      <table class=\"$pastorfuture\" cellspacing=\"1\" cellpadding=\"2\" 
        border=\"0\" width=\"100%\">\n"; 
                } elseif($BName == "MSIE") { 
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
            
            echo "
        <tr>
          <td>
            ", ifold('<font size="1">', ""), "<a href=\"display.php?day=$nextday&amp;month=$month&amp;year=$year\">
              $temp_time - $subject
            </a>", ifold("</font>", ""), "
          </td>
        </tr>\n";
        }
        
        
        if ($tabling == 1) {
            echo "      </table>";
            if(isold()) { echo "</td></tr></table>"; }
        }
        echo "    </td>";
    }
    echo "  </tr>\n";
    if($nextday >= $lastday) {
        break;
    }
}

echo "</table>\n";
if(isold()) { 
    echo "</td></tr></table>"; 
}

include("footer.php");
?>
