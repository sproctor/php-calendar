<?php
include_once("calendar.inc");
include_once("config.inc");

top();

$database = mysql_connect($mysql_hostname, $mysql_username, $mysql_password);
mysql_select_db($mysql_database, $database);

if(empty($_GET['action'])) $action = "none";
else $action = $_GET['action'];

if(empty($_GET['day'])) $day = date("j");
else $day = $_GET['day'];

if(empty($_GET['month'])) $month = date("n");
else $month = $_GET['month'];

if(empty($_GET['year'])) $year = date("Y");
else $year = $_GET['year'];

switch ($action) {
 case "Delete Selected":
     if (empty($id)) {
         echo "<div class=\"box\">You must select an item to delete</div>.";
         break;
     }
     
     $headerstring =  "We are about to delete id $id from $mysql_tablename";
     
     $query = "SELECT username FROM $mysql_tablename WHERE id = $id";
     $result = mysql_query($query);
     $row = mysql_fetch_array($result);
     
     $query = "DELETE FROM $mysql_tablename WHERE id = '$id'";
     mysql_query($query)
         or die("couldn't delete item");
     
     if(mysql_affected_rows() == 0) {
         echo "<div class=\"box\">No item to delete</div>";
     } else {        
         echo "<div class=\"box\">Item Deleted</div>";
     }
     
     break;
     
 case "Modify Selected":
     $modify = "Modify";
     if (empty($_GET['id'])) {
         echo "<div class=\"box\">Nothing to modify.</div>";
         break;
     } else {
       $id = $_GET['id'];
     }
     
     $headerstring = "We are about to modify id $id from $mysql_tablename";
     $result = mysql_query("SELECT * FROM $mysql_tablename WHERE id = '$id'")
         or die("couldn't get items from table");
         
     $row = mysql_fetch_array($result);
     $username = stripslashes($row['username']);
     $subject = stripslashes($row['subject']);
     $desc = htmlspecialchars(stripslashes($row['description']));
     $thetime = strtotime($row['stamp']);
     $hour = date("G", $thetime);
     $minute = date("i", $thetime);
     $month = date("n", $thetime);
     $year = date("Y", $thetime);
     $day = date("j", $thetime);
     $durtime = strtotime($row['duration']);
     $durhr = date("G", $durtime) - $hour;
     $durmin = date("i", $durtime) - $minute;
     $durday = date("j", $durtime) - $day;
     $durmon = date("n", $durtime) - $month;
     if($durmin < 0) {
         $durmin = $durmin + 60;
         $durhr = $durhr - 1;
     }
     if($durhr < 0) {
         $durhr = $durhr + 24;
         $durday = $durday - 1;
     }
     if($durmon > 0) $durday = $durday + date("t", $thetime);
     if($hour >= 12) {
         $pm = 1;
         $hour = $hour - 12;
     } else $pm = 0;
     $typeofevent = $row['eventtype'];
     
 case "Add Item":
     if($action == "Add Item") {
         $headerstring = "Adding item to calendar";
         
         $result = mysql_query("SELECT max(id) as id FROM $mysql_tablename");
         if($result) {
             $row = mysql_fetch_array($result);
             $id = $row['id'] + 1;
         } else {
             $id = 0;
         }
         
         $username = "";
         $subject = "";
         $desc = "";
         if($day == date("j") && $month == date("n") 
            && $year == date("Y")) {
             $hour = date("G") + 1;
             if($hour >= 12) {
                 $hour = $hour - 12;
                 $pm = 1;
             } else $pm = 0;
         } else { $hour = 6; $pm = 1; }
         $minute = 0;
         $durhr = 1;
         $durday = 0;
         $durmin = 0;
         $typeofevent = 1;
     }
   
     echo "<form method=\"get\" action=\"operate.php\">\n";
     if(isold()) {
         echo "<table cellspacing=\"0\" cellpadding=\"1\" border=\"0\" width=\"96%\"><tr><td bgcolor=\"$bordercolor\">
<table class=\"box\" cellspacing=\"0\" border=\"0\" cellpadding=\"2\" width=\"100%\"";
     } else {
         echo "<table class=\"box\"";
         if($BName == "MSIE") {
             echo " cellspacing=\"0\"";
         }
     }
     echo <<<END
>
  <thead>
  <tr>
    <th colspan="2">$headerstring</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td>Name</td>
    <td><input type="text" name="username" size="20" value="$username" /></td>
  </tr>
  <tr><td>Day</td>
    <td>
      <select name="day" size="1">\n
END;
     
     $lastday = date("t", mktime(0,0,0,$month,1,$year));
     for ($i = 1; $i <= $lastday; $i++){
         if ($i == $day) {
             echo "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
         } else {
             echo "        <option value=\"$i\">$i</option>\n";
         }
     }
     
     echo "      </select>\n      <select size=\"1\" name=\"month\">\n";

     for ($i=1; $i<13; $i++) {
         $nm = date("F", mktime(0,0,0,$i,1,$year));
         if ($i == $month) {
             echo "        <option value=\"$i\" selected=\"selected\">$nm</option>\n";
         } else {
             echo "        <option value=\"$i\">$nm</option>\n";
         }
     }
     
     echo "      </select>\n      <select size=\"1\" name=\"year\">";
     
     for ($i=$year-2; $i<$year+5; $i++) {
         if ($i == $year) {
             echo "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
         } else {
             echo "        <option value=\"$i\">$i</option>\n";
         }
     }
     
     echo "      </select></td>
  </tr>
  <tr>
    <td>Event Type</td>
    <td>
<select name=\"typeofevent\" size=\"1\">
<option value=\"1\"";

     if($typeofevent == 1) {
         echo " selected=\"selected\"";
     }
     
     echo ">Normal Event</option>
<option value=\"2\"";

     if($typeofevent == 2) {
         echo " selected=\"selected\"";
     }
     
     echo ">Full Day Event</option>
<option value=\"3\"";
     
     if($typeofevent == 3) {
         echo " selected=\"selected\"";
     }
     
     echo ">Unkown Time</option>
</select>
    </td>
  </tr>
  <tr>
    <td>Time</td>
    <td>
<select name=\"hour\" size=\"1\">\n";

     for($i = 1; $i < 12; $i++) {
         echo "<option value='$i'";
         if($hour == $i) {
             echo " selected=\"selected\"";
         }
         echo ">$i</option>\n";
     }

     echo "<option value='0'";

     if($hour == 0) {
         echo " selected=\"selected\"";
     }
     echo ">12</option>
</select><b>:</b><select name=\"minute\" size=\"1\">\n";

     for($i = 0; $i <= 59; $i = $i + 5) {
         echo "<option value='$i'";
         if($minute >= $i && $i > $minute - 5) {
             echo " selected=\"selected\"";
         }
         printf(">%02d</option>\n", $i);
     }
   
     echo "</select><select name=\"pm\" size=\"1\">
<option value='0'";
     if(!$pm) {
         echo " selected=\"selected\"";
     }
     echo ">AM</option>
<option value='1'";
     if($pm) {
         echo " selected=\"selected\"";
     }
     echo ">PM</option>
</select></td>
  </tr>
  <tr>
    <td>Duration</td>
    <td>
<select name=\"durationday\" size=\"1\">";

     for($i = 0; $i < 31; $i++) {
         echo "<option value='$i'";
         if($durday == $i) {
             echo " selected=\"selected\"";
         }
         echo ">$i</option>\n";
     }
     echo "</select>
days
<select name=\"durationhour\" size=\"1\">";
     for($i = 0; $i < 24; $i++) {
         echo "<option value='$i'";
         if($durhr == $i) {
             echo " selected=\"selected\"";
         }
         echo ">$i</option>\n";
     }
     echo "</select>
hours
<select name=\"durationmin\" size=\"1\">";
     for($i = 0; $i <= 59; $i = $i + 5) {
         echo "<option value='$i'";
         if($durmin >= $i && $i > $durmin - 5) {
             echo " selected=\"selected\"";
         }
         printf(">%02d</option>\n", $i);
     }
     echo "</select>
minutes
</td>
  </tr>
  <tr>
    <td>Subject (255 chars max)</td>
    <td><input type=\"text\" name=\"subject\" value=\"$subject\" /></td>
  </tr>
  <tr>
    <td>Description</td>
    <td>
      <textarea rows=\"5\" cols=\"50\" name=\"description\">$desc</textarea>
    </td>
  </tr>
  <tr>
    <td colspan=\"2\" style=\"text-align:center\">
      <input type=\"hidden\" name=\"action\" value=\"Addsucker\" />
      <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
   
     if(isset($modify)) {
         echo "<input type=\"hidden\" name=\"modify\" value=\"1\" />\n";
     }
     
     echo "<input type=\"submit\" value=\"Submit item\" />
    </td>
  </tr>
  </tbody>
</table>
</form>\n";
     break;
   
 case "Addsucker":
     if(empty($_GET['id'])) {
       echo "<div class=\"box\">No ID given.</div>";
       break;
     } else {
       $id = $_GET['id'];
     }
     
     if (isset($_GET['modify'])) {
         mysql_query("DELETE FROM $mysql_tablename WHERE id = '$id'")
             or die("couldn't delete item");
         if(mysql_affected_rows() == 0) {
             echo "<div class=\"box\">Item already deleted.</div>";
             break;
         }
     } else {
         $query = "SELECT max(id) as id FROM $mysql_tablename";
         $result = mysql_query($query);
         if($result) {
             $row = mysql_fetch_array($result);
             if($id != $row['id'] + 1) {
                 echo "<div class=\"box\">Item already created</div>";
                 break;
             }
         }
     }
     
     if($_GET['description']) {
       $description = ereg_replace("<[bB][rR][^>]*>", "\n", 
         $_GET['description']);
     } else {
       $description = '';
     }
     
     if($_GET['subject']) {
       $subject = addslashes(ereg_replace("<[^>]*>", "", $_GET['subject']));
     } else {
       $subject = '';
     }

     if($_GET['username']) {
       $username = addslashes(ereg_replace("<[^>]*>", "", $_GET['username']));
     } else {
       $username = '';
     }

     if($_GET['description']) {
       $description = addslashes(ereg_replace("</?([^aA/]|[a-zA-Z_]{2,})[^>]*>",
       "", $_GET['description']));
     } else {
       $description = '';
     }
     
     if(isset($_GET['hour'])) $hour = $_GET['hour'];
     else $hour = 0;
     
     if(isset($_GET['pm']) && $_GET['pm'] == 1) $hour += 12;
     
     if(isset($_GET['minute'])) $minute = $_GET['minute'];
     else $minute = 0;

     if(isset($_GET['durationhour'])) $durationhour = $_GET['durationhour'];
     else $durationhour = 1;

     if(isset($_GET['durationmin'])) $durationmin = $_GET['durationmin'];
     else $durationmin = 0;
     
     if(isset($_GET['durationday'])) $durationday = $_GET['durationday'];
     else $durationday = 0;
     
     if(isset($_GET['typeofevent'])) $typeofevent = $_GET['typeofevent'];
     else $typeofevent = 0;

     $timestamp = date("Y-m-d H:i:s", mktime($hour,$minute,0,$month,$day,$year));
     $durationstamp = date("Y-m-d H:i:s", mktime($hour+$durationhour,$minute+$durationmin,0,$month,$day+$durationday,$year));
     ;
     $result = mysql_query("INSERT INTO $mysql_tablename (username, stamp, subject, description, eventtype, duration) VALUES ('$username', '$timestamp', '$subject', '$description', '$typeofevent', '$durationstamp')");
     if ($result)
         echo "<div class=\"box\">Item added ...</div>";
     else {
         echo "<div class=\"box\">Item may not have been added ...", mysql_error(), "</div>";
     }
     
     break;
}

echo "<div>
  <a class=\"box\" href=\"display.php?month=$month&amp;year=$year&amp;day=$day\">View date</a>
  <a class=\"box\" href=\"index.php?month=$month&amp;year=$year\">Back to Calendar</a>
</div>";

bottom();
?>
