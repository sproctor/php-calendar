<?php
/*
   Copyright 2002 Sean Proctor

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

echo '<html>
<head>
<title>install php calendar</title>
</head>
<html>
';

if(!isset($action)) {
	echo '<form method="get" action="install.php">
		<table class="display">
		<tr>
		<td>MySQL hostname:</td>
		<td><input type="text" name="my_hostname" value="localhost"></td>
		</tr>
		<tr>
		<td>Database name:</td>
		<td><input type="text" name="my_database" value="calendar"></td>
		</tr>
		<tr>
		<td>Table prefix:</td>
		<td><input type="text" name="my_tablename" value="phpc_"></td>
		</tr>
		<tr>
		<td>Username:</td>
		<td><input type="text" name="my_username" value="calendar"></td>
		</tr>
		<tr>
		<td>Password:</td>
		<td><input type="password" name="my_passwd"></td>
		</tr>
		<tr>
		<td>MySQL admin user:</td>
		<td><input type="text" name="admin_username" value="root"></td>
		<tr>
		<td>MySQL admin password:</td>
		<td><input type="password" name="admin_passwd"></td>
		</tr>
		<tr>
		<td colspan="2"><input name="action" type="submit" value="Install"></td>
		</tr>
		</table>
		</form>';
} else {
	if(empty($HTTP_GET_VARS['my_hostname'])
			|| empty($HTTP_GET_VARS['my_username'])
			|| empty($HTTP_GET_VARS['my_passwd'])
			|| empty($HTTP_GET_VARS['my_tablename'])
			|| empty($HTTP_GET_VARS['my_database'])) {
		exit('You didn\'t fill in all the values.');
	}

	$my_hostname = $HTTP_GET_VARS['my_hostname'];
	$my_username = $HTTP_GET_VARS['my_username'];
	$my_passwd = $HTTP_GET_VARS['my_passwd'];
	$my_prefix = $HTTP_GET_VARS['my_tablename'];
	$my_database = $HTTP_GET_VARS['my_database'];
	$admin_username = $HTTP_GET_VARS['admin_username'];
	$admin_passwd = $HTTP_GET_VARS['admin_passwd'];

	$fp = fopen('config.inc.php', 'w')
		or die('Couldn\'t open config file.');

	$fstring = "<?php\n"
		."define('SQL_HOSTNAME', '$my_hostname');\n"
		."define('SQL_USERNAME', '$my_username');\n"
		."define('SQL_PASSWORD', '$my_passwd');\n"
		."define('SQL_DATABASE', '$my_database');\n"
		."define('SQL_PREFIX',   '$my_prefix');\n"
		."define('TITLE',        'PHP-Calendar 0.8');\n"
		."define('START_MONDAY', 0);\n"
		."define('HOURS_24',     0);\n"
		."define('TRANSLATE',    1);\n"
		."?>";

	fwrite($fp, $fstring)
		or die("could not write to file");
	fclose($fp);

	$database = mysql_connect($my_hostname, $admin_username, $admin_passwd)
		or die("Could not connect to server");

	$sql = "CREATE DATABASE $my_database";
	if(!mysql_query($sql) and mysql_errno() != "1007")
		die('create db:'.mysql_errno().': '.mysql_error().': '.$sql);

	mysql_select_db("mysql")
		or die("could not select mysql");

	mysql_query("REPLACE INTO user (host, user, password)\n"
			."VALUES (\n"
			."'$my_hostname',\n"
			."'$my_username',\n"
			."password('$my_passwd')\n"
			.");")
		or die("Could not add user");

	mysql_query("REPLACE INTO db (host, db, user, select_priv, "
			."insert_priv, update_priv, delete_priv, "
			."create_priv, drop_priv)\n"
			."VALUES (\n"
			."'$my_hostname',\n"
			."'$my_database',\n"
			."'$my_username',\n"
			."'Y', 'Y', 'Y', 'Y', 'Y', 'Y'\n"
			.");") or die("Could not change privileges"); 

	mysql_select_db($my_database)
		or die("Could not select $my_database");

	mysql_query("CREATE TABLE $my_prefix"."events (\n"
			."id int(11) DEFAULT \'0\' NOT NULL auto_increment,\n"
			."username varchar(255),\n"
			."stamp datetime,\n"
			."duration datetime,\n"
			."eventtype int(4),\n"
			."subject varchar(255),\n"
			."description text,\n"
			."PRIMARY KEY (id)\n"
			.")")
		or die("Could not create table");

	mysql_query("GRANT SELECT, INSERT, UPDATE, DELETE ON $my_prefix"."events TO $my_username;")
		or die("Could not grant");

	mysql_query("FLUSH PRIVILEGES;")
		or die("Could not flush privileges");

	mysql_close($database);

	echo "<p><a href=\".\">Calendar created</a></p>";
}
echo '</html>';
?>
