<?
	include ("header.php");
	include ("config.php");
	$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password);
	mysql_select_db($mysql_database, $database);

	$lastday = 1;
	while (checkdate($month,$lastday,$year))
	{
		$lastday++;
	}

	switch ($action)
	{
		case "Delete Selected":
			if (!$id)
			{
				echo "<p>You can't delete nothing from the table.";
				break;
			}
			echo "<p>We are about to delete id $id from $mysql_tablename";

			$query = mysql_query("SELECT username FROM $mysql_tablename WHERE id = $id");
			$row = mysql_fetch_array($query);
		
			if ( !$REMOTE_USER )
			{
				mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'");
				echo "<p>Item Deleted";
			}
			else
			{
				if ( strcmp($row[username], $REMOTE_USER) == 0 )
				{
					mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'");
					echo "<p>Item Deleted";
				}
				else
				{
					echo "<p>You aren't the original user, you can't delete this";
				}
			}
					

			break;

		case "Modify Selected":
			if (!$id)
			{
				echo "Nothing to modify.";
				break;
			}
			echo "<p>We are about to modify id $id from $mysql_tablename</p>\n";
			$query = mysql_query("SELECT *, RIGHT(stamp, 8) AS thetime, SUBSTRING(stamp FROM 9 FOR 2) AS theday FROM $mysql_tablename WHERE id = '$id'");
			$row = mysql_fetch_array($query);

			if ( !$REMOTE_USER )
			{
				$name = stripslashes($row[username]);
			}
			else
			{
				if(strcmp($REMOTE_USER, $row[username]) != 0) {
					echo "<p>Since you are not the original user, you can't change this</p>";
					break;
				}
			}

			$the_day = $row[theday];

			$subject = stripslashes($row[subject]);
			$desc = htmlspecialchars(stripslashes($row[description]));
			$the_time = $row[thetime];

		case "Add Item":
			if($action == "add") {
				echo "<p>Adding item to calendar</p>\n";
				$query = mysql_query("SELECT max(id) as id FROM $mysql_tablename");
				if ($query)
				{
					$result = mysql_fetch_array($query);
					$result["id"]++;
				}
				else
				{
					$result["id"] = 0;
				}
				$id = $result["id"];
				$name = "";
				$the_day = $day;
				$subject = "";
				$the_time = "";
				$desc = "";
			}
			echo "<form method=post action=operate.php>\n";
			echo "<table class=edit>
  <tr>
    <td>Name</td>";

			if ( !$REMOTE_USER )
			{
				echo "
    <td><input type=text name=username size=20 value=\"$name\"></td>
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
			for ($i=1; $i<$lastday; $i++)
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
    <td>Time (hh:mm:ss)</td>
    <td><input type=text name=time value=\"$the_time\"></td>
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
    <td colspan=2 style=\"text-align:center\"><input type=hidden name=action value=Addsucker>";
			if($action == "mod") {
				echo "<input type=hidden name=id value=\"$id\">\n<input type=hidden name=modify value=Modify>\n";
			}
			echo "<input type=submit value=\"Submit item\"></td>
  </tr>
</table>
</form>\n";
			break;

		case "Addsucker":

			if ($modify)
			{
				mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'");
			}
			$description = ereg_replace("<[bB][rR][^>]*>", "\n", $description);
			$subject = ereg_replace("<[^>]*>", "", $subject);
			$name = ereg_replace("<[^>]*>", "", $name);
			$description = ereg_replace("</?([^aA/]|[a-zA-Z_]{2,})[^>]*>", "", $description);
			$description = addslashes($description);
			$subject = addslashes($subject);

			$temp = mysql_query("INSERT INTO $mysql_tablename (username, stamp, subject, description) VALUES ('$username', '$year-$month-$day $time', '$subject', '$description')");
			if ($temp != 0)
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
