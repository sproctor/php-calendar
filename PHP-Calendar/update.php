<?
include ("config.php");
include ("header.php");

{
    $database = mysql_connect($mysql_hostname, $mysql_username, 
                              $mysql_password) 
        or die("Could not connect to server");

    mysql_select_db($mysql_database)
        or die("Could not select $mysql_database");

    $query = "ALTER TABLE $mysql_tablename 
  ADD duration datetime AFTER stamp,
  ADD eventtype int(4) AFTER duration;";
    $result = mysql_query($query)
        or die(mysql_error());

    $query = "UPDATE $mysql_tablename
  SET duration=stamp, eventtype=1;";
    $result = mysql_query($query)
        or die(mysql_error());

    mysql_close($database);
}
include("footer.php");
?>
