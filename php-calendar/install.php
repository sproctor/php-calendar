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

/*
   Run this file to install the calendar
   it needs very much work
*/

$phpc_root_path = './';
$calendar_name = '0';

define('IN_PHPC', 1);

// SQL codes
define('BEGIN_TRANSACTION', 1);
define('END_TRANSACTION', 2);

// include adodb from the local path
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        ini_set ('include_path', ini_get ('include_path').';'.$phpc_root_path
                        .'adodb');
} else {
        ini_set ('include_path', ini_get ('include_path').':'.$phpc_root_path
                        .'adodb');
}

require_once($phpc_root_path . 'includes/calendar.php');
require_once('adodb.inc.php');

echo '<html>
<head>
<title>install php calendar</title>
</head>
<body>
<form method="post" action="install.php">
';

foreach($_POST as $key => $value) {
	echo "<input name=\"$key\" value=\"$value\" type=\"hidden\">";
}

if(!isset($_POST['config'])) {
	get_config();
} elseif(!isset($_POST['my_hostname'])
		&& !isset($_POST['my_username'])
		&& !isset($_POST['my_passwd'])
		&& !isset($_POST['my_prefix'])
		&& !isset($_POST['my_database'])) {
	get_server_setup();
} elseif((isset($_POST['create_user']) || isset($_POST['create_db']))
		&& !isset($_POST['done_user_db'])) {
	add_sql_user_db();
} elseif(!isset($_POST['base'])) {
	install_base();
} elseif(!isset($_POST['admin_user'])
		&& !isset($_POST['admin_pass'])) {
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

function get_server_setup()
{
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
		<td>Database type:</td>
		<td><select name="sql_type">
		<option value="mysql">MySQL</option>
		<option value="postgres7">PostgreSQL 7.x</option>
		</select>
		</td>
		</tr>
		<tr>
		<td colspan="2">
		  <input type="checkbox" name="create_db" value="yes">
		  create the database (don\'t check this if it already exists)
		</td>
		</tr>
		<tr><td colspan="2">
		  <input type="checkbox" name="create_user" value="yes">
		  Should the user info supplied above be created? Do not check
		  this if the user already exists
		</td></tr>
		<tr><td colspan="2">
		  You only need to provide the following information if you
		  need to create a user
		</td></tr>
		<tr>
		<td>Admin name:</td>
		<td><input type="text" name="my_adminname"></td>
		</tr>
		<tr>
		<td>Amind Password:</td>
		<td><input type="password" name="my_adminpassword"></td>
		</tr>
		<tr>
		<td colspan="2">
		  <input name="action" type="submit" value="Install">
		</td>
		</tr>
		</table>';
}

function add_sql_user_db()
{
	global $db;

	$my_hostname = $_POST['my_hostname'];
	$my_username = $_POST['my_username'];
	$my_passwd = $_POST['my_passwd'];
	$my_prefix = $_POST['my_prefix'];
	$my_database = $_POST['my_database'];
	$my_adminname = $_POST['my_adminname'];
	$my_adminpasswd = $_POST['my_adminpassword'];
	$sql_type = $_POST['sql_type'];

	$create_user = isset($_POST['create_user'])
		&& $_POST['create_user'] = 'yes';
	$create_db = isset($_POST['create_db']) && $_POST['create_db'] = 'yes';

	$db = NewADOConnection($sql_type);

	$string = "";

	if($create_user) {
		$db->Connect($my_hostname, $my_adminname, $my_adminpasswd, '');
	} else {
		$db->Connect($my_hostname, $my_username, $my_passwd, '');
	}

	if($create_db) {
		$sql = "CREATE DATABASE $my_database";

		$db->Execute($sql)
			or db_error(_('error creating db'), $sql);

		$string .= "<div>Successfully created database</div>";
	}

	if($create_user) {
		switch($sql_type) {
			case 'mysql':
				$sql = "GRANT ALL ON accounts.* TO $my_username@$my_hostname identified by '$my_passwd'";
				$db->Execute($sql)
					or db_error(_('Could not grant:'), $sql);
				$sql = "GRANT ALL ON $my_database.* to $my_username identified by '$my_passwd'";
				$db->Execute($sql)
					or db_error(_('Could not grant:'), $sql);

				$sql = "FLUSH PRIVILEGES";
				$db->Execute($sql)
					or db_error("Could not flush privileges", $sql);

				$string .= "<div>Successfully added user</div>";

				break;

			default:
				die('we don\'t support creating users for this database type yet');

		}
	}

	echo "$string\n"
		."<div><input type=\"submit\" name=\"done_user_db\" value=\"continue\">"
		."</div>\n";

}

function install_base()
{
	global $phpc_root_path, $db;

	$sql_type = $_POST['sql_type'];
	$my_hostname = $_POST['my_hostname'];
	$my_username = $_POST['my_username'];
	$my_passwd = $_POST['my_passwd'];
	$my_prefix = $_POST['my_prefix'];
	$my_database = $_POST['my_database'];

	$fp = fopen("$phpc_root_path/config.php", 'w')
		or die('Couldn\'t open config file.');

	$fstring = "<?php\n"
		."define('SQL_HOST',     '$my_hostname');\n"
		."define('SQL_USER',     '$my_username');\n"
		."define('SQL_PASSWD',   '$my_passwd');\n"
		."define('SQL_DATABASE', '$my_database');\n"
		."define('SQL_PREFIX',   '$my_prefix');\n"
		."define('SQL_TYPE',     '$sql_type');\n"
		."?>\n";

	fwrite($fp, $fstring)
		or die("could not write to file");
	fclose($fp);

	require_once($phpc_root_path . 'config.php');
	require_once($phpc_root_path . 'includes/db.php');

	create_tables();

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

	foreach($query as $sql) {
		$result = $db->Execute($sql);

		if(!$result) {
			db_error("Error creating table:", $sql);
		}
	}
}

function get_admin()
{

	echo "<table>\n"
		."<tr><td colspan=\"2\">The following is to log in to the "
		."calendar (not the SQL admin)</td></tr>\n"
		."<tr><td>\n"
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
	global $db, $phpc_root_path, $calendar_name;

	require_once($phpc_root_path . 'config.php');
	require_once($phpc_root_path . 'includes/db.php');

	$hours_24 = 0;
	$start_monday = 0;
	$translate = 1;
	$calendar_title = 'PHP-Calendar';

	$query = "INSERT INTO ".SQL_PREFIX."calendars (calendar, hours_24, "
	."start_monday, translate, subject_max, calendar_title) "
	."VALUES ('$calendar_name', '$hours_24', '$start_monday', '$translate',"
	." '32', '$calendar_title')";

	$result = $db->Execute($query);

	if(!$result) {
		db_error("Couldn't create calendar.", $query);
	}

	echo "<p>calendar created</p>\n";

	$query = "insert into ".SQL_PREFIX."users\n"
		."(uid, username, password, calendar) VALUES\n"
		."('".$db->GenID('uid', 0)."', 'anonymous', '',"
                ." $calendar_name)";

	$result = $db->Execute($query);
	if(!$result) {
		db_error("Could not add anonymous user:", $query);
	}

	$passwd = md5($_POST['admin_pass']);

	$query = "insert into ".SQL_PREFIX."users\n"
		."(uid, username, password, calendar) VALUES\n"
		."('".$db->GenID('uid')."', '$_POST[admin_user]', '$passwd',"
		." $calendar_name)";

	$result = $db->Execute($query);
	if(!$result) {
		db_error("Could not add admin:", $query);
	}
	
	echo "<p>admin added; <a href=\"index.php\">View calendar</a></p>";
	echo '<p>you should delete install.php now and chmod 444 config.php</p>';
}

echo '</form></body></html>';
?>
