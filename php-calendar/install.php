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
<body>
<form method="post" action="install.php">
';

function get_config()
{
	if(is_writeable('config.inc.php') || is_writeable('.')) {
		echo '<input type="hidden" name="config" value="1">
			<p>your config file is writable</p>
			<input type="submit" value="continue">';
	} else {
		echo '<p>your config file is not readable.  I suggest logging in with a shell and typing:</p>
			<p><code>
			touch config.inc.php<br>
			chmod 666 config.inc.php
			</code></p>
			<p>or if you only have ftp access, upload a blank file named config.inc.php then use the chmod command to change the permissions of config.inc.php to 666</p>
			<input type="submit" value="retry">';
	}
}

function get_user_add()
{
	echo '<p>Have you already created the user for your database?</p>
		<input type="submit" name="has_user" value="yes">
		<input type="submit" name="has_user" value="no">';
}

reset($HTTP_POST_VARS);
while(list($key, $value) = each($HTTP_POST_VARS)) {
	echo "<input name=\"$key\" value=\"$value\" type=\"hidden\">";
}

if(!isset($HTTP_POST_VARS['config'])) {
	get_config();
} elseif(!isset($HTTP_POST_VARS['my_hostname'])
		&& !isset($HTTP_POST_VARS['my_username'])
		&& !isset($HTTP_POST_VARS['my_passwd'])
		&& !isset($HTTP_POST_VARS['my_prefix'])
		&& !isset($HTTP_POST_VARS['my_database'])) {
	get_server_setup();
} elseif(!isset($HTTP_POST_VARS['has_user'])) {
	get_user_add();
} elseif(!isset($HTTP_POST_VARS['my_adminname'])
		&& !isset($HTTP_POST_VARS['my_adminpassword'])
		&& $HTTP_POST_VARS['has_user'] == 'no') {
	user_add_do();
} else {
finalize_install();
}

function get_server_setup()
{
	/* ignore this comment.  it should be setting stuff up to give some info, but I'm lazy so FIXME
	   !isset($HTTP_POST_VARS['my_hostname'])
	   || !isset($HTTP_POST_VARS['my_username'])
	   || !isset($HTTP_POST_VARS['my_passwd'])
	   || !isset($HTTP_POST_VARS['my_prefix'])
	   || !isset($HTTP_POST_VARS['my_database'])) {
	 */
	echo '
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
		<td><input type="text" name="my_prefix" value="phpc_"></td>
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
		<td colspan="2"><input name="action" type="submit" value="Install"></td>
		</tr>
		<tr>
		<td><input type="checkbox" name="create_db" value="1">
		create the database (don\'t check this if it already exists)
		</td>
		</tr>
		</table>';
}

function user_add_do()
{
	global $HTTP_POST_VARS;

	$my_hostname = $HTTP_POST_VARS['my_hostname'];
	$my_username = $HTTP_POST_VARS['my_username'];
	$my_passwd = $HTTP_POST_VARS['my_passwd'];
	$my_prefix = $HTTP_POST_VARS['my_prefix'];
	$my_database = $HTTP_POST_VARS['my_database'];

	$link = mysql_connect($my_hostname, $HTTP_POST_VARS['my_adminname'], $HTTP_POST_VARS['my_adminpassword'])
		or die("Could not connect");

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

	if(!empty($HTTP_POST_VARS['create_db'])) {
		$sql = "CREATE DATABASE $my_database";
		if(!mysql_query($sql) and mysql_errno() != "1007")
			die('create db:'.mysql_errno().': '.mysql_error().': '.$sql);
	}

	mysql_query("GRANT SELECT, INSERT, UPDATE, DELETE ON $my_prefix"."events TO $my_username;")
		or die("Could not grant");

	mysql_query("FLUSH PRIVILEGES;")
		or die("Could not flush privileges");

}

function finalize_install()
{
	global $HTTP_POST_VARS;
	$my_hostname = $HTTP_POST_VARS['my_hostname'];
	$my_username = $HTTP_POST_VARS['my_username'];
	$my_passwd = $HTTP_POST_VARS['my_passwd'];
	$my_prefix = $HTTP_POST_VARS['my_prefix'];
	$my_database = $HTTP_POST_VARS['my_database'];

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

	$link = mysql_connect($my_hostname, $my_username, $my_passwd)
		or die("Could not connect");

	if(!empty($HTTP_POST_VARS['create_db'])
			&& $HTTP_POST_VARS['has_user'] == 'yes') {
		$sql = "CREATE DATABASE $my_database";
		if(!mysql_query($sql) and mysql_errno() != "1007")
			die('create db:'.mysql_errno().': '.mysql_error().': '.$sql);
	}
	mysql_select_db($my_database)
		or die("Could not select $my_database");

	$query = "CREATE TABLE $my_prefix"."events (\n"
			."id int(11) DEFAULT '0' NOT NULL auto_increment,\n"
			."username varchar(255),\n"
			."stamp datetime,\n"
			."duration datetime,\n"
			."eventtype int(4),\n"
			."subject varchar(255),\n"
			."description text,\n"
			."PRIMARY KEY (id)\n"
			.")";
echo "<pre>$query</pre>";
	mysql_query($query)
		or die("Could not create table");

	mysql_close($link);

	echo "<p><a href=\".\">Calendar created</a></p>";
}

echo '</form></body></html>';
?>
