<?
	include ("config.php");
	include ("header.php");

	$currentday = date("j", time());
	$currentmonth = date("n", time());
	$currentyear = date("Y", time());

	$lastday = 28;
	$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password);

	if (!$month) {
		$month = $currentmonth;
	}

	if(!$day) {
		if($month == $currentmonth) $day = $currentday;
		else $day = 1;
	}

	if(!$year) {
		$year = $currentyear;
	}
	mysql_select_db($mysql_database, $database);
	
	$firstday = date( 'w', mktime(0,0,0,$month,1,$year));
	while (checkdate($month,$lastday,$year))
	{
	        $lastday++;
	}      
	
	$nextyear = $year+1;
	$prevyear = $year-1;

	echo "<table class=nav>
	<colgroup span=1 width=\"*\">
	<colgroup>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
		<col width=30px>
	</colgroup>
	<colgroup span=1 width=\"*\">
	<thead>
	<tr>
	<td colspan=14 class=title>" . date('F', mktime(0,0,0,$month,1,$year)) . " $year</td>
	</tr>
	</thead>
	<tr>
	<td><a href=\"?month=$month&amp;year=$prevyear\">prev year</a></td>
	<td><a href=\"?month=1&amp;year=$year\">Jan</a></td>
	<td><a href=\"?month=2&amp;year=$year\">Feb</a></td>
	<td><a href=\"?month=3&amp;year=$year\">Mar</a></td>
	<td><a href=\"?month=4&amp;year=$year\">Apr</a></td>
	<td><a href=\"?month=5&amp;year=$year\">May</a></td>
	<td><a href=\"?month=6&amp;year=$year\">Jun</a></td>
	<td><a href=\"?month=7&amp;year=$year\">Jul</a></td>
	<td><a href=\"?month=8&amp;year=$year\">Aug</a></td>
	<td><a href=\"?month=9&amp;year=$year\">Sep</a></td>
	<td><a href=\"?month=10&amp;year=$year\">Oct</a></td>
	<td><a href=\"?month=11&amp;year=$year\">Nov</a></td>
	<td><a href=\"?month=12&amp;year=$year\">Dec</a></td>
	<td><a href=\"?month=$month&amp;year=$nextyear\">next year</a></td>
	</tr>
	<tr>
	<td colspan=14><a href=\"operate.php?action=Add+Item&amp;month=$month&amp;year=$year&amp;day=$day\">Add Item</a></td>
	</tr></table>
	<table class=calendar>
	<colgroup span=7 width=\"1*\">
	<thead>
		<tr>
		<td>Sunday</td>
		<td>Monday</td>
		<td>Tuesday</td>
		<td>Wednesday</td>
		<td>Thursday</td>
		<td>Friday</td>
		<td>Saturday</td>
		</tr>
	</thead>
	<tbody>\n";

	for ($j = 0; $j<6; $j++)
	{
		echo "<tr>";
		for ($k = 0; $k<7; $k++)
		{
			$i = $j * 7 + $k;
			$nextday = $i - $firstday + 1;
			if($i < $firstday || $nextday >= $lastday) {
				echo "<td class=none></td>";
				continue;
			}
			if ($currentyear > $year || ($currentmonth > $month || $currentmonth == $month && $currentday > $nextday) && $currentyear == $year)
			{
				echo "<td valign=top class=past>";
                        }
                        else    
                        {
                               	echo "<td valign=top class=future>";
                        }
			echo "<a href=\"display.php?day=$nextday&amp;month=$month&amp;year=$year\" class=date>$nextday</a>";
			$query3 = mysql_query("SELECT subject, stamp FROM $mysql_tablename WHERE stamp >= \"$year-$month-$nextday 00:00:00\" AND stamp <= \"$year-$month-$nextday 23:59:59\" ORDER BY stamp");
			$tabling = 0;
			for ($i = 0; $i<mysql_num_rows($query3); $i++)
			{
				$results2 = mysql_fetch_array($query3);
				if ($results2["stamp"])
				{
					if($i == 0) {
						echo "<table class=list>";
						$tabling = 1;
					}
					$subject = htmlspecialchars(stripslashes($results2[subject]));
					$temp_time = date("g:i A", strtotime($results2[stamp]));
					echo "<tr><td><a href=\"display.php?day=$nextday&amp;month=$month&amp;year=$year\">$temp_time - $subject</a></td></tr>";
				}
			}
			if ($tabling == 1) {
				echo "</table>";
			}
			echo "</td>";
		}
		echo "</tr>\n";
		if($nextday >= $lastday) {
			break;
		}
	}

	echo "</table>\n";

	include("footer.php");
?>
