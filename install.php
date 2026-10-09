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

$phpc_db_version = 2;

$phpc_root_path = dirname(__FILE__);
$phpc_includes_path = "$phpc_root_path/src";
$phpc_config_file = "$phpc_root_path/config.php";

define('IN_PHPC', true);

if (PHP_VERSION_ID < 80100) {
    soft_error("PHP-Calendar requires PHP 8.1 or later. This server is running PHP " . PHP_VERSION . ".");
}

if (!function_exists("mysqli_connect")) {
    soft_error("You must have the mysqli extension for PHP installed to use this calendar.");
}

// The installer checks query results itself ("or db_error(...)") and probes
//   for tables that may not exist, so turn off PHP 8.1+'s mysqli exceptions.
mysqli_report(MYSQLI_REPORT_OFF);

echo '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<link rel="stylesheet" type="text/css" href="static/phpc.css"/>
<title>PHP Calendar Installation</title>
</head>
<body>
<h1>PHP Calendar</h1>';

$must_upgrade = false;
$existing_version = 0;

if (file_exists($phpc_config_file)) {
    require_once $phpc_config_file;
    if (defined("SQL_HOST")) {
        $dbh = connect_db(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE);

        $query = "SELECT *\n"
            ."FROM `" . SQL_PREFIX .  "calendars`\n";

        $sth = $dbh->query($query);
        $have_calendar = $sth && $sth->fetch_assoc();


        $query = "SELECT version\n"
            ."FROM `" . SQL_PREFIX .  "version`\n";


        $sth = $dbh->query($query);
        if ($sth) {
            $result = $sth->fetch_assoc();
            if (!empty($result['version'])) {
                $existing_version = $result['version'];
            }
        }

        if ($have_calendar) {

            $must_upgrade = true;

            if ($existing_version > $phpc_db_version) {
                echo "<p>DB version is newer than the upgrader.</p>";
                exit;
            } elseif ($existing_version == $phpc_db_version) {
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

foreach ($_POST as $key => $value) {
    // Passwords are only needed by the step they're submitted to; don't copy
    //   them into later pages.
    if (!is_string($value) || $key == 'my_passwd' || $key == 'admin_pass') {
        continue;
    }
    $key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    echo "<input name=\"$key\" value=\"$value\" type=\"hidden\">\n";
}

$drop_tables = isset($_POST["drop_tables"]) && $_POST["drop_tables"] == "yes";

if (empty($_POST['action'])) {
    get_action();
} elseif ($_POST['action'] == 'upgrade') {
    $drop_tables = false;
    if (check_upgrade_supported()) {
        if (empty($_POST['version'])) {
            get_upgrade_version();
        } else {
            upgrade_db_2();
            echo "<p>Update complete.</p>";
        }
    }
} elseif (!isset($_POST['my_hostname'])
        && !isset($_POST['my_username'])
        && !isset($_POST['my_passwd'])
        && !isset($_POST['my_prefix'])
        && !isset($_POST['my_database'])) {
    get_server_setup();
} elseif (!isset($_POST['base'])) {
    install_base();
} elseif (!isset($_POST['admin_user'])
        && !isset($_POST['admin_pass'])) {
    get_admin();
} else {
    add_calendar();
}

function get_action()
{
    echo '<input type="submit" name="action" value="install"/><span style="display: inline-block; width:50px;"></span>';
    echo '<input type="submit" name="action" value="upgrade"/>';
}

// Only databases from PHP-Calendar 2.0.0 through 2.0.13 (database version 1)
//   can be upgraded. They are told apart from 2.0-rc2, which also has version
//   1, by the users.gid column that 2.0.0 added.
function check_upgrade_supported()
{
    global $phpc_config_file, $existing_version, $dbh;

    if (!file_exists($phpc_config_file) || !defined('SQL_HOST')) {
        echo '<p><span style="font-weight:bold;">ERROR</span>: You must copy over your config.php from the version you are updating.';
        echo '<br/><input type="button" onclick="window.location.reload()" value="Reload"/>';
        return false;
    }

    $sth = $dbh->query("SHOW COLUMNS FROM `" . SQL_PREFIX . "users` LIKE 'gid'");
    $has_users_gid = $sth && $sth->num_rows > 0;

    if ($existing_version != 1 || !$has_users_gid) {
        echo '<p>This database is from a version of PHP-Calendar older than 2.0.0, which this installer can no longer upgrade. Upgrade to PHP-Calendar 2.0.13 first using its installer, then run this one.</p>';
        return false;
    }

    return true;
}

function get_upgrade_version()
{
    echo '<h3>Upgrade from PHP-Calendar 2.0.x</h3>';
    echo '<p>This updates your database for this version of PHP-Calendar. Back up your database before continuing.</p>';
    echo '<input type="hidden" name="action" value="upgrade"/>';
    echo '<input type="hidden" name="version" value="2.0"/>';
    echo '<input type="submit" value="Upgrade">';
}

// Database version 2: the password column holds password_hash() output
//   instead of 32-character MD5 hashes.
function upgrade_db_2()
{
    global $dbh;

    echo "<p>Widening the password column.</p>";
    $query = "ALTER TABLE `" . SQL_PREFIX . "users`\n"
        ."MODIFY `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''";
    $dbh->query($query)
        or db_error($dbh, 'Error widening the password column.', $query);

    set_db_version(2);
}

// The version table has no key, so update its row rather than REPLACE INTO.
function set_db_version($version)
{
    global $dbh;

    $version_table = SQL_PREFIX . 'version';
    $query = "UPDATE `$version_table` SET `version`='$version'";
    $dbh->query($query)
        or db_error($dbh, 'Error updating version.', $query);

    $sth = $dbh->query("SELECT COUNT(*) AS `rows` FROM `$version_table`");
    if ($sth && $sth->fetch_assoc()['rows'] == 0) {
        $query = "INSERT INTO `$version_table` SET `version`='$version'";
        $dbh->query($query)
            or db_error($dbh, 'Error creating version row.', $query);
    }
}

function create_version_table()
{
    global $phpc_db_version, $dbh;

    create_table(
        "version",
        "`version` SMALLINT unsigned NOT NULL DEFAULT '$phpc_db_version'"
    );

    $query = "REPLACE INTO `" . SQL_PREFIX . "version`\n"
        ."SET `version`='$phpc_db_version'";

    $dbh->query($query)
        or db_error($dbh, 'Error creating version row.', $query);
}

function check_config()
{
    global $phpc_config_file;

    if (is_writable($phpc_config_file)) {
        return true;
    }

    // Check if we can create the file
    if ($file = @fopen($phpc_config_file, 'a')) {
        fclose($file);
        return true;
    }
    return false;
}

function report_config()
{
    echo '<p>Your configuration file could not be written to. This file '
        .'probably does not yet exist. If that is the case, you need to '
        .'create it. You need to make sure this script can write to '
        .'it. We suggest logging in with a shell and typing:</p>
		<pre>
		touch config.php
		chmod 666 config.php
		</pre>
		<p>or if you only have ftp access, upload a blank file named '
        .'config.php to your php-calendar directory then use the chmod '
        .'command to change the permissions of config.php to 666.</p>
		<p><input type="submit" value="Retry"/></p>';
}

function get_server_setup()
{
    if (!check_config()) {
        return report_config();
    }

    echo '
		<h3>Step 1: Database</h3>
		<p>Before continuing, create a MySQL database and a database user for
		PHP-Calendar, for example in your hosting control panel. If you
		administer the database server yourself, you can use:</p>
		<pre>CREATE DATABASE calendar CHARACTER SET utf8mb4;
CREATE USER \'calendar\'@\'localhost\' IDENTIFIED BY \'your password\';
GRANT ALL ON calendar.* TO \'calendar\'@\'localhost\';</pre>
		<table class="display">
		<tr>
		<td><label for="my_hostname">SQL Server Hostname:</label></td>
		<td><input type="text" name="my_hostname" id="my_hostname" value="localhost"></td>
		</tr>
		<tr>
		<td><label for="my_database">SQL Database name:</label></td>
		<td><input type="text" name="my_database" id="my_database" value="calendar"></td>
		</tr>
		<tr>
		<td><label for="my_prefix">SQL Table prefix:</label></td>
		<td><input type="text" name="my_prefix" id="my_prefix" value="phpc_"></td>
		</tr>
		<tr>
		<td><label for="my_username">SQL Username:</label></td>
		<td><input type="text" name="my_username" id="my_username" value="calendar"></td>
		</tr>
		<tr>
		<td><label for="my_passwd">SQL Password:</label></td>
		<td><input type="password" name="my_passwd" id="my_passwd"></td>
		</tr>
		<tr>
		<td colspan="2">
		  <input type="checkbox" name="drop_tables" id="drop_tables" value="yes">
		  <label for="drop_tables">Drop tables before creating them</label>
		</td>
		</tr>
		<tr>
		<td colspan="2">
		  <input name="action" type="submit" value="Install">
		</td>
		</tr>
		</table>';
}

// The server setup steps must never run against an existing configuration,
// otherwise anyone who can reach install.php could replace config.php.
function refuse_if_configured()
{
    if (defined('SQL_HOST')) {
        soft_error('The calendar is already configured. To install again, manually delete config.php.');
    }
}

function install_base()
{
    global $phpc_config_file, $dbh;

    refuse_if_configured();

    $config = array(
        'SQL_HOST'     => (string)$_POST['my_hostname'],
        'SQL_USER'     => (string)$_POST['my_username'],
        'SQL_PASSWD'   => (string)$_POST['my_passwd'],
        'SQL_DATABASE' => (string)$_POST['my_database'],
        'SQL_PREFIX'   => (string)$_POST['my_prefix'],
        'SQL_TYPE'     => 'mysqli',
    );

    // The prefix and database name are used unquoted in SQL identifiers.
    if (!preg_match('/^\w*$/', $config['SQL_PREFIX'])) {
        soft_error('The table prefix may only contain letters, numbers and underscores.');
    }
    if (!preg_match('/^\w+$/', $config['SQL_DATABASE'])) {
        soft_error('The database name may only contain letters, numbers and underscores.');
    }

    // Make the database connection.
    $dbh = connect_db(
        $config['SQL_HOST'],
        $config['SQL_USER'],
        $config['SQL_PASSWD'],
        $config['SQL_DATABASE']
    );

    $fp = fopen($phpc_config_file, 'w')
        or soft_error('Couldn\'t open config file.');

    phpc_debug("Writing config file \"$phpc_config_file\".");

    // var_export() produces safely quoted PHP literals
    $defines = '';
    foreach ($config as $name => $value) {
        $defines .= "define('$name', " . var_export($value, true) . ");\n";
    }

    fwrite($fp, "<"."?php\n$defines?".">\n") // Break this up to fix syntax HL in vim
        or soft_error("Could not write to file");
    fclose($fp);

    foreach ($config as $name => $value) {
        define($name, $value);
    }

    create_tables();

    echo "<p>Config file created at \"". realpath($phpc_config_file) ."\"</p>"
        ."<p>Calendars database created</p>\n"
        ."<div><input type=\"submit\" name=\"base\" value=\"continue\">"
        ."</div>\n";
}

function create_tables()
{
    global $dbh;

    create_table(
        "events",
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
        "AUTO_INCREMENT=1"
    );

    create_table(
        "occurrences",
        "`oid` int(11) unsigned NOT NULL auto_increment,\n"
        ."`eid` int(11) unsigned NOT NULL,\n"
        ."`start_date` date default NULL,\n"
        ."`end_date` date default NULL,\n"
        ."`start_ts` timestamp NULL default NULL,\n"
        ."`end_ts` timestamp NULL default NULL,\n"
        ."`time_type` tinyint(4) NOT NULL default '0',\n"
        ."PRIMARY KEY  (`oid`),\n"
        ."KEY `eid` (`eid`)\n",
        "AUTO_INCREMENT=1"
    );

    create_table(
        "users",
        "`uid` int(11) unsigned NOT NULL auto_increment,\n"
        ."`username` varchar(32) collate utf8_unicode_ci NOT NULL,\n"
        ."`password` varchar(255) collate utf8_unicode_ci NOT NULL default '',\n"
        ."`admin` tinyint(4) NOT NULL DEFAULT '0',\n"
        ."`password_editable` tinyint(1) NOT NULL DEFAULT '1',\n"
        ."`timezone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
        ."`language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
        ."`gid` int(11),\n"
        ."PRIMARY KEY (`uid`),\n"
        ."UNIQUE KEY `username` (`username`)\n",
        "AUTO_INCREMENT=1"
    );

    // added in 2.0-rc3
    create_table(
        "groups",
        "`gid` int(11) NOT NULL AUTO_INCREMENT,\n"
        ."`cid` int(11),\n"
        ."`name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n"
        ."PRIMARY KEY (`gid`)\n"
    );

    // added in 2.0-rc3
    create_table(
        "user_groups",
        "`gid` int(11),\n"
        ."`uid` int(11)\n"
    );

    create_logins_table();

    create_table(
        "permissions",
        "`cid` int(11) unsigned NOT NULL,\n"
        ."`uid` int(11) unsigned NOT NULL,\n"
        ."`read` tinyint(1) NOT NULL,\n"
        ."`write` tinyint(1) NOT NULL,\n"
        ."`readonly` tinyint(1) NOT NULL,\n"
        ."`modify` tinyint(1) NOT NULL,\n"
        ."`admin` tinyint(1) NOT NULL,\n"
        ."UNIQUE KEY `cid` (`cid`,`uid`)\n"
    );

    create_table(
        "calendars",
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
        "AUTO_INCREMENT=1"
    );

    create_table(
        "categories",
        "`catid` int(11) unsigned NOT NULL auto_increment,\n"
        ."`cid` int(11) unsigned NOT NULL,\n"
        ."`gid` int(11) unsigned DEFAULT NULL,\n"
        ."`name` varchar(255) collate utf8_unicode_ci NOT NULL,\n"
        ."`text_color` varchar(255) collate utf8_unicode_ci default NULL,\n"
        ."`bg_color` varchar(255) collate utf8_unicode_ci default NULL,\n"
        ."PRIMARY KEY  (`catid`),\n"
        ."KEY `cid` (`cid`)\n",
        "AUTO_INCREMENT=1"
    );

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

    $passwd = password_hash((string)$_POST['admin_pass'], PASSWORD_DEFAULT);
    $admin_user = $dbh->real_escape_string($_POST['admin_user']);

    $query = "INSERT INTO `" . SQL_PREFIX . "users`\n"
        ."(`username`, `password`, `admin`) VALUES\n"
        ."('$admin_user', '$passwd', 1)";

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

    if ($query != "") {
        $string .= "<br />SQL query: $query";
    }

    soft_error($string);
}

// called when there is an error involving the DB that we should keep trying
//   to procede after
function db_warning($dbh, $str, $query = "")
{
    $string = "$str<br />" . $dbh->error;

    if ($query != "") {
        $string .= "<br />SQL query: $query";
    }

    echo $string;
}

function connect_db($hostname, $username, $passwd, $database = false)
{

    phpc_debug("Connecting to SQL server $hostname with user $username.");

    $dbh = new mysqli($hostname, $username, $passwd);

    if (mysqli_connect_errno()) {
        soft_error("Database connect failed (" . mysqli_connect_errno()
                . "): " . mysqli_connect_error());
    }

    if ($database) {
        phpc_debug("Selecting database $database");
        $dbh->select_db($database)
            or soft_error("Could not select database \"$database\".");
    }
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
    foreach (debug_backtrace() as $bt) {
        echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
    }
    echo "</ol>\n";
    exit;
}

function create_logins_table()
{
    global $dbh;

    echo '<h3>Step 3: Database Created</h3>';
    create_table(
        "logins",
        "`uid` int(11) unsigned NOT NULL,\n"
        ."`series` char(43) collate utf8_unicode_ci NOT NULL,\n"
        ."`token` char(43) collate utf8_unicode_ci NOT NULL,\n"
        ."`atime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
        ."PRIMARY KEY  (`uid`, `series`)\n"
    );

    echo "<p>Logins table updated.</p>";
}

function create_table($suffix, $columns, $table_args = "")
{
    global $drop_tables, $dbh;

    $table_name = SQL_PREFIX . $suffix;

    phpc_debug("Creating table \"$table_name\".");

    if ($drop_tables) {
        $query = "DROP TABLE IF EXISTS `$table_name`";

        $dbh->query($query)
            or db_error(
                $dbh,
                "Error dropping table `$table_name`.",
                $query
            );
    }

    $query = "CREATE TABLE `$table_name` (\n"
        .$columns
        .") ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci $table_args;\n";

    $dbh->query($query)
        or db_error(
            $dbh,
            "Error creating table `$table_name`.",
            $query
        );
}

function phpc_debug($msg)
{
    echo "<p>$msg</p>";
}
