<?
include ("config.php");
include ("header.php");

$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password);
mysql_select_db($mysql_database, $database);

$lastday = date("t", mktime(0,0,0,$month,1,$year));

switch ($action) {
case "Delete Selected":
	if (!isset($id)) {
		echo "<p>You can't delete nothing from the table.";
		break;
	}
	echo "<p>We are about to delete id $id from $mysql_tablename";

	$query = mysql_query("SELECT username FROM $mysql_tablename WHERE id = $id");
	$row = mysql_fetch_array($query);
		
	if (!isset($REMOTE_USER)) {
		mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'");
		echo "<p>Item Deleted";
	} else {
		if ( strcmp($row['username'], $REMOTE_USER) == 0 ) {
			mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'");
			echo "<p>Item Deleted";
		} else {
			echo "<p>You aren't the original user, you can't delete this";
		}
	}
					

	break;

case "Modify Selected":
	$modify = "Modify";
	if (!isset($id)) {
		echo "Nothing to modify.";
		break;
	}
	echo "<p>We are about to modify id $id from $mysql_tablename</p>\n";
	$query = mysql_query("SELECT *, RIGHT(stamp, 8) AS thetime, SUBSTRING(stamp FROM 9 FOR 2) AS theday FROM $mysql_tablename WHERE id = '$id'");
	$row = mysql_fetch_array($query);

	if ( !isset($REMOTE_USER) )
			{
				$username = stripslashes($row['username']);
			}
			else
			{
				if(strcmp($REMOTE_USER, $row['username']) != 0) {
					echo "<p>Since you are not the original user, you can't change this</p>";
					break;
				}
			}

			$subject = stripslashes($row['subject']);
			$desc = htmlspecialchars(stripslashes($row['description']));
			$thetime = strtotime($row['stamp']);
			$hour = date("G", $thetime);
			$minute = date("i", $thetime);
			$month = date("n", $thetime);
			$year = date("Y", $thetime);
			$day = date("j", $thetime);
			if($hour >= 12) {
				$pm = 1;
				$hour = $hour - 12;
			}
			else $pm = 0;
			echo "$hour";
		case "Add Item":
			if($action == "Add Item") {
				echo "<p>Adding item to calendar</p>\n";
				$query = mysql_query("SELECT max(id) as id FROM $mysql_tablename");
				if ($query)
				{
					$result = mysql_fetch_array($query);
					$result['id']++;
				}
				else
				{
					$result['id'] = 0;
				}
				$id = $result['id'];
				$username = "";
				$subject = "";
				$desc = "";
				if(!isset($day)) $day = date("j");
				if(!isset($month)) $month = date("n");
				if(!isset($year)) $year = date("Y");
				if($day == date("j") && $month == date("n") 
				  && $year == date("Y")) {
				  $hour = date("G") + 1;
				  if($hour >= 12) {
				    $hour = $hour - 12;
				    $pm = 1;
				  } else $pm = 0;
				} else { $hour = 6; $pm = 1; }
				$minute = 0;
			}

			echo "<form method=post action=operate.php>\n";
			if(isold()) echo "<table cellspacing=0 cellpadding=1 border=0 width=\"96%\"><tr><td bgcolor=\"$bordercolor\">
<table class=edit cellspacing=0 border=0 cellpadding=2 width=\"100%\"";
			else echo "<table class=edit";
			echo ">
  <tr>
    <td>Name</td>";

			if ( !isset($REMOTE_USER) )
			{
				echo "
    <td><input type=text name=username size=20 value=\"$username\"></td>
  </tr>";
			}
			else
			{
				echo "<td>$REMOTE_USER<input type=hidden name=username value=\"$REMOTE_USER\"></td></tr>";
			}
			
			echo "
  <tr><td>Day</td>
    <td>
      <select name=day size=1>\n";
			for ($i=1; $i <= $lastday; $i++)
			{
				if ($i == $day)
					echo "        <option value=$i selected>$i</option>\n";
				else    
					echo "        <option value=$i>$i</option>\n";
			}
			echo "      </select>\n      <select size=1 name=month>\n";
			for ($i=1; $i<13; $i++)
			{
				$nm = date("F", mktime(0,0,0,$i,1,$year));
				if ($i == $month)
					echo "        <option value=$i selected>$nm</option>\n";
				else
					echo "        <option value=$i>$nm</option>\n";
			}
			echo "      </select>\n      <select size=1 name=year>";
			for ($i=$year-2; $i<$year+5; $i++)
			{
				if ($i == $year)
					echo "        <option value=$i selected>$i</option>\n";
				else
					echo "        <option value=$i>$i</option>\n";
			}
			echo "      </select></td>
  </tr>
  <tr>
    <td>Time:</td>
    <td><select name=hour size=1>\n";
	for($i = 1; $i < 12; $i++) {
		echo "<option value='$i'";
		if($hour == $i) echo " selected";
		echo ">$i</option>\n";
	}
	echo "<option value='0'";
	if($hour == 0) echo " selected";
	echo ">12</option>
</select><span class=bold>:</span><select name=minute size=1>\n";
	for($i = 0; $i <= 59; $i = $i + 5) {
		echo "<option value='$i'";
		if($minute >= $i && $i > $minute - 5) echo " selected";
		printf(">%02d</option>\n", $i);
	}
	echo "</select><select name=pm size=1>
<option value='0'";
if(!$pm) echo " selected";
echo ">AM</option>
<option value='1'";
if($pm) echo " selected";
echo ">PM</option>
</select></td>
  </tr>
  <tr>
    <td>Subject (255 chars max)</td>
    <td><input type=text name=subject value=\"$subject\"></td>
  </tr>
  <tr>
    <td>Description</td>
    <td><textarea rows=5 cols=50 name=description>$desc</textarea></td>
  </tr>
  <tr>
    <td colspan=2 " . ifold("align=center", "style=\"text-align:center\"") . "><input type=hidden name=action value=Addsucker>";
			if($modify) {
				echo "<input type=hidden name=id value=\"$id\">\n<input type=hidden name=modify value=Modify>\n";
			}
			echo "<input type=submit value=\"Submit item\"></td>
  </tr>
</table>" . ifold("</td></tr></table>", "") . "
</form>\n";
			break;

		case "Addsucker":
			if (isset($modify))
			{
				mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'");
			}
			$description = ereg_replace("<[bB][rR][^>]*>", "\n", $description);
			$subject = addslashes(ereg_replace("<[^>]*>", "", $subject));
			$username = addslashes(ereg_replace("<[^>]*>", "", $username));
			$description = addslashes(ereg_replace("</?([^aA/]|[a-zA-Z_]{2,})[^>]*>", "", $description));
			if($pm) $hour = $hour + 12;
			$timestamp = date("Y-m-d H:i:s", mktime($hour,$minute,0,$month,$day,$year));

			$temp = mysql_query("INSERT INTO $mysql_tablename (username, stamp, subject, description) VALUES ('$username', '$timestamp', '$subject', '$description')");
			if ($temp)
				echo "Item added ...";
			else
			{
				echo "Item may not have been added ...";
				echo mysql_error();
			}

			break;
	}

	echo "<form method=get action=\"./\"><p>
  <input type=submit value='Back to Calendar'>
  <input type=hidden name=month value=\"$month\">
  <input type=hidden name=year value=\"$year\">
</p></form>";
	include("footer.php");
?>
