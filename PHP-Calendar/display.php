<?
	include("config.php");
	include("header.php");

	$tablename = date('Fy', mktime(0,0,0,$month,1,$year));
	$monthname = date('F', mktime(0,0,0,$month,1,$year));
	$lastmonthname = $monthname;
	$nextmonthname = $monthname;

	if(!isset($day)) $day = date("j");
	if(!isset($month)) $month = date("n");
	if(!isset($year)) $year = date("Y");

	$lasttime = mktime(0,0,0,$month,$day-1,$year);
	$lastday = date("j", $lasttime);
	$lastmonth = date("n", $lasttime);
	$lastyear = date("Y", $lasttime);
	$lastmonthname = date("F", $lasttime);

	$nexttime = mktime(0,0,0,$month,$day+1,$year);
	$nextday = date("j", $nexttime);
	$nextmonth = date("n", $nexttime);
	$nextyear = date("Y", $nexttime);
	$nextmonthname = date('F', $nexttime);

	if(isold()) { echo "<table border=0 cellspacing=0 cellpadding=0 width=\"96%\">
<tr><td bgcolor=\"$bordercolor\">
<table border=0 cellspacing=1 cellpadding=2 width=\"100%\" bgcolor=\"$tablebgcolor\">\n";
} else echo "<table class=nav>\n";
	echo "<tr>
	<td" . ifold(" align=center><a style=\"text-decoration:none;color:$headercolor\"", "><a") . " href=\"display.php?month=$lastmonth&amp;day=$lastday&amp;year=$lastyear\">$lastmonthname $lastday</a></td>
	<td" . ifold(" align=center><a style=\"color:$headercolor;text-decoration:none\"", "><a") . " href=\".?month=$month&amp;day=$day&amp;year=$year\">Back to Calendar</a></td>
	<td" . ifold(" align=center><a style=\"color:$headercolor;text-decoration:none\"", "><a") . " href=\"display.php?month=$nextmonth&amp;day=$nextday&amp;year=$nextyear\">$nextmonthname $nextday</a></td>
	</tr>
	</table>" . ifold("</td></tr></table>", "") . "
	<form action=operate.php>" . ifold("<table cellspacing=0 cellpadding=0 border=0 width=\"100%\"><tr><td bgcolor=\"$bordercolor\">", "") . "
	<table cellspacing=2 " . ifold("bgcolor=\"$tablebgcolor\" cellpadding=2 border=0 width=\"100%\"", "class=display") . ">
	<tr><td colspan=5 class=title>$day $monthname $year</td></tr>";

	$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password);
	mysql_select_db($mysql_database, $database);

	$query = mysql_query("SELECT * FROM $mysql_tablename WHERE stamp >= \"$year-$month-$day 00:00:00\" AND stamp <= \"$year-$month-$day 23:59:59\" ORDER BY stamp", $database);

	echo "<tr><td><b>Select</b></td><td><b>Username</b></td><td><b>Time</b></td><td><b>Subject</b></td>
		<td><b>Description</b></td></tr>";
	while ($row = mysql_fetch_array($query))
	{
		$i++;
		$name = stripslashes($row['username']);
		$subject = stripslashes($row['subject']);
		$desc = nl2br(stripslashes($row['description']));
		$time = date("j F Y, h:i A", strtotime($row['stamp']));
		if(isold()) {
			if(!$name) $name = "&nbsp;";
			if(!$subject) $subject = "&nbsp";
			if(!$desc) $desc = "&nbsp;";
		}
		echo "<tr><td><input type=radio name=id value=$row[id]></td>
			<td>$name</td><td>$time</td>
			<td>$subject</td><td>$desc</td></tr>";
	}
	echo "</table>" . ifold("</td></tr></table>", "") . "
	<p>
	<input type=hidden name=day value=\"$day\">
	<input type=hidden name=month value=\"$month\">
	<input type=hidden name=year value=\"$year\">
	<input type=submit name=action value=\"Delete Selected\">
	<input type=submit name=action value=\"Modify Selected\">
	<input type=submit name=action value=\"Add Item\">
	</form>";
	include("footer.php");
?>
