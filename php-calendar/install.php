<?php
/*
 * Copyright 2013 Sean Proctor
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

$phpc_db_version = 1;

$phpc_root_path = dirname(__FILE__);
$phpc_includes_path = "$phpc_root_path/includes";
$phpc_config_file = "$phpc_root_path/config.php";

define('IN_PHPC', true);

if(!function_exists("mysqli_connect"))
	soft_error("You must have the mysqli extension for PHP installed to use this calendar.");

echo '<html>
<head>
<link rel="stylesheet" type="text/css" href="static/phpc.css"/>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<title>PHP Calendar Installation</title>
</head>
<body>
<h1>PHP Calendar</h1>';

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

		$query = "SELECT version\n"
			."FROM `" . SQL_PREFIX .  "version`\n";


		$sth = $dbh->query($query);
		if($sth) {
			$result = $sth->fetch_assoc();
			if(!empty($result['version']))
				$existing_version = $result['version'];
		}

		if($have_calendar) {

			$must_upgrade = true;

			if($existing_version > $phpc_db_version) {
				echo "<p>DB version is newer than the upgrader.</p>";
				exit;
			} elseif($existing_version == $phpc_db_version) {
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

if(empty($_POST['action'])) {
	get_action();
} elseif($_POST['action'] == 'upgrade') {
	if(empty($_POST['version'])) {
		get_upgrade_version();
	} else {

		if($_POST['version'] == "2.0beta11") {
			upgrade_20beta11();
			echo "<p>Update complete.</p>";
		} elseif($_POST['version'] == "2.0beta10") {
			upgrade_20beta10();
			echo "<p>Update complete.</p>";
		} elseif($_POST['version'] == "2.0beta9") {
			upgrade_20beta10();
			echo "<p>Update complete.</p>";
			if(empty($_POST['timezone']))
				upgrade_20_form();
			else
				upgrade_20_action();
		} elseif($_POST['version'] == "1.1") {
			echo "<p>You must first install the calendar into a new location, then import the old events through the Admin section.</p>";
		} else {
			echo "<p>Invalid version identifier.</p>";
		}
	}
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

function get_action() {
	echo '<input type="submit" name="action" value="install"/><span style="display: inline-block; width:50px;"></span>';
	echo '<input type="submit" name="action" value="upgrade"/>';
}

function get_upgrade_version() {
	global $phpc_config_file;

	if(!file_exists($phpc_config_file)) {
		echo '<p><span style="font-weight:bold;">ERROR</span>: You must copy over your config.php from the version you are updating. Only versions 2.0 beta4 and later are supported.';
		echo '<br/><input type="button" onclick="window.location.reload()" value="Reload"/>';
		return;
	}

	echo '<h3>Pick the version you are updating from</h3>';
	echo '<input type="hidden" name="action" value="upgrade"/>';
	echo '<select name="version">';
	echo '<option value="1.1">version 1.1</option>';
	echo '<option value="2.0beta9">version 2.0-beta4 to beta9</option>';
	echo '<option value="2.0beta10">version 2.0-beta10</option>';
	echo '<option value="2.0beta11">version 2.0-beta11 to rc2</option>';
	echo '</select><span style="display: inline-block; width:50px;"></span>';
	echo '<input type="submit" value="Submit">';
}

function upgrade_20_form() {
	echo '<div>Timezone: <select name="timezone">';
	foreach(timezone_identifiers_list() as $timezone) {
		echo "<option value=\"$timezone\">$timezone</option>\n";
	}
	echo "</select></div>";
	echo "<input type=\"submit\" value=\"Submit\"/>";
}

function upgrade_20_action() {
	global $phpc_config_file, $dbh;


	$query = "ALTER TABLE `" . SQL_PREFIX . "occurrences`\n"
		."ADD `start_ts` timestamp NULL default NULL,\n"
		."ADD `end_ts` timestamp NULL default NULL,\n"
		."CHANGE `startdate` `start_date` date default NULL,\n"
		."CHANGE `enddate` `end_date` date default NULL,\n"
		."CHANGE `timetype` `time_type` tinyint(4) NOT NULL default '0'\n";

	$dbh->query($query)
		or db_error($dbh, 'Error adding elements to occurrences table.',
				$query);

	echo "<p>Occurrences table update";

	$query = "SELECT `oid`, YEAR(`start_date`) AS `start_year`, "
		."MONTH(`start_date`) AS `start_month`, "
		."DAY(`start_date`) AS `start_day`, "
		."HOUR(`starttime`) AS `start_hour`, "
		."MINUTE(`starttime`) AS `start_minute`, "
		."SECOND(`starttime`) AS `start_second`, "
		."YEAR(`end_date`) AS `end_year`, "
		."MONTH(`end_date`) AS `end_month`, "
		."DAY(`end_date`) AS `end_day`, "
		."HOUR(`endtime`) AS `end_hour`, "
		."MINUTE(`endtime`) AS `end_minute`, "
		."SECOND(`endtime`) AS `end_second` "
		."FROM `" . SQL_PREFIX . "occurrences`\n";

	$occ_result = $dbh->query($query)
		or db_error($dbh, 'Error selecting old occurrences.', $query);

	while($row = $occ_result->fetch_assoc()) {
		if($row['start_hour'] !== NULL) {
			$start_ts = mktime($row['start_hour'],
					$row['start_minute'],
					$row['start_second'],
					$row['start_month'],
					$row['start_day'],
					$row['start_year']);

			$end_ts = mktime($row['end_hour'],
					$row['end_minute'],
					$row['end_second'],
					$row['end_month'],
					$row['end_day'],
					$row['end_year']);

			$query = "UPDATE `" . SQL_PREFIX . "occurrences`\n"
				. "SET `start_date` = NULL, "
				. "`start_ts` = FROM_UNIXTIME($start_ts), "
				. "`end_ts` = FROM_UNIXTIME($end_ts)\n"
				. "WHERE `oid` = {$row['oid']}";
			$dbh->query($query)
				or db_error($dbh, 'Error updating occurrences.',
						$query);
		}
	}

	echo "<p>Occurrences updated.</p>";

	upgrade_20beta10();

	echo "<p>Update complete.</p>";
}

function upgrade_20beta10() {
	create_logins_table();
	upgrade_20beta11();
}

function upgrade_20beta11() {
	add_event_time();
	add_calendar_config();
	create_version_table();
}

function add_event_time() {
	global $dbh;

	$query = "ALTER TABLE `" . SQL_PREFIX . "events`\n"
		."ADD `ctime` timestamp NOT NULL default CURRENT_TIMESTAMP,\n"
		."ADD `mtime` timestamp NULL default NULL";

	$dbh->query($query)
		or db_error($dbh, 'Error adding time elements to events table.',
				$query);
}

function add_calendar_config() {
	global $dbh;

	$query = "ALTER TABLE `" . SQL_PREFIX . "calendars`\n"
		."ADD `hours_24` tinyint(1) NOT NULL DEFAULT 0,\n"
		."ADD `date_format` tinyint(1) NOT NULL DEFAULT 0,\n"
		."ADD `week_start` tinyint(1) NOT NULL DEFAULT 0,\n"
		."ADD `subject_max` smallint(5) unsigned NOT NULL DEFAULT 50,\n"
		."ADD `events_max` tinyint(3) unsigned NOT NULL DEFAULT 8,\n"
		."ADD `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PHP-Calendar',\n"
		."ADD `anon_permission` tinyint(1) NOT NULL DEFAULT 1,\n"
		."ADD `timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."ADD `language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."ADD `theme` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL";

	$dbh->query($query)
		or db_error($dbh, 'Error adding time elements to events table.',
				$query);
}

function create_version_table() {
	global $phpc_db_version, $dbh;

	create_table("version",
			"`version` SMALLINT unsigned NOT NULL DEFAULT '$phpc_db_version'");

	$query = "REPLACE INTO `" . SQL_PREFIX . "version`\n"
		."SET `version`='$phpc_db_version'";

	$dbh->query($query)
		or db_error($dbh, 'Error creating version row.', $query);
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
	global $dbh;

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
			or db_error($dbh, 'error creating db', $query);

		$string .= "<p>Successfully created database</p>";
	}
	
	if($create_user) {
		$query = "GRANT ALL ON accounts.* TO $my_username@$my_hostname identified by '$my_passwd'";
		$dbh->query($query)
			or db_error($dbh, 'Could not grant:', $query);
		$query = "GRANT ALL ON $my_database.*\n"
			."TO $my_username IDENTIFIED BY '$my_passwd'";
		$dbh->query($query)
			or db_error($dbh, 'Could not grant:', $query);

		$query = "FLUSH PRIVILEGES";
		$dbh->query($query)
			or db_error($dbh, "Could not flush privileges", $query);

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
	global $phpc_config_file, $dbh;

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

	create_tables();

	echo "<p>Config file created at \"". realpath($phpc_config_file) ."\"</p>"
		."<p>Calendars database created</p>\n"
		."<div><input type=\"submit\" name=\"base\" value=\"continue\">"
		."</div>\n";
}

function create_tables()
{
	global $dbh;

	create_table("events",
		"`eid` int(11) unsigned NOT NULL auto_increment,\n"
		."`cid` int(11) unsigned NOT NULL,\n"
		."`owner` int(11) unsigned NOT NULL default 0,\n"
		."`subject` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
		."`description` text collate utf8_unicode_ci NOT NULL,\n"
		."`readonly` tinyint(1) NOT NULL default 0,\n"
		."`catid` int(11) unsigned default NULL,\n"
		."`ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
		."`mtime` timestamp NULL DEFAULT NULL,\n"
		."PRIMARY KEY (`eid`)\n",
		"AUTO_INCREMENT=1");

	create_table("occurrences",
		"`oid` int(11) unsigned NOT NULL auto_increment,\n"
		."`eid` int(11) unsigned NOT NULL,\n"
		."`start_date` date default NULL,\n"
		."`end_date` date default NULL,\n"
		."`start_ts` timestamp NULL default NULL,\n"
		."`end_ts` timestamp NULL default NULL,\n"
		."`time_type` tinyint(4) NOT NULL default '0',\n"
		."PRIMARY KEY  (`oid`),\n"
		."KEY `eid` (`eid`)\n",
		"AUTO_INCREMENT=1");

	create_table("users",
		"`uid` int(11) unsigned NOT NULL auto_increment,\n"
		."`username` varchar(32) collate utf8_unicode_ci NOT NULL,\n"
		."`password` varchar(32) collate utf8_unicode_ci NOT NULL default '',\n"
		."`admin` tinyint(4) NOT NULL DEFAULT '0',\n"
		."`password_editable` tinyint(1) NOT NULL DEFAULT '1',\n"
		."`timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."`language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."`gid` int(11),\n"
		."PRIMARY KEY (`uid`),\n"
		."UNIQUE KEY `username` (`username`)\n",
		"AUTO_INCREMENT=1");

	create_table("groups",
		"`gid` int(11) NOT NULL AUTO_INCREMENT,\n"
		."`cid` int(11),\n"
		."`name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."PRIMARY KEY (`gid`)\n");

	create_table("user_groups",
		"`gid` int(11),\n"
		."`uid` int(11)\n");

	create_logins_table();

	create_table("permissions",
		"`cid` int(11) unsigned NOT NULL,\n"
		."`uid` int(11) unsigned NOT NULL,\n"
		."`read` tinyint(1) NOT NULL,\n"
		."`write` tinyint(1) NOT NULL,\n"
		."`readonly` tinyint(1) NOT NULL,\n"
		."`modify` tinyint(1) NOT NULL,\n"
		."`admin` tinyint(1) NOT NULL,\n"
		."UNIQUE KEY `cid` (`cid`,`uid`)\n");

	create_table("calendars",
		"`cid` int(11) unsigned NOT NULL auto_increment,\n"
		."`hours_24` tinyint(1) NOT NULL DEFAULT 0,\n"
		."`date_format` tinyint(1) NOT NULL DEFAULT 0,\n"
		."`week_start` tinyint(1) NOT NULL DEFAULT 0,\n"
		."`subject_max` smallint(5) unsigned NOT NULL DEFAULT 50,\n"
		."`events_max` tinyint(4) unsigned NOT NULL DEFAULT 8,\n"
		."`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PHP-Calendar',\n"
		."`anon_permission` tinyint(1) NOT NULL DEFAULT 1,\n"
		."`timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."`language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."`theme` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."PRIMARY KEY  (`cid`)\n",
		"AUTO_INCREMENT=1");
		
	create_table("categories",
		"`catid` int(11) unsigned NOT NULL auto_increment,\n"
		."`cid` int(11) unsigned NOT NULL,\n"
		."`gid` int(11) unsigned DEFAULT NULL,\n"
		."`name` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
		."`text_color` varchar(255) collate utf8_unicode_ci default NULL,\n"
		."`bg_color` varchar(255) collate utf8_unicode_ci default NULL,\n"
		."PRIMARY KEY  (`catid`),\n"
		."KEY `cid` (`cid`)\n",
		"AUTO_INCREMENT=1");

	create_version_table();
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
	global $dbh, $phpc_config_file;

	require_once($phpc_config_file);

	$calendar_title = 'PHP-Calendar';

	// Make the database connection.
	$dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

	$query = "INSERT INTO ".SQL_PREFIX."calendars\n"
		."(`cid`) VALUE (1)";

	$dbh->query($query)
		or db_error($dbh, 'Error reading options', $query);

	$cid = $dbh->insert_id;

	echo "<h3>Final Step</h3>\n";
	
	echo "<p>Saved default configuration</p>\n";

	$passwd = md5($_POST['admin_pass']);

	$query = "INSERT INTO `" . SQL_PREFIX . "users`\n"
		."(`username`, `password`, `admin`) VALUES\n"
		."('$_POST[admin_user]', '$passwd', 1)";

	$dbh->query($query)
		or db_error($dbh, 'Error adding admin.', $query);
	
	echo "<p>Admin account created.</p>";
	echo "<p>Now you should delete install.php file from root directory (for security reasons).</p>";
	echo "<p>You should also change the permissions on config.php so only your webserver can read it.</p>";
	echo "<p><a href=\"index.php\">View calendar</a></p>";
}

echo '</form></body></html>';

// called when there is an error involving the DB
function db_error($dbh, $str, $query = "")
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

// called when some error happens
function soft_error($str)
{
	echo "<h1>Software Error</h1>\n",
	     "<h2>Message:</h2>\n",
	     "<pre>$str</pre>\n",
	     "<h2>Backtrace</h2>\n",
	     "<ol>\n";
	foreach(debug_backtrace() as $bt) {
		echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
	}
	echo "</ol>\n";
	exit;
}

function create_logins_table() {
	global $dbh;
	
	echo '<h3>Step 3: Database Created</h3>';
	create_table("logins",
		"`uid` int(11) unsigned NOT NULL,\n"
		."`series` char(43) collate utf8_unicode_ci NOT NULL,\n"
		."`token` char(43) collate utf8_unicode_ci NOT NULL,\n"
		."`atime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
		."PRIMARY KEY  (`uid`, `series`)\n");

	echo "<p>Logins table updated.</p>";
}

function create_table($suffix, $columns, $table_args = "") {
	global $drop_tables, $dbh;

	$table_name = SQL_PREFIX . $suffix;

	if($drop_tables) {
		$query = "DROP TABLE IF EXISTS `$table_name`";

		$dbh->query($query)
			or db_error($dbh, "Error dropping table `$table_name`.",
					$query);
	}

	$query = "CREATE TABLE `$table_name` (\n"
		.$columns
		.") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci $table_args;\n";

	$dbh->query($query)
		or db_error($dbh, "Error creating table `$table_name`.",
				$query);
}
?>
