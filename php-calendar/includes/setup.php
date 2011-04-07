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
   This file sets up the global variables to be used later
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

// make sure that we have _ defined
if(!function_exists('_')) {
	function _($str) { return $str; }
	$translate = false;
} else {
	$translate = true;
}

require_once("$phpc_includes_path/util.php");

// Run the installer if we have no config file
// This doesn't work when embedded from outside
if(!file_exists($phpc_config_file)) {
        redirect('install/install.php');
        exit;
}
require_once($phpc_config_file);
if(!defined('SQL_TYPE')) {
        redirect('install/install.php');
        exit;
}

if(!defined("PHPC_DEBUG") && file_exists("$phpc_root_path/install")) {
	display_error(_("You must remove the install directory."));
}

if(defined('PHPC_DEBUG')) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
}

// Create vars
foreach($_GET as $key => $value) {
	if(!get_magic_quotes_gpc())
		$vars[$key] = addslashes_r($value);
	else
		$vars[$key] = $value;
}

foreach($_POST as $key => $value) {
	if(!get_magic_quotes_gpc())
		$vars[$key] = addslashes_r($value);
	else
		$vars[$key] = $value;
}

if(!empty($vars['phpcid']) && is_numeric($vars['phpcid'])) {
        $phpcid = $vars['phpcid'];
} elseif(!empty($default_calendar_id)) {
	$phpcid = $default_calendar_id;
} else {
	$phpcid = 1;
}

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

require_once("$phpc_includes_path/calendar.php");

// Make the database connection.
require_once("$phpc_includes_path/phpcdatabase.class.php");
$phpcdb = new PhpcDatabase;

// Set the session to something unique to this setup
session_name(SQL_PREFIX . SQL_DATABASE . '_SESSION');
session_start();

$phpc_tz = NULL;
if(!empty($_SESSION['phpc_uid'])) {
	$phpc_user = $phpcdb->get_user($_SESSION['phpc_uid']);
	$phpc_user_lang = $phpc_user->get_language();
	$phpc_tz = $phpc_user->get_timezone();
}

// setup translation stuff
$phpc_datefmt = "\%\s j, Y";
if($translate) {
	$phpc_store_lang = false;
	$phpc_cal_lang = get_config($phpcid, 'language');
	if(!empty($vars['lang'])) {
		$phpc_lang = $vars['lang'];
		$phpc_store_lang = true;
	} elseif(!empty($_COOKIE['lang'])) {
		$phpc_lang = $_COOKIE['lang'];
	} elseif(!empty($phpc_user_lang)) {
		$phpc_lang = $phpc_user_lang;
	} elseif(!empty($phpc_cal_lang)) {
		$phpc_lang = $phpc_cal_lang;
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
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'ca':
			$locale = setlocale(LC_ALL, 'ca_ES.utf8', 'ca.utf8', 'ca');
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'da':
			$locale = setlocale(LC_ALL, 'da_DK.utf8', 'da.utf8', 'da');
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'de':
			$locale = setlocale(LC_ALL, 'de_DE.utf8', 'de.utf8', 'de', 'ge');
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'en':
			$locale = setlocale(LC_ALL, 'C');
			break;
                case 'es':
                        $locale = setlocale(LC_ALL, 'es_ES.utf8', 'es.utf8', 'es');
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'fr':
			$locale = setlocale(LC_ALL, 'fr_FR.utf8', 'fr.utf8', 'fr');
			$phpc_datefmt = "j \%\s Y";
			break;
                case 'it':
                        $locale = setlocale(LC_ALL, 'it_IT.utf8', 'it.utf8', 'it');
			$phpc_datefmt = "j \%\s Y";
			break;
                case 'ja':
                        $locale = setlocale(LC_ALL, 'ja_JP.utf8', 'ja.utf8', 'ja', 'jp');
			$phpc_datefmt = "j \%\s Y";
                        break;
                case 'nl':
                        $locale = setlocale(LC_ALL, 'nl_NL.utf8', 'nl.utf8', 'nl');
			$phpc_datefmt = "j \%\s Y";
                        break;
		case 'zh':
			$locale = setlocale(LC_ALL, 'zh_CN.utf8', 'zh.utf8', 'zh');
			$phpc_datefmt = "j \%\s Y";
			break;
		default:
			$phpc_lang = 'C';
			$locale = 'C';
	}

	putenv("LC_ALL=$locale");
	putenv("LANGUAGE=$locale");

	if($phpc_store_lang)
		setcookie('lang', $phpc_lang);

	bindtextdomain('messages', $phpc_locale_path);
	textdomain('messages');
} else {
	$phpc_lang = 'en';
}

// Create a secret token to check for CSRF
if(empty($_SESSION["phpc_token"])) {
	$phpc_token = generate_token();
	$_SESSION["phpc_token"] = $phpc_token;
} else {
	$phpc_token = $_SESSION["phpc_token"];
}

// Expire the session after 30 minutes
if(isset($_SESSION['phpc_time']) && time() - $_SESSION['phpc_time'] > 1800) {
	// session is expired
	session_destroy();
	$_SESSION = array();
	$_SESSION['phpc_token'] = $phpc_token;
}

$_SESSION['phpc_time'] = time();

// Check if our session timed out and logged us out
if(!empty($_COOKIE["phpc_user"]) && !is_user()) {
	setcookie("phpc_user", "0");
	if(empty($_SESSION['messages']))
		$_SESSION['messages'] = array();
	$_SESSION['messages'][] = _("Session has expired.");
}

if(empty($phpc_tz))
	$phpc_tz = get_config($phpcid, 'timezone');

if(!empty($phpc_tz))
	date_default_timezone_set($phpc_tz); 
$phpc_tz = date_default_timezone_get();

header("Content-Type: text/html; charset=UTF-8");

?>
