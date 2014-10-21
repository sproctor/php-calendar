<?php
/*
 * Copyright 2014 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
   Run this file to install the calendar
   it needs very much work
*/

$phpc_root_path = dirname(__FILE__);
$phpc_includes_path = "$phpc_root_path/includes";
$phpc_config_file = "$phpc_root_path/config.php";

define('IN_PHPC', true);

if(!function_exists("mysqli_connect"))
	soft_error("You must have the mysqli extension for PHP installed to use this calendar.");

require_once("$phpc_includes_path/dbversion.php");
require_once("$phpc_includes_path/calendar.php");

?><!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" type="text/css" href="static/phpc.css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <title>PHP Calendar Installation</title>
  </head>
<body>
<h1>PHP Calendar</h1>
<?php

$must_upgrade = false;

if(file_exists($phpc_config_file)) {
	include_once($phpc_config_file);
	if(defined("SQL_HOST")) {
		$dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

		$query = "SELECT *\n"
			."FROM `" . SQL_PREFIX .  "calendars`\n";

		$sth = $dbh->query($query);
		$have_calendar = $sth && $sth->fetch_assoc();

		$existing_version = 0;

		$query = "SELECT `value`\n"
			."FROM `" . SQL_PREFIX .  "config`\n"
			."WHERE `name`='version'";

		$sth = $dbh->query($query);
		if($sth) {
			$result = $sth->fetch_assoc();
			if(!empty($result['value']))
				$existing_version = $result['value'];
		}

		if($have_calendar) {

			$must_upgrade = true;

			if($existing_version > PHPC_DB_VERSION) {
				echo "<p>DB version is newer than the upgrader.</p>";
				exit;
			} elseif($existing_version == PHPC_DB_VERSION) {
				echo '<p>The calendar has already been installed. <a href="index.php">Installed calendar</a></p>';
				echo '<p>If you want to install again, manually delete config.php</p>';
				exit;
			}
		}
	}
}

echo '<p>Welcome to the PHP Calendar installation process.</p>
<form method="post" action="install.php">
';

foreach($_POST as $key => $value) {
	echo "<input name=\"$key\" value=\"$value\" type=\"hidden\">\n";
}

$drop_tables = isset($_POST["drop_tables"]) && $_POST["drop_tables"] == "yes";

if(!isset($_POST['my_hostname'])
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

function check_config()
{
	global $phpc_config_file;

	if(is_writable($phpc_config_file))
		return true;
	
	// Check if we can create the file
	if($file = @fopen($phpc_config_file, 'a')) {
		fclose($file);
		return true;
	}
	return false;
}

function report_config()
{
	echo '<p>Your configuration file could not be written to. This file '
		.'probably does not yet exist. If that is the case, youneed to '
		.'create it. You need to make sure this script can write to '
		.'it. We suggest logging in with a shell and typing:</p>
		<p><pre>
		touch config.php
		chmod 666 config.php
		</pre></p>
		<p>or if you only have ftp access, upload a blank file named '
		.'config.php to your php-calendar directory then use the chmod '
		.'command to change the permissions of config.php to 666.</p>
		<input type="submit" value="Retry"/>';
}

function get_server_setup()
{
	if(!check_config())
		return report_config();

	echo '
		<h3>Step 1: Database</h3>
		<table class="display">
		<tr>
		<td>SQL Server Hostname:</td>
		<td><input type="text" name="my_hostname" value="localhost"></td>
		</tr>
		<tr>
		<td>SQL Database name:</td>
		<td><input type="text" name="my_database" value="calendar"></td>
		</tr>
		<tr>
		<td>SQL Table prefix:</td>
		<td><input type="text" name="my_prefix" value="phpc_"></td>
		</tr>
		<tr>
		<td>SQL Username:</td>
		<td><input type="text" name="my_username" value="calendar"></td>
		</tr>
		<tr>
		<td>SQL Password:</td>
		<td><input type="password" name="my_passwd"></td>
		</tr>
		<tr>
		<td colspan="2">
		  <input type="checkbox" name="create_db" value="yes"/>
		  Create the database (don\'t check this if it already exists)
		</td>
		</tr>
		<tr>
		<td colspan="2">
		  <input type="checkbox" name="drop_tables" value="yes">
		  Drop tables before creating them
		</td>
		</tr>
		<tr><td colspan="2">
		<span style="font-weight:bold;">Optional: user creation on database</span>
		</td></tr>
		<tr><td colspan="2">
		If the credentials supplied above are new, you have to be the database administrator.
		</td></tr>
		<tr><td colspan="2">
		 <input type="checkbox" name="create_user" value="yes">
			Check this if you want to do it and provide admin user and password.
		</td></tr>
		<tr>
		<td>SQL Admin name:</td>
		<td><input type="text" name="my_adminname"></td>
		</tr>
		<tr>
		<td>SQL Admin Password:</td>
		<td><input type="password" name="my_adminpasswd"></td>
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
	$my_hostname = $_POST['my_hostname'];
	$my_username = $_POST['my_username'];
	$my_passwd = $_POST['my_passwd'];
	$my_prefix = $_POST['my_prefix'];
	$my_database = $_POST['my_database'];
	$my_adminname = $_POST['my_adminname'];
	$my_adminpasswd = $_POST['my_adminpasswd'];

	$create_user = isset($_POST['create_user'])
		&& $_POST['create_user'] == 'yes';
	$create_db = isset($_POST['create_db']) && $_POST['create_db'] == 'yes';
	
	// Make the database connection.
	if($create_user) {
		$dbh = connect_db($my_hostname, $my_adminname, $my_adminpasswd);
	} else {
		$dbh = connect_db($my_hostname, $my_username, $my_passwd);
	}

	$string = "<h3>Step 2: Database Setup</h3>";

	if($create_db) {
		$query = "CREATE DATABASE $my_database";

		$dbh->query($query)
			or install_db_error($dbh, 'error creating db', $query);

		$string .= "<p>Successfully created database</p>";
	}
	
	if($create_user) {
		$query = "GRANT ALL ON accounts.* TO $my_username@$my_hostname identified by '$my_passwd'";
		$dbh->query($query)
			or install_db_error($dbh, 'Could not grant:', $query);
		$query = "GRANT ALL ON $my_database.*\n"
			."TO $my_username IDENTIFIED BY '$my_passwd'";
		$dbh->query($query)
			or install_db_error($dbh, 'Could not grant:', $query);

		$query = "FLUSH PRIVILEGES";
		$dbh->query($query)
			or install_db_error($dbh, "Could not flush privileges", $query);

		$string .= "<p>Successfully added user</p>";
	}

	echo "$string\n"
		."<div><input type=\"submit\" name=\"done_user_db\" value=\"continue\"/>"
		."</div>\n";

}

function create_config($sql_hostname, $sql_username, $sql_passwd, $sql_database,
                $sql_prefix, $sql_type)
{
	return "<?php\n"
		."define('SQL_HOST',     '$sql_hostname');\n"
		."define('SQL_USER',     '$sql_username');\n"
		."define('SQL_PASSWD',   '$sql_passwd');\n"
		."define('SQL_DATABASE', '$sql_database');\n"
		."define('SQL_PREFIX',   '$sql_prefix');\n"
		."define('SQL_TYPE',     '$sql_type');\n"
		."?".">\n"; // Break this up to fix syntax HL in vim
}

function install_base()
{
	global $phpc_config_file;

	$sql_type = "mysqli";
	$my_hostname = $_POST['my_hostname'];
	$my_username = $_POST['my_username'];
	$my_passwd = $_POST['my_passwd'];
	$my_prefix = $_POST['my_prefix'];
	$my_database = $_POST['my_database'];

	$fp = fopen($phpc_config_file, 'w')
		or soft_error('Couldn\'t open config file.');

	fwrite($fp, create_config($my_hostname, $my_username, $my_passwd,
                                $my_database, $my_prefix, $sql_type))
		or soft_error("Could not write to file");
	fclose($fp);

	// Make the database connection.
	include($phpc_config_file);
	$dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

	create_tables($dbh);

	$query = "REPLACE INTO `" . SQL_PREFIX . "config`\n"
		."(`name`, `value`)\n"
		."VALUES ('version', '".PHPC_DB_VERSION."')";

	$dbh->query($query)
		or install_db_error($dbh, 'Error creating version row.', $query);

	echo "<p>Config file created at \"". realpath($phpc_config_file) ."\"</p>"
		."<p>Calendars database created</p>\n"
		."<div><input type=\"submit\" name=\"base\" value=\"continue\">"
		."</div>\n";
}

function create_tables($dbh)
{
	global $drop_tables, $phpc_includes_path;

	require_once("$phpc_includes_path/schema.php");

	foreach(phpc_table_schemas() as $table) {
		$table->create($dbh, $drop_tables);
	}
}

function get_admin()
{
	echo '<h3>Step 4: Administration account</h3>';
	echo "<table>\n"
		."<tr><td colspan=\"2\">Now you must create the calendar administrative "
		."account.</td></tr>\n"
		."<tr><td>\n"
		."Admin name:\n"
		."</td><td>\n"
		."<input type=\"text\" name=\"admin_user\" />\n"
		."</td></tr><tr><td>\n"
		."Admin password:"
		."</td><td>\n"
		."<input type=\"password\" name=\"admin_pass\" />\n"
		."</td></tr><tr><td colspan=\"2\">"
		."<input type=\"submit\" value=\"Create Admin Account\" />\n"
		."</td></tr></table>\n";

}

function add_calendar()
{
	global $phpc_config_file;

	require_once($phpc_config_file);

	$calendar_title = 'PHP-Calendar';

	// Make the database connection.
	$dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

	$query = "INSERT INTO ".SQL_PREFIX."calendars\n"
		."(`cid`) VALUE (1)";

	$dbh->query($query)
		or install_db_error($dbh, 'Error reading options', $query);

	$cid = $dbh->insert_id;

	echo "<h3>Final Step</h3>\n";
	
	echo "<p>Saved default configuration</p>\n";

	$passwd = md5($_POST['admin_pass']);

	$query = "INSERT INTO `" . SQL_PREFIX . "users`\n"
		."(`username`, `password`, `admin`) VALUES\n"
		."('$_POST[admin_user]', '$passwd', 1)";

	$dbh->query($query)
		or install_db_error($dbh, 'Error adding admin.', $query);
	
	echo "<p>Admin account created.</p>";
	echo "<p>Now you should delete install.php file from root directory (for security reasons).</p>";
	echo "<p>You should also change the permissions on config.php so only your webserver can read it.</p>";
	echo "<p><a href=\"index.php\">View calendar</a></p>";
}

echo '</form></body></html>';

// called when there is an error involving the DB
function install_db_error($dbh, $str, $query = "")
{
	$string = "$str<br />" . $dbh->error;

	if($query != "")
		$string .= "<br />SQL query: $query";

	soft_error($string);
}

function connect_db($hostname, $username, $passwd, $database = false)
{
	$dbh = new mysqli($hostname, $username, $passwd);

	if(mysqli_connect_errno()) {
		soft_error("Database connect failed (" . mysqli_connect_errno()
				. "): " . mysqli_connect_error());
	}

	if($database)
		$dbh->select_db($database);
	$dbh->query("SET NAMES 'utf8'");

	return $dbh;
}

?>
