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

$phpc_version = "2.0-rc3";

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

if(defined('PHPC_DEBUG')) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
}

$phpc_prefix = "phpc_" . SQL_PREFIX . SQL_DATABASE;

require_once("$phpc_includes_path/calendar.php");

// Make the database connection.
require_once("$phpc_includes_path/phpcdatabase.class.php");
if(!defined("SQL_PORT"))
	define("SQL_PORT", ini_get("mysqli.default_port"));
$phpcdb = new PhpcDatabase(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE,
		SQL_PORT);

session_start();

if(empty($_SESSION["{$phpc_prefix}uid"])) {
	if(!empty($_COOKIE["{$phpc_prefix}login"])
			&& !empty($_COOKIE["{$phpc_prefix}uid"])
			&& !empty($_COOKIE["{$phpc_prefix}login_series"])) {
		// Cleanup before we check their token so they can't login with
		//   an ancient token
		$phpcdb->cleanup_login_tokens();

		$phpc_uid = $_COOKIE["{$phpc_prefix}uid"];
		$phpc_login_series = $_COOKIE["{$phpc_prefix}login_series"];
		$token = $phpcdb->get_login_token($phpc_uid,
					$phpc_login_series);
		if($token) {
			if($token == $_COOKIE["{$phpc_prefix}login"]) {
				$user = $phpcdb->get_user($phpc_uid);
				phpc_do_login($user, $phpc_login_series);
			} else {
				$phpcdb->remove_login_tokens($phpc_uid);
				soft_error(_("Possible hacking attempt on your account."));
			}
		} else {
			$phpc_uid = 0;
		}
	}
			
}

// Create vars
if(get_magic_quotes_gpc()) {
	$_GET = stripslashes_r($_GET);
	$_POST = stripslashes_r($_POST);
}

$vars = array_merge(real_escape_r($_GET), real_escape_r($_POST));

if(!empty($vars['phpcid']) && is_numeric($vars['phpcid'])) {
        $phpcid = $vars['phpcid'];
} elseif(!empty($default_calendar_id)) {
	$phpcid = $default_calendar_id;
} else {
	$phpcid = 1;
}

$phpc_cal = $phpcdb->get_calendar($phpcid);

// set day/month/year
if(isset($vars['month']) && is_numeric($vars['month'])) {
	$month = $vars['month'];
	if($month < 1 || $month > 12)
		display_error(_("Month is out of range."));
} else {
	$month = date('n');
}

if(isset($vars['year']) && is_numeric($vars['year'])) {
	$time = mktime(0, 0, 0, $month, 1, $vars['year']);
        if(!$time || $time < 0) {
                display_error(_('Invalid year') . ": {$vars['year']}");
        }
	$year = date('Y', $time);
} else {
	$year = date('Y');
}

if(isset($vars['day']) && is_numeric($vars['day'])) {
	$day = ($vars['day'] - 1) % date('t', mktime(0, 0, 0, $month, 1, $year))
                + 1;
} else {
	if($month == date('n') && $year == date('Y')) {
                $day = date('j');
	} else {
                $day = 1;
        }
}

//set action
if(empty($vars['action'])) {
	$action = 'display_month';
} else {
	$action = $vars['action'];
}

if(empty($vars['contentType']))
	$vars['contentType'] = "html";

if(!empty($_SESSION["{$phpc_prefix}uid"])) {
	$phpc_user = $phpcdb->get_user($_SESSION["{$phpc_prefix}uid"]);
} else {
	$anonymous = array('uid' => 0,
			'username' => 'anonymous',
			'password' => '',
			'admin' => false,
			'password_editable' => false,
			'timezone' => NULL,
			'language' => NULL,
			);
	if(isset($_COOKIE["{$phpc_prefix}tz"]))
		$anonymous['timezone'] = $_COOKIE["{$phpc_prefix}tz"];
	if(isset($_COOKIE["{$phpc_prefix}lang"]))
		$anonymous['language'] = $_COOKIE["{$phpc_prefix}lang"];
	$phpc_user = new PhpcUser($anonymous);
}

$phpc_user_lang = $phpc_user->get_language();
$phpc_user_tz = $phpc_user->get_timezone();

// setup translation stuff
if($phpc_translate) {
	if(!empty($vars['lang'])) {
		$phpc_lang = $vars['lang'];
	} elseif(!empty($phpc_user_lang)) {
		$phpc_lang = $phpc_user_lang;
	} elseif(!empty($phpc_cal->language)) {
		$phpc_lang = $phpc_cal->language;
	} elseif(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$phpc_lang = substr(htmlentities(
					$_SERVER['HTTP_ACCEPT_LANGUAGE']),
				0, 2);
	} else {
		$phpc_lang = 'en';
	}

	switch($phpc_lang) {
		case 'bg':
			$locale = setlocale(LC_ALL, 'bg_BG.utf8', 'bg.utf8', 'bg');
			break;
		case 'ca':
			$locale = setlocale(LC_ALL, 'ca_ES.utf8', 'ca.utf8', 'ca');
			break;
		case 'da':
			$locale = setlocale(LC_ALL, 'da_DK.utf8', 'da.utf8', 'da');
			break;
		case 'de':
			$locale = setlocale(LC_ALL, 'de_DE.utf8', 'de.utf8', 'de', 'ge');
			break;
		case 'en':
			$locale = setlocale(LC_ALL, 'C');
			break;
                case 'es':
                        $locale = setlocale(LC_ALL, 'es_ES.utf8', 'es.utf8', 'es');
			break;
		case 'fr':
			$locale = setlocale(LC_ALL, 'fr_FR.utf8', 'fr.utf8', 'fr');
			break;
                case 'it':
                        $locale = setlocale(LC_ALL, 'it_IT.utf8', 'it.utf8', 'it');
			break;
                case 'ja':
                        $locale = setlocale(LC_ALL, 'ja_JP.utf8', 'ja.utf8', 'ja', 'jp');
                        break;
                case 'nl':
                        $locale = setlocale(LC_ALL, 'nl_NL.utf8', 'nl.utf8', 'nl');
                        break;
		case 'zh':
			$locale = setlocale(LC_ALL, 'zh_CN.utf8', 'zh.utf8', 'zh');
			break;
		default:
			$phpc_lang = 'en';
			$locale = 'C';
	}

	putenv("LANG=$locale");
	putenv("LC_ALL=$locale");
	putenv("LANGUAGE=$locale");
	setlocale(LC_ALL, $locale);

	$domain = "messages";
	bindtextdomain($domain, $phpc_locale_path);
	textdomain($domain);
} else {
	$phpc_lang = 'en';
}

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

if ($vars["contentType"] == "json") {
	header("Content-Type: application/json; charset=UTF-8");
	echo do_action();
	exit;
}

header("Content-Type: text/html; charset=UTF-8");

?>
