<?php
/*
   Copyright 2002 Sean Proctor, Nathan Poiro

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

$phpc_root_path = './';
$calendar_name = '0';

define('IN_PHPC', 1);

// SQL codes
define('BEGIN_TRANSACTION', 1);
define('END_TRANSACTION', 2);

echo '<html>
<head>
<title>install php calendar</title>
</head>
<body>
<form method="post" action="install.php">
';

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
	get_sql_user();
} elseif(!isset($HTTP_POST_VARS['my_adminname'])
		&& !isset($HTTP_POST_VARS['my_adminpassword'])
		&& $HTTP_POST_VARS['has_user'] == 'no') {
	add_sql_user();
} elseif(!isset($HTTP_POST_VARS['base'])) {
	install_base();
} elseif(!isset($HTTP_POST_VARS['admin_user'])
		&& !isset($HTTP_POST_VARS['admin_pass'])) {
	get_admin();
} else {
	add_calendar();
}

function get_config()
{
	global $phpc_root_path;

	if(is_writeable($phpc_root_path . 'config.php')
			|| is_writeable($phpc_root_path)) {
		echo '<input type="hidden" name="config" value="1">
			<p>your config file is writable</p>
			<input type="submit" value="continue">';
	} else {
		echo '<p>your config file is not writeable.  I suggest logging in with a shell and typing:</p>
			<p><code>
			touch config.php<br>
			chmod 666 config.php
			</code></p>
			<p>or if you only have ftp access, upload a blank file named config.php then use the chmod command to change the permissions of config.php to 666</p>
			<input type="submit" value="retry">';
	}
}

function get_sql_user()
{
	echo '<p>Have you already created the user for your database?</p>
		<input type="submit" name="has_user" value="yes">
		<input type="submit" name="has_user" value="no">';
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
		<td>SQL server hostname:</td>
		<td><input type="text" name="my_hostname" value="localhost"></td>
		</tr>
		<tr>
		<td>SQL Database name:</td>
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
		<tr>
		<td>Database type:</td>
		<td><select name="sql_type">
		<option value="mysql">MySQL 3.x</option>
		<option value="postgres7">PostgreSQL 7.x</option>
		</select>
		</td>
		</tr>
		</table>';
}

function add_sql_user()
{
	global $HTTP_POST_VARS;

	$my_hostname = $HTTP_POST_VARS['my_hostname'];
	$my_username = $HTTP_POST_VARS['my_username'];
	$my_passwd = $HTTP_POST_VARS['my_passwd'];
	$my_prefix = $HTTP_POST_VARS['my_prefix'];
	$my_database = $HTTP_POST_VARS['my_database'];
	$my_adminname = $HTTP_POST_VARS['my_adminname'];
	$my_adminpasswd = $HTTP_POST_VARS['my_adminpassword'];

	switch($HTTP_POST_VARS['sql_type']) {
		case 'mysql':
			$link = $mysql_connect($my_hostname, $my_adminname, $my_adminpasswd)
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
				create_db($my_hostname, $my_adminname,
				$my_adminpasswd, $my_database,
				$HTTP_POST_VARS['sql_type']);
			}

			mysql_query("GRANT SELECT, INSERT, UPDATE, DELETE ON $my_prefix"."events TO $my_username;")
				or die("Could not grant");

			mysql_query("FLUSH PRIVILEGES;")
				or die("Could not flush privileges");

		default:
			die('we don\'t support creating users for this database type yet');
	}
}

function create_db($my_hostname, $my_username, $my_passwd, $my_database,
		$sql_type)
{
	global $phpc_root_path;

	include($phpc_root_path . "db/$sql_type.php");

	$db = new sql_db($my_hostname, $my_username, $my_passwd, '');

	$sql = "CREATE DATABASE $my_database";

	if(!$db->sql_query($sql)) {
		$error = $db->sql_error();
		if($error['code'] != '1007') {
			die(_('error creating db')
					.": $error[code]: $error[message]: $sql");
		}
	}
}

function create_dependent($dbms)
{
	global $db;

	$sequence = SQL_PREFIX . 'sequence';

	$query = array();

	switch($dbms) {
		case 'mysql':
			$query[] = "CREATE TABLE $sequence (id integer DEFAULT '0' AUTO_INCREMENT, PRIMARY KEY(id))";
			break;
		default:
			$query[] = "CREATE SEQUENCE $sequence";
			$query[] = "CREATE FUNCTION dayofweek(date) RETURNS double precision AS ' SELECT EXTRACT(DOW FROM \$1); ' LANGUAGE SQL;";
			$query[] = "CREATE FUNCTION dayofmonth(date) RETURNS double precision AS ' SELECT EXTRACT(DAY FROM \$1); ' LANGUAGE SQL;";
			$query[] = "CREATE FUNCTION year(date) RETURNS double precision AS ' SELECT EXTRACT(YEAR FROM \$1); ' LANGUAGE SQL;";
			$query[] = "CREATE FUNCTION month(date) RETURNS double precision AS ' SELECT EXTRACT(MONTH FROM \$1); ' LANGUAGE SQL;";

	}

	reset($query);
	while(list(,$q) = each($query)) {
		$result = $db->sql_query($q);
		$result = 1;
		if(!$result) {
			$error = $db->sql_error();
			die("error in sequence: $error[code]: $error[message]:<pre>$query</pre>");
		}
	}
}

function install_base()
{
	global $HTTP_POST_VARS, $phpc_root_path, $db;

	$sql_type = $HTTP_POST_VARS['sql_type'];
	$my_hostname = $HTTP_POST_VARS['my_hostname'];
	$my_username = $HTTP_POST_VARS['my_username'];
	$my_passwd = $HTTP_POST_VARS['my_passwd'];
	$my_prefix = $HTTP_POST_VARS['my_prefix'];
	$my_database = $HTTP_POST_VARS['my_database'];

	$fp = fopen("$phpc_root_path/config.php", 'w')
		or die('Couldn\'t open config file.');

	$fstring = "<?php\n"
		."define('SQL_HOST', '$my_hostname');\n"
		."define('SQL_USER', '$my_username');\n"
		."define('SQL_PASSWD', '$my_passwd');\n"
		."define('SQL_DATABASE', '$my_database');\n"
		."define('SQL_PREFIX',   '$my_prefix');\n"
		."\$dbms = '$sql_type';\n"
		."?>";

	fwrite($fp, $fstring)
		or die("could not write to file");
	fclose($fp);

	if(!empty($HTTP_POST_VARS['create_db'])
			&& $HTTP_POST_VARS['has_user'] == 'yes') {
		create_db($my_hostname, $my_username, $my_passwd, $my_database,
				$sql_type);
	}

	include($phpc_root_path . 'config.php');
	include($phpc_root_path . 'includes/db.php');

	create_tables();

	create_dependent($sql_type);

	echo "<p>calendars base created</p>\n"
		."<div><input type=\"submit\" name=\"base\" value=\"continue\">"
		."</div>\n";
}

function create_tables()
{
	global $db;

	$query = array();

	$query[] = "CREATE TABLE ".SQL_PREFIX."events (\n"
		."id integer NOT NULL,\n"
		."uid integer,\n"
		."startdate date,\n"
		."enddate date,\n"
		."starttime time,\n"
		."duration integer,\n"
		."eventtype integer,\n"
		."subject varchar(255),\n"
		."description text,\n"
		."calendar varchar(32)\n"
		.")";

	$query[] = "CREATE TABLE ".SQL_PREFIX."users (\n"
		."calendar varchar(32) NOT NULL default '0',\n"
		."uid integer NOT NULL,\n"
		."username varchar(32) NOT NULL,\n"
		."password varchar(32) NOT NULL default '',\n"
		."PRIMARY KEY (calendar, uid))";

	$query[] = "CREATE TABLE ".SQL_PREFIX."calendars (\n"
		."calendar varchar(32) NOT NULL,\n"
		."hours_24 integer NOT NULL default '0',\n"
		."start_monday integer NOT NULL default '0',\n"
		."translate integer NOT NULL default '0',\n"
		."anon_permission integer NOT NULL default '0',\n"
		."subject_max integer NOT NULL default '32',\n"
		."contact_name varchar(255) default NULL,\n"
		."contact_email varchar(255) default NULL,\n"
		."calendar_title varchar(255) NOT NULL default '',\n"
		."URL varchar(200) default NULL,\n"
		."PRIMARY KEY (calendar)\n"
		.")";

	reset($query);
	while(list(, $sql) = each($query)) {
		$result = $db->sql_query($sql);

		if(!$result) {
			$error = $db->sql_error();
			die("Error creating table: $error[code]: "
					."$error[message]:<pre>$sql</pre>");
		}
	}
}

function get_admin()
{

	echo "<table><tr><td>\n"
		."Admin name:\n"
		."</td><td>\n"
		."<input type=\"text\" name=\"admin_user\" />\n"
		."</td></tr><tr><td>\n"
		."Admin password:"
		."</td><td>\n"
		."<input type=\"password\" name=\"admin_pass\" />\n"
		."</td></tr><tr><td colspan=\"2\">"
		."<input type=\"submit\" value=\"Create Admin\" />\n"
		."</td></tr></table>\n";

}

function add_calendar()
{
	global $HTTP_POST_VARS, $db, $phpc_root_path, $calendar_name;

	include($phpc_root_path . 'config.php');
	include($phpc_root_path . 'includes/db.php');

	$hours_24 = 0;
	$start_monday = 0;
	$translate = 1;
	$calendar_title = 'PHP-Calendar';

	$query = "INSERT INTO ".SQL_PREFIX."calendars (calendar, hours_24, "
	."start_monday, translate, subject_max, calendar_title) "
	."VALUES ('$calendar_name', '$hours_24', '$start_monday', '$translate',"
	." '32', '$calendar_title')";

	$result = $db->sql_query($query);

	if(!$result) {
		$error = $db->sql_error();
		die("Couldn't create calendar. $error[code]: $error[message]<pre>$query</pre>");
	}

	echo "<p>calendar created</p>\n";

	$passwd = md5($HTTP_POST_VARS['admin_pass']);

	$query = "insert into ".SQL_PREFIX."users\n"
		."(uid, username, password, calendar) VALUES\n"
		."('1', '$HTTP_POST_VARS[admin_user]', '$passwd',"
		." $calendar_name)";

	$result = $db->sql_query($query);
	if(!$result) {
		$error = $db->sql_error();
		die("Could not add admin: $error[code]: $error[message]:\n<pre>$query</pre>");
	}
	
	$passwd = md5($HTTP_POST_VARS['admin_pass']);

	$query = "insert into ".SQL_PREFIX."users\n"
		."(uid, username, password, calendar) VALUES\n"
		."('0', 'anonymous', '$passwd', $calendar_name)";

	$result = $db->sql_query($query);
	if(!$result) {
		$error = $db->sql_error();
		die("Could not add admin: $error[code]: $error[message]:\n<pre>$query</pre>");
	}

	echo "<p>admin added; <a href=\"index.php\">View calendar</a></p>";
	echo '<p>you should delete install.php now and chmod 444 config.php</p>';
}

echo '</form></body></html>';
?>
