<?
	include ("header.php");
	include ("config.php");

function print_menu ($month, $day, $year)
{
	$lastseconds = mktime(0,0,0,$month,$day,$year)-(24*60*60);
        $lastday = date('j', $lastseconds);
        $lastmonth = date('m', $lastseconds);
        $lastyear = date('Y', $lastseconds);

        $nextseconds = mktime(0,0,0,$month,$day,$year)+(24*60*60);
        $nextday = date('j', $nextseconds);
        $nextmonth = date('m', $nextseconds);
        $nextyear = date('Y', $nextseconds);

	echo "<table width=\"100%\">
	<colgroup>
		<col width=\"1*\">
		<col width=\"0*\" span=3>
		<col width=\"1*\">
	</colgroup>
	<tr>
	<td align=right><form action=display.php><p>
		<input type=submit value='<<'>
        	<input type=hidden name=day value=$lastday>
        	<input type=hidden name=month value=$lastmonth>
        	<input type=hidden name=year value=$lastyear>
	</p></form></td>
        <td><form action=operate.php><p>
                <input type=hidden name=month value=$month>
                <input type=hidden name=year value=$year>
                <input type=hidden name=day value=$day>
                <input type=submit name='action' value='Add item to calendar'>
	</p></form></td>
	<td><form action=modify.php><p>
                <input type=hidden name=month value=$month>
                <input type=hidden name=day value=$day>
                <input type=hidden name=year value=$year>
                <input type=submit value='Delete or Modify'>
	</p></form></td>
	<td><form action=\"./\"><p>
              <input type=submit value='Return to Calendar'>
              <input type=hidden name=month value=$month>
              <input type=hidden name=year value=$year>
	</p></form></td>
        <td align=left><form action=display.php><p>
		<input type=submit value='>>'>
        	<input type=hidden name=day value=$nextday>
        	<input type=hidden name=month value=$nextmonth>
        	<input type=hidden name=year value=$nextyear>
	</p></form></td>
	</tr></table>";
}
	print_menu($month, $day, $year);
	$monthname = date('F', mktime(0,0,0,$month,1,$year));

	echo "</div><div class=title>$day $monthname $year</div><div class=desc>";

	$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password);
	mysql_select_db($mysql_database, $database);

	$lastseconds = mktime(0,0,0,$month,$day,$year)-(24*60*60);
        $lastday = date('j', $lastseconds);
        $lastmonth = date('m', $lastseconds);
        $lastyear = date('Y', $lastseconds);

        $nextseconds = mktime(0,0,0,$month,$day,$year)+(24*60*60);
        $nextday = date('j', $nextseconds);
        $nextmonth = date('m', $nextseconds);
        $nextyear = date('Y', $nextseconds);

	$query = mysql_query("SELECT * FROM $mysql_tablename WHERE stamp >= \"$year-$month-$day 00:00:00\" AND stamp <= \"$year-$month-$day 23:59:59\" ORDER BY stamp", $database);

	$using = 0;
	while ($row = mysql_fetch_array($query))
	{
		if ($using == 0) {
			echo "<table width=\"100%\" cellspacing=2 border=1>
				<colgroup>
					<col>
					<col width=96px>
					<col>
					<col>
				</colgroup>
				<tr><th>Poster</th><th>Time</th><th>Subject</th><th>Description</th></tr>";
			$using = 1;
		}
		$name = stripslashes($row[username]);
		$time = date("g:i A", strtotime($row[stamp]));
		$subject = stripslashes($row[subject]);
		$desc = stripslashes($row[description]);
		echo "<tr><td>$name</td><td style=\"text-align:right;padding-right:10px\">$time</td><td>$subject</td><td>$desc</td></tr>";
	}
	if ($using == 1) {
		echo "</table>";
	}
	print_menu($month, $day, $year);
	include("footer.php");
?>








