<?php
/*
 * Copyright 2009 Sean Proctor
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

define('IN_PHPC', true);

$phpc_root_path = './';

require_once($phpc_root_path . "includes/calendar.php");
require_once('adodb/adodb.inc.php');

$have_config = false;
if(file_exists($phpc_root_path . 'config.php')) {
        require_once($phpc_root_path . 'config.php');
        $have_config = true;
} elseif(!empty($_GET['configfile'])) {
        if(file_exists($_GET['configfile'])) {
                require_once($_GET['configfile']);
                $have_config = true;
        } else {
                echo "<p>File not found</p>\n";
        }
} else {
        echo "<p>No config file found.</p>\n";
}

if(!$have_config) {
        echo "<form action=\"update.php\" method=\"get\">\n";
        echo "Config file (include full path): ";
        echo "<input name=\"configfile\" type=\"text\" />\n";
        echo "<input type=\"submit\" value=\"Update\" />\n";
        echo "</form>";
        exit;
}

// grab the DB info from the config file
if(defined('SQL_HOST')) {
        $sql_host = SQL_HOST;
        echo "<p>Your host is: $sql_host</p>";
} else {
        soft_error('No hostname found in your config file');
}

if(defined('SQL_USER')) {
        $sql_user = SQL_USER;
        echo "<p>Your SQL username is: $sql_user</p>";
} else {
        soft_error('No username found in your config file');
}

if(defined('SQL_PASSWD')) {
        $sql_passwd = SQL_PASSWD;
        echo "<p>Your SQL password is: $sql_passwd</p>";
} else {
        soft_error('No password found in your config file');
}

if(defined('SQL_DATABASE')) {
        $sql_database = SQL_DATABASE;
        echo "<p>Your SQL database name is: $sql_database</p>";
} else {
        soft_error('No database found in your config file');
}

if(defined('SQL_PREFIX')) {
        $sql_prefix = SQL_PREFIX;
        echo "<p>Your SQL table prefix is: $sql_prefix</p>";
} else {
        soft_error('No table prefix found in your config file');
}

if(defined('SQL_TYPE')) {
        $sql_type = SQL_TYPE;
} elseif(isset($dbms)) {
        $sql_type = $dbms;
} else {
        soft_error('No database type found in your config file');
}
echo "<p>Your database type is: $sql_type</p>";

// connect to the database
$db = NewADOConnection($sql_type);
$ok = $db->Connect($sql_host, $sql_user, $sql_passwd, $sql_database);
if(!$ok) {
        soft_error('Could not connect to the database');
}

$query = "ALTER TABLE ".SQL_PREFIX."users
	ADD admin tinyint(1) AFTER password;";
$db->Execute($query) or db_error("Error in alter", $query);

echo "<p>Database updated</p>";

?>
