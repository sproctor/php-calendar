<?php
/*
 * Copyright 2011 Sean Proctor
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

$phpc_db_version = 0;

$phpc_root_path = dirname(dirname(__FILE__));
$phpc_includes_path = "$phpc_root_path/includes";
$phpc_config_file = "$phpc_root_path/config.php";

define('IN_PHPC', true);

if(!function_exists("mysqli_connect"))
	soft_error("You must have the mysqli extension for PHP installed to use this calendar.");

echo '<html>
<head>
<title>install php calendar</title>
</head>
<body>
<form method="post" action="install.php">
';

foreach($_POST as $key => $value) {
	echo "<input name=\"$key\" value=\"$value\" type=\"hidden\">\n";
}

if(empty($_POST['action'])) {
	get_action();
} elseif($_POST['action'] == 'upgrade') {
	if(empty($_POST['version'])) {
		get_upgrade_version();
	} elseif($_POST['version'] == "2.0") {
		if(empty($_POST['timezone']))
			upgrade_20_form();
		else
			upgrade_20_action();
	} elseif($_POST['version'] == "1.1") {
		echo "<p>Sorry upgrading from version 1.1 is not supported at this time.";
	} else {
		echo "<p>Invalid version identifier.";
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
	echo '<input type="submit" name="action" value="install"><br>';
	echo '<input type="submit" name="action" value="upgrade">';
}

function get_upgrade_version() {
	echo '<h3>Pick the version you are updating from</h3>';
	echo '<input type="hidden" name="action" value="upgrade">';
	echo '<select name="version">';
	echo '<option value="1.1">version 1.1</option>';
	echo '<option value="2.0">version 2.0-beta4 or later</option>';
	echo '</select>';
	echo '<input type="submit" value="Submit">';
}

function upgrade_20_form() {
	global $phpc_config_file, $dbh;

	if(!include($phpc_config_file)) {
		echo '<p>You must copy over your config.php from the version you are updating. Only versions 2.0 beta4 and later are supported.';
		return;
	}

	echo '<div>Timezone: <select name="timezone">';
	foreach(timezone_identifiers_list() as $timezone) {
		echo "<option value=\"$timezone\">$timezone</option>\n";
	}
	echo "</select></div>";
	echo "<input type=\"submit\" value=\"Submit\"/>";
}

function upgrade_20_action() {
	global $phpc_config_file, $dbh;

	if(!include($phpc_config_file)) {
		echo '<p>You must copy over your config.php from the version you are updating. Only versions 2.0 beta4 and later are supported.';
		return;
	}

	$dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

	$query = "ALTER TABLE `" . SQL_PREFIX . "occurrences`\n"
		."ADD `start_ts` timestamp NULL default NULL,\n"
		."ADD `end_ts` timestamp NULL default NULL,\n"
		."CHANGE `startdate` `start_date` date default NULL,\n"
		."CHANGE `enddate` `end_date` date default NULL,\n"
		."CHANGE `timetype` `time_type` tinyint(4) NOT NULL default '0'\n";

/*
	$dbh->query($query)
		or db_error($dbh, 'Error adding elements to occurrences table.',
				$query);
*/

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

	echo "<p>Occurrences updated.";

	$query = "ALTER TABLE `" . SQL_PREFIX . "config`\n"
		."CHANGE `cid` `cid` int(11)\n";

	$dbh->query($query)
		or db_error($dbh, 'Error modifying config table.',
				$query);
	
	echo "<p>Config table updated.";

	$query = "INSERT INTO ".SQL_PREFIX."config\n"
		."(`cid`, `config_name`, `config_value`) "
		."VALUE (NULL, 'version', '0')";

	$dbh->query($query)
		or db_error($dbh, 'Error adding version.',
				$query);

	echo "<p>Calendar version updated.";

	echo "<p>Update complete.";
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
		<p><code>
		touch config.php<br>
		chmod 666 config.php
		</code></p>
		<p>or if you only have ftp access, upload a blank file named '
		.'config.php to your php-calendar directory then use the chmod '
		.'command to change the permissions of config.php to 666.</p>
		<input type="submit" value="Retry">';
}

function get_server_setup()
{
	if(!check_config())
		return report_config();

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

	$string = "";

	if($create_db) {
		$query = "CREATE DATABASE $my_database";

		$dbh->query($query)
			or db_error($dbh, 'error creating db', $query);

		$string .= "<div>Successfully created database</div>";
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

		$string .= "<div>Successfully added user</div>";
	}

	echo "$string\n"
		."<div><input type=\"submit\" name=\"done_user_db\" value=\"continue\">"
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
		."?>\n";
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
		or soft_error("could not write to file");
	fclose($fp);

	require_once($phpc_config_file);

	// Make the database connection.
	$dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

	create_tables();

	$query = "INSERT INTO ".SQL_PREFIX."config\n"
		."(`cid`, `config_name`, `config_value`) "
		."VALUE (NULL, 'version', '0')";

	$dbh->query($query)
		or db_error($dbh, 'Error adding version.', $query);

	echo "<p>config created at \"". realpath($phpc_config_file) ."\"</p>"
		."<p>calendars base created</p>\n"
		."<div><input type=\"submit\" name=\"base\" value=\"continue\">"
		."</div>\n";
}

function create_tables()
{
	global $dbh;

	$query = "CREATE TABLE `" . SQL_PREFIX . "events` (\n"
		."`eid` int(11) unsigned NOT NULL auto_increment,\n"
		."`cid` int(11) unsigned NOT NULL,\n"
		."`owner` int(11) unsigned NOT NULL default 0,\n"
		."`subject` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
		."`description` text collate utf8_unicode_ci NOT NULL,\n"
		."`readonly` tinyint(1) NOT NULL default 0,\n"
		."`catid` int(11) unsigned default NULL,\n"
		."PRIMARY KEY  (`eid`)\n"
		.") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;\n";

	$dbh->query($query)
		or db_error($dbh, 'Error creating events table.', $query);

	$query = "CREATE TABLE `" . SQL_PREFIX . "occurrences` (\n"
		."`oid` int(11) unsigned NOT NULL auto_increment,\n"
		."`eid` int(11) unsigned NOT NULL,\n"
		."`start_date` date default NULL,\n"
		."`end_date` date default NULL,\n"
		."`start_ts` timestamp NULL default NULL,\n"
		."`end_ts` timestamp NULL default NULL,\n"
		."`time_type` tinyint(4) NOT NULL default '0',\n"
		."PRIMARY KEY  (`oid`),\n"
		."KEY `eid` (`eid`)\n"
		.") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=750 ;\n";

	$dbh->query($query)
		or db_error($dbh, 'Error creating occurrences table.', $query);

	$query = "CREATE TABLE `" . SQL_PREFIX . "users` (\n"
		."`uid` int(11) unsigned NOT NULL auto_increment,\n"
		."`username` varchar(32) collate utf8_unicode_ci NOT NULL,\n"
		."`password` varchar(32) collate utf8_unicode_ci NOT NULL default '',\n"
		."`admin` tinyint(4) NOT NULL DEFAULT '0',\n"
		."`password_editable` tinyint(1) NOT NULL DEFAULT '1',\n"
		."`timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."`language` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
		."PRIMARY KEY  (`uid`)\n"
		.") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";

	$dbh->query($query)
		or db_error($dbh, 'Error creating users table.', $query);

	$query = "CREATE TABLE `" . SQL_PREFIX . "config` (\n"
		."`cid` int(11) DEFAULT NULL,\n"
		."`config_name` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
		."`config_value` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
		."UNIQUE KEY `calendar_id` (`cid`,`config_name`)\n"
		.") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$dbh->query($query)
		or db_error($dbh, 'Error creating config table.', $query);

	$query = "CREATE TABLE `" . SQL_PREFIX . "permissions` (\n"
		."`cid` int(11) unsigned NOT NULL,\n"
		."`uid` int(11) unsigned NOT NULL,\n"
		."`read` tinyint(1) NOT NULL,\n"
		."`write` tinyint(1) NOT NULL,\n"
		."`readonly` tinyint(1) NOT NULL,\n"
		."`modify` tinyint(1) NOT NULL,\n"
		."`admin` tinyint(1) NOT NULL,\n"
		."UNIQUE KEY `cid` (`cid`,`uid`)\n"
		.") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

	$dbh->query($query)
		or db_error($dbh, 'Error creating permissions table.', $query);

	$query = "CREATE TABLE `" . SQL_PREFIX . "calendars` (\n"
		."`cid` int(11) unsigned NOT NULL auto_increment,\n"
		."PRIMARY KEY  (`cid`)\n"
		.") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";

	$dbh->query($query)
		or db_error($dbh, 'Error creating calendars table.', $query);

	$query = "CREATE TABLE `" . SQL_PREFIX . "categories` (\n"
		."`catid` int(11) unsigned NOT NULL auto_increment,\n"
		."`cid` int(11) unsigned NOT NULL,\n"
		."`name` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
		."`text_color` varchar(255) collate utf8_unicode_ci default NULL,\n"
		."`bg_color` varchar(255) collate utf8_unicode_ci default NULL,\n"
		."PRIMARY KEY  (`catid`),\n"
		."KEY `cid` (`cid`)\n"
		.") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";

	$dbh->query($query)
		or db_error($dbh, 'Error categories calendars table.', $query);
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

	$config_array = array(
			'hours_24' => '0',
			'week_start' => '0',
			'translate' => '1',
			'subject_max' => '50',
			'events_max' => '8',
			'calendar_title' => 'PHP-Calendar',
			'anon_permission' => '3',
			'timezone' => '',
			'language' => '',
			);
	foreach($config_array as $name => $value) {
		$query = "INSERT INTO ".SQL_PREFIX."config\n"
			."(`cid`, `config_name`, `config_value`)\n"
			."VALUES ('$cid', '$name', '$value')";

		$dbh->query($query)
			or db_error($dbh, 'Error creating options.', $query);
	}

	echo "<p>saved default configuration</p>\n";

	$passwd = md5($_POST['admin_pass']);

	$query = "INSERT INTO `" . SQL_PREFIX . "users`\n"
		."(`username`, `password`, `admin`) VALUES\n"
		."('$_POST[admin_user]', '$passwd', 1)";

	$dbh->query($query)
		or db_error($dbh, 'Error adding admin.', $query);
	
	echo "<p>Admin created.</p>";
	echo "<p>You delete the install directory and you should change the permissions on config.php so only your webserver can read it.</p>";
	echo "<p><a href=\"../index.php\">View calendar</a></p>";
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
	echo "<html><head><title>Error</title></head>\n",
	     "<body><h1>Software Error</h1>\n",
	     "<h2>Message:</h2>\n",
	     "<pre>$str</pre>\n",
	     "<h2>Backtrace</h2>\n",
	     "<ol>\n";
	foreach(debug_backtrace() as $bt) {
		echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
	}
	echo "</ol>\n",
	     "</body></html>\n";
	exit;
}

?>
