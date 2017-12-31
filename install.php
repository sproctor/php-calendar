<?php
/*
 * Copyright 2017 Sean Proctor
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

namespace PhpCalendar;

require_once __DIR__ . '/vendor/autoload.php';

/*
   Run this file to install the calendar
   it needs very much work
*/

require_once(__DIR__ . "/src/schema.php");

?><!doctype html>
<html>
  <head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css"
      integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <title>PHP Calendar Installation</title>
  </head>
<body>
<div class="container-fluid">
<h1>PHP Calendar</h1>
<?php

if (file_exists(PHPC_CONFIG_FILE)) {
    $config = include PHPC_CONFIG_FILE;
    if (isset($config["sql_host"])) {
        $db = new Database($config);
        $have_calendar = sizeof($db->getCalendars()) > 0;

        $existing_version = $db->getConfig('version');

        if ($have_calendar) {
            if ($existing_version > PHPC_DB_VERSION) {
                echo "<p>DB version is newer than the upgrader.</p>";
                exit;
            } elseif ($existing_version == PHPC_DB_VERSION) {
                echo '<p>The calendar has already been installed. <a href="index.php">Installed calendar</a></p>';
                echo '<p>If you want to install again, manually delete config.php</p>';
                exit;
            }
        }
    }
}

?><p>Welcome to the PHP Calendar installation process.</p>
<form method="post" action="install.php">
<?php

if (!check_config()) {
    report_config();
    exit;
}

foreach ($_POST as $key => $value) {
    echo "<input name=\"$key\" value=\"$value\" type=\"hidden\">\n";
}

if (!isset($_POST['my_hostname'])
        && !isset($_POST['my_username'])
        && !isset($_POST['my_passwd'])
        && !isset($_POST['my_prefix'])
        && !isset($_POST['my_database'])) {
    get_server_setup();
} elseif ((isset($_POST['create_user']) || isset($_POST['create_db']))
        && !isset($_POST['done_user_db'])) {
    add_sql_user_db();
} elseif (!isset($_POST['base'])) {
    install_base();
} elseif (!isset($_POST['admin_user'])
        && !isset($_POST['admin_pass'])) {
    get_admin();
} else {
    add_calendar($config);
}

function check_config()
{
    if (is_writable(PHPC_CONFIG_FILE)) {
        return true;
    }
    
    // Check if we can create the file
    if ($file = @fopen(PHPC_CONFIG_FILE, 'a')) {
        fclose($file);
        return true;
    }
    return false;
}

function report_config()
{
    ?><p>Your configuration file could not be written to. This file
        probably does not yet exist. If that is the case, youneed to
        create it. You need to make sure this script can write to
        it. We suggest logging in with a shell and typing:</p>
    <pre>
        touch config.php
        chmod 666 config.php
    </pre>
    <p>or if you only have ftp access, upload a blank file named
        config.php to your php-calendar directory then use the chmod
        command to change the permissions of config.php to 666.</p>
    <button type="submit" class="btn btn-primary">Retry</button>
    <?php
}

function get_server_setup()
{
    ?>
        <h3>Step 1: Database</h3>
        <div class="form-group">
          <label for="my_hostname">SQL Server Hostname:</label>
          <input type="text" class="form-control" name="my_hostname" id="my_hostname" value="localhost">
        </div>
        <div class="form-group">
          <label for="my_database">SQL Database name:</label>
          <input type="text" class="form-control" name="my_database" id="my_database" value="calendar">
        </div>
        <div class="form-group">
          <label for="my_prefix">SQL Table prefix:</label>
          <input type="text" class="form-control" name="my_prefix" id="my_username" value="phpc_">
        </div>
        <div class="form-group">
          <label for="my_username">SQL Username:</label>
          <input type="text" class="form-control" name="my_username" id="my_username" value="calendar">
        </div>
        <div class="form-group">
          <label for="my_passwd">SQL Password:</label>
          <input type="password" class="form-control" name="my_passwd" id="my_passwd" placeholder="Password">
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" name="create_db" id="create_db" value="yes">
          <label class="form-check-label" for="create_db">Create the database (don't check this if it already exists)</label>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" name="drop_tables" id="drop_tables" value="yes">
          <label class="form-check-label" for="drop_tables">Drop tables before creating them</label>
        </div>
        <button type="submit" class="btn btn-primary">Install</button>
        <?php
}

function add_sql_user_db()
{
    $my_hostname = $_POST['my_hostname'];
    $my_username = $_POST['my_username'];
    $my_passwd = $_POST['my_passwd'];
    //$my_prefix = $_POST['my_prefix'];
    $my_database = $_POST['my_database'];

    $create_db = isset($_POST['create_db']) && $_POST['create_db'] == 'yes';
    

    // Make the database connection.
    try {
        $dbh = new \PDO("mysql:host={$my_hostname};charset=utf8", $my_username, $my_passwd);
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    } catch (\PDOException $e) {
        soft_error(__("Database connect failed: " . $e->getMessage()));
    }

    $string = "<h3>Step 2: Database Setup</h3>";

    if ($create_db) {
        $query = "CREATE DATABASE $my_database";

        $dbh->query($query)
            or install_db_error($dbh, 'error creating db', $query);

        $string .= "<p>Successfully created database</p>";
    }

    echo "$string\n"
        ."<div><input type=\"submit\" name=\"done_user_db\" value=\"continue\"/>"
        ."</div>\n";
}

function create_config($sql_hostname, $sql_username, $sql_passwd, $sql_database, $sql_prefix, $sql_type)
{
    return array(
        "sql_host" => $sql_hostname,
        "sql_user" => $sql_username,
        "sql_passwd" => $sql_passwd,
        "sql_database" => $sql_database,
        "sql_prefix" => $sql_prefix,
        "sql_type" => $sql_type,
        "token_key" => base64_encode(random_bytes(55)));
}

function install_base()
{
    global $config;

    $sql_type = "mysqli";
    $my_hostname = $_POST['my_hostname'];
    $my_username = $_POST['my_username'];
    $my_passwd = $_POST['my_passwd'];
    $my_prefix = $_POST['my_prefix'];
    $my_database = $_POST['my_database'];

    $config = create_config($my_hostname, $my_username, $my_passwd, $my_database, $my_prefix, $sql_type);
    $writer = new \Zend\Config\Writer\PhpArray();
    $writer->toFile(PHPC_CONFIG_FILE, new \Zend\Config\Config($config));


    $db = new Database($config);

    create_tables($db);

    $db->setConfig('version', PHPC_DB_VERSION);

    echo "<p>Config file created at \"". realpath(PHPC_CONFIG_FILE) ."\"</p>"
        ."<p>Calendars database created</p>\n"
        ."<div><input type=\"submit\" name=\"base\" value=\"continue\">"
        ."</div>\n";
}

/**
 * @param Database $db
 */
function create_tables(Database $db)
{
    $drop_tables = isset($_POST["drop_tables"]) && $_POST["drop_tables"] == "yes";

    $db->create($drop_tables);
}

/**
 *
 */
function get_admin()
{
    ?>
    <h3>Step 4: Administration account</h3>
    <div>Now you must create the calendar administrative account.</div>
    <div class="form-group">
        <label for="admin_user">Admin name:</label>
        <input type="text" class="form-control" name="admin_user" id="admin_user">
    </div>
    <div class="form-group">
        <label for="admin_pass">Admin password:</label>
        <input type="password" class="form-control" name="admin_pass" id="admin_pass">
    </div>
    <button type="submit" class="btn btn-primary">Create Account</button>
    <?php
}

/**
 * @param $config
 */
function add_calendar($config)
{
    // Make the database connection.
    $db = new Database($config);

    $db->createCalendar();

    echo "<h3>Final Step</h3>\n";
    
    echo "<p>Saved default configuration</p>\n";

    $db->createUser($_POST['admin_user'], $_POST['admin_pass'], true);
    
    echo "<p>Admin account created.</p>";
    echo "<p>Now you should delete install.php file from root directory (for security reasons).</p>";
    echo "<p>You should also change the permissions on config.php so only your webserver can read it.</p>";
    echo "<p><a href=\"index.php\">View calendar</a></p>";
}

echo '</form></div></body></html>';

// called when there is an error involving the DB
/**
 * @param \PDO $dbh
 * @param string $str
 * @param string $query
 */
function install_db_error(\PDO $dbh, $str, $query = "")
{
    $string = "$str<br />" . json_encode($dbh->errorInfo());

    if ($query != "") {
        $string .= "<br />SQL query: $query";
    }

    soft_error($string);
}
