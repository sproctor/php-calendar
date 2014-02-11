<?php
/*
 * Copyright 2012 Sean Proctor
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
   This file sets up the global variables to be used later
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// Displayed in admin
$phpc_version = "2.1";

// Run the installer if we have no config file
// This doesn't work when embedded from outside
if(!file_exists($phpc_config_file)) {
        redirect('install.php');
        exit;
}
require_once($phpc_config_file);
if(!defined('SQL_TYPE')) {
        redirect('install.php');
        exit;
}

ini_set('arg_separator.output', '&amp;');
mb_internal_encoding('UTF-8');
mb_http_output('pass');

if(defined('PHPC_DEBUG')) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
}

if(isset($_SERVER['PATH_INFO']) && strstr($_SERVER['PATH_INFO'], "/")) {
	soft_error("Cannot have a path after the script.");
}

$phpc_prefix = "phpc_" . SQL_PREFIX . SQL_DATABASE;

$phpc_title = "";

require_once("$phpc_includes_path/calendar.php");
require_once("$phpc_includes_path/dbversion.php");

// Make the database connection.
require_once("$phpc_includes_path/phpcdatabase.class.php");
if(!defined("SQL_PORT"))
	define("SQL_PORT", ini_get("mysqli.default_port"));
$phpcdb = new PhpcDatabase(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE,
		SQL_PORT);

session_start();

if ($phpcdb->get_config('version') < PHPC_DB_VERSION) {
	if(isset($_GET['update'])) {
		require_once("$phpc_includes_path/schema.php");
		phpc_updatedb($phpcdb->dbh);
	} else {
		print_update_form();
	}
	exit;
}

if(empty($_SESSION["{$phpc_prefix}uid"])) {
	if(!empty($_COOKIE["{$phpc_prefix}login"])
			&& !empty($_COOKIE["{$phpc_prefix}uid"])
			&& !empty($_COOKIE["{$phpc_prefix}login_series"])) {
		// Cleanup before we check their token so they can't login with
		//   an ancient token
		$phpcdb->cleanup_login_tokens();

	// FIXME should this be _SESSION below?
		$phpc_uid = $_COOKIE["{$phpc_prefix}uid"];
		$phpc_login_series = $_COOKIE["{$phpc_prefix}login_series"];
		$phpc_token = $phpcdb->get_login_token($phpc_uid,
					$phpc_login_series);
		if($phpc_token) {
			if($phpc_token == $_COOKIE["{$phpc_prefix}login"]) {
				$user = $phpcdb->get_user($phpc_uid);
				phpc_do_login($user, $phpc_login_series);
			} else {
				$phpcdb->remove_login_tokens($phpc_uid);
				soft_error(__("Possible hacking attempt on your account."));
			}
		} else {
			$phpc_uid = 0;
		}
	}
} else {
	$phpc_token = $_SESSION["{$phpc_prefix}login"];
}

if(empty($phpc_token))
	$phpc_token = '';

// Create vars
if(get_magic_quotes_gpc()) {
	$_GET = stripslashes_r($_GET);
	$_POST = stripslashes_r($_POST);
}

$vars = array_merge(real_escape_r($_GET), real_escape_r($_POST));

$phpc_user = false;
if(!empty($_SESSION["{$phpc_prefix}uid"])) {
	$phpc_user = $phpcdb->get_user($_SESSION["{$phpc_prefix}uid"]);
}

if ($phpc_user === false) {
	$phpc_uid = 0;
	$anonymous = array('uid' => 0,
			'username' => 'anonymous',
			'password' => '',
			'admin' => false,
			'password_editable' => false,
			'default_cid' => NULL,
			'timezone' => NULL,
			'language' => NULL,
			'disabled' => 0,
			);
	if(isset($_COOKIE["{$phpc_prefix}tz"]))
		$anonymous['timezone'] = $_COOKIE["{$phpc_prefix}tz"];
	if(isset($_COOKIE["{$phpc_prefix}lang"]))
		$anonymous['language'] = $_COOKIE["{$phpc_prefix}lang"];
	$phpc_user = new PhpcUser($anonymous);
}

// Find an appropriate calendar id
if(!empty($vars['phpcid']) && is_numeric($vars['phpcid'])) {
        $phpcid = $vars['phpcid'];
}

if(!isset($phpcid) && !empty($vars['eid'])) {
	if(is_array($vars['eid'])) {
		$eid = $vars['eid'][0];
	} else {
		$eid = $vars['eid'];
	}
	$event = $phpcdb->get_event_by_eid($eid);
	if($event)
		$phpcid = $event['cid'];
}

if(!isset($phpcid) && !empty($vars['oid'])) {
	$event = $phpcdb->get_event_by_oid($vars['oid']);
	if($event)
		$phpcid = $event['cid'];
}

if(!isset($phpcid)) {
	$calendars = $phpcdb->get_calendars();
	if(empty($calendars))
		soft_error(__("Unhandled condition: all calendars have been deleted."));
	if ($phpc_user->get_default_cid() !== false)
		$default_cid = $phpc_user->get_default_cid();
	else
		$default_cid = $phpcdb->get_config('default_cid');
	if (!empty($calendars[$default_cid]))
		$phpcid = $default_cid;
	else
		$phpcid = reset($calendars)->get_cid();
}

$phpc_cal = $phpcdb->get_calendar($phpcid);

//set action
if(empty($vars['action'])) {
	$action = 'display_month';
} else {
	$action = $vars['action'];
}

if(empty($vars['content']))
	$vars['content'] = "html";

$phpc_user_lang = $phpc_user->get_language();
$phpc_user_tz = $phpc_user->get_timezone();

// setup translation stuff
if(!empty($vars['lang'])) {
	$phpc_lang = $vars['lang'];
} elseif(!empty($phpc_user_lang)) {
	$phpc_lang = $phpc_user_lang;
} elseif(!empty($phpc_cal->language)) {
	$phpc_lang = $phpc_cal->language;
} elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$phpc_lang = substr(htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']),
			0, 2);
} else {
	$phpc_lang = 'en';
}

// Require a 2 letter language
if(!preg_match('/^\w{2}$/', $phpc_lang, $matches))
	$phpc_lang = 'en';

$phpc_gettext = new Gettext_PHP($phpc_locale_path, 'messages', $phpc_lang);

// Must be included after translation is setup
require_once("$phpc_includes_path/globals.php");

if(!empty($vars['clearmsg']))
	$_SESSION["{$phpc_prefix}messages"] = NULL;

$phpc_messages = array();

if(!empty($_SESSION["{$phpc_prefix}messages"])) {
	foreach($_SESSION["{$phpc_prefix}messages"] as $message) {
		$phpc_messages[] = $message;
	}
}

if(!empty($phpc_user_tz))
	$phpc_tz = $phpc_user_tz;
else
	$phpc_tz = $phpc_cal->timezone;

if(!empty($phpc_tz))
	date_default_timezone_set($phpc_tz); 
$phpc_tz = date_default_timezone_get();

// set day/month/year - This needs to be done after the timezone is set.
if(isset($vars['month']) && is_numeric($vars['month'])) {
	$phpc_month = $vars['month'];
	if($phpc_month < 1 || $phpc_month > 12)
		display_error(__("Month is out of range."));
} else {
	$phpc_month = date('n');
}

if(isset($vars['year']) && is_numeric($vars['year'])) {
	$time = mktime(0, 0, 0, $phpc_month, 1, $vars['year']);
        if(!$time || $time < 0) {
                display_error(__('Invalid year') . ": {$vars['year']}");
        }
	$phpc_year = date('Y', $time);
} else {
	$phpc_year = date('Y');
}

if(isset($vars['day']) && is_numeric($vars['day'])) {
	$phpc_day = ($vars['day'] - 1) % date('t',
			mktime(0, 0, 0, $phpc_month, 1, $phpc_year)) + 1;
} else {
	if($phpc_month == date('n') && $phpc_year == date('Y')) {
                $phpc_day = date('j');
	} else {
                $phpc_day = 1;
        }
}

?>
