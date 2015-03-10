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

namespace PhpCalendar;

/*
   This file sets up the global variables to be used later
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// Displayed in admin
$version = "2.1";

// Run the installer if we have no config file
// This doesn't work when embedded from outside
if(!file_exists($config_file)) {
        redirect('install.php');
        exit;
}
require_once($config_file);
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

$prefix = "phpc_" . SQL_PREFIX . SQL_DATABASE;

$title = "";

<<<<<<< HEAD:src/setup.php
require_once("$includes_path/helpers.php");
=======
require_once("$phpc_includes_path/calendar.php");
>>>>>>> 40e298632ef39e166d9bd118a0801f6be06f3e2c:includes/setup.php

if(!defined("SQL_PORT"))
	define("SQL_PORT", ini_get("mysqli.default_port"));
$phpcdb = new Database(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE, SQL_PORT);

session_start();

<<<<<<< HEAD:src/setup.php
require_once("$includes_path/schema.php");
=======
require_once("$phpc_includes_path/schema.php");
>>>>>>> 40e298632ef39e166d9bd118a0801f6be06f3e2c:includes/setup.php
if ($phpcdb->get_config('version') < PHPC_DB_VERSION) {
	if(isset($_GET['update'])) {
		phpc_updatedb($phpcdb->dbh);
	} else {
		print_update_form();
	}
	exit;
}

if(empty($_SESSION["{$prefix}uid"])) {
	if(!empty($_COOKIE["{$prefix}login"])
			&& !empty($_COOKIE["{$prefix}uid"])
			&& !empty($_COOKIE["{$prefix}login_series"])) {
		// Cleanup before we check their token so they can't login with
		//   an ancient token
		$phpcdb->cleanup_login_tokens();

	// FIXME should this be _SESSION below?
		$uid = $_COOKIE["{$prefix}uid"];
		$login_series = $_COOKIE["{$prefix}login_series"];
		$token = $phpcdb->get_login_token($uid,
					$login_series);
		if($token) {
			if($token == $_COOKIE["{$prefix}login"]) {
				$user = $phpcdb->get_user($uid);
				do_login($user, $login_series);
			} else {
				$phpcdb->remove_login_tokens($uid);
				soft_error(__("Possible hacking attempt on your account."));
			}
		} else {
			$uid = 0;
		}
	}
} else {
	$token = $_SESSION["{$prefix}login"];
}

if(empty($token))
	$token = '';

// Create vars
if(get_magic_quotes_gpc()) {
	$_GET = stripslashes_r($_GET);
	$_POST = stripslashes_r($_POST);
}

$vars = array_merge(real_escape_r($_GET), real_escape_r($_POST));

$user = false;
if(!empty($_SESSION["{$prefix}uid"])) {
	$user = $phpcdb->get_user($_SESSION["{$prefix}uid"]);
}

if ($user === false) {
	$uid = 0;
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
	if(isset($_COOKIE["{$prefix}tz"])) {
		$_tz = $_COOKIE["{$prefix}tz"];
		// If we have a timezone, make sure it's valid
		if(in_array($_tz, timezone_identifiers_list())) {
			$anonymous['timezone'] = $_tz;
		} else {
			$anonymous['timezone'] = '';
		}
	}
	if(isset($_COOKIE["{$prefix}lang"]))
		$anonymous['language'] = $_COOKIE["{$prefix}lang"];
	$user = new User($anonymous);
}

// Find an appropriate calendar id
if(!empty($vars['phpcid'])) {
	if(!is_numeric($vars['phpcid']))
		soft_error(__("Invalid calendar ID."));
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
	if(empty($calendars)) {
		if(empty($vars['action'])) {
			if(is_admin())
				$vars['action'] = 'admin';
			else
				$vars['action'] = 'settings';
		}
	} else {
		if ($user->get_default_cid() !== false)
			$default_cid = $user->get_default_cid();
		else
			$default_cid = $phpcdb->get_config('default_cid');
		if (!empty($calendars[$default_cid]))
			$phpcid = $default_cid;
		else
			$phpcid = reset($calendars)->get_cid();
	}
}

if(isset($phpcid)) {
	$cal = $phpcdb->get_calendar($phpcid);

	if(empty($cal))
		soft_error(__("Bad calendar ID."));
}

//set action
if(empty($vars['action'])) {
	$action = 'display_month';
} else {
	$action = $vars['action'];
}

if(empty($vars['content']))
	$vars['content'] = "html";

$user_lang = $user->get_language();
$user_tz = $user->get_timezone();

// setup translation stuff
if(!empty($vars['lang'])) {
	$lang = $vars['lang'];
} elseif(!empty($user_lang)) {
	$lang = $user_lang;
} elseif(!empty($cal->language)) {
	$lang = $cal->language;
} elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$lang = substr(htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']),
			0, 2);
} else {
	$lang = 'en';
}

// Require a 2 letter language
if(!preg_match('/^\w+$/', $lang, $matches))
	$lang = 'en';

//$gettext = new \Gettext_PHP($locale_path, 'messages', $lang);
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

$translator = new Translator($lang, new MessageSelector());
$translator->addLoader('mo', new MoFileLoader());
if($lang != 'en')
	$translator->addResource('mo', "$locale_path/$lang/LC_MESSAGES/messages.mo", $lang);

if(!empty($vars['clearmsg']))
	$_SESSION["{$prefix}messages"] = NULL;

$messages = array();

if(!empty($_SESSION["{$prefix}messages"])) {
	foreach($_SESSION["{$prefix}messages"] as $message) {
		$messages[] = $message;
	}
}

if(!empty($user_tz))
	$tz = $user_tz;
else
	$tz = $cal->timezone;

if(!empty($tz))
	date_default_timezone_set($tz); 
$tz = date_default_timezone_get();

// set day/month/year - This needs to be done after the timezone is set.
if(isset($vars['month']) && is_numeric($vars['month'])) {
	$month = $vars['month'];
	if($month < 1 || $month > 12)
		soft_error(__("Month is out of range."));
} else {
	$month = date('n');
}

if(isset($vars['year']) && is_numeric($vars['year'])) {
	$time = mktime(0, 0, 0, $month, 1, $vars['year']);
        if(!$time || $time < 0) {
                soft_error(__('Invalid year') . ": {$vars['year']}");
        }
	$year = date('Y', $time);
} else {
	$year = date('Y');
}

if(isset($vars['day']) && is_numeric($vars['day'])) {
	$day = ($vars['day'] - 1) % date('t',
			mktime(0, 0, 0, $month, 1, $year)) + 1;
} else {
	if($month == date('n') && $year == date('Y')) {
                $day = date('j');
	} else {
                $day = 1;
        }
}

?>
