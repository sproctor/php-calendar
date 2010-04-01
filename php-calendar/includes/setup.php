<?php
/*
 * Copyright 2010 Sean Proctor
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

define('IN_PHPC', true);

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
	soft_error(_("You must remove the install directory."));
}

if(defined('PHPC_DEBUG')) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
}

// Make the database connection.
require_once("$phpc_includes_path/phpcdatabase.class.php");
$phpcdb = new PhpcDatabase;

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
if (!isset($vars['month'])) {
	$month = date('n');
} else {
	$month = $vars['month'];
}

if(!isset($vars['year'])) {
	$year = date('Y');
} else {
	$time = mktime(0, 0, 0, $month, 1, $vars['year']);
        if(!$time || $time < 0) {
                soft_error(_('Invalid year') . ": {$vars['year']}");
        }
	$year = date('Y', $time);
}

if(!isset($vars['day'])) {
	if($month == date('n') && $year == date('Y')) {
                $day = date('j');
	} else {
                $day = 1;
        }
} else {
	$day = ($vars['day'] - 1) % date('t', mktime(0, 0, 0, $month, 1, $year))
                + 1;
}

while($month < 1) $month += 12;
$month = ($month - 1) % 12 + 1;

//set action
if(empty($vars['action'])) {
	$action = 'display_month';
} else {
	$action = $vars['action'];
}

if(empty($vars['contentType']))
	$vars['contentType'] = "html";

// setup translation stuff
$phpc_datefmt = "\%\s j, Y";
if($translate) {
	if(isset($vars['lang'])) {
		$lang = $vars['lang'];
		setcookie('lang', $vars['lang']);
	} elseif(isset($_COOKIE['lang'])) {
		$lang = $_COOKIE['lang'];
	} elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	} else {
		$lang = 'en';
	}

	switch($lang) {
		case 'de':
			setlocale(LC_ALL, 'de_DE.utf8', 'de.utf8', 'de', 'ge');
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'en':
			setlocale(LC_ALL, 'C');
			break;
                case 'es':
                        setlocale(LC_ALL, 'es_ES.utf8', 'es.utf8', 'es');
			$phpc_datefmt = "j \%\s Y";
			break;
		case 'fr':
			setlocale(LC_ALL, 'fr_FR.utf8', 'fr.utf8', 'fr');
			$phpc_datefmt = "j \%\s Y";
			break;
                case 'it':
                        setlocale(LC_ALL, 'it_IT.utf8', 'it.utf8', 'it');
			$phpc_datefmt = "j \%\s Y";
			break;
                case 'ja':
                        setlocale(LC_ALL, 'ja_JP.utf8', 'ja.utf8', 'ja', 'jp');
			$phpc_datefmt = "j \%\s Y";
                        break;
                case 'nl':
                        setlocale(LC_ALL, 'nl_NL.utf8', 'nl.utf8', 'nl');
			$phpc_datefmt = "j \%\s Y";
                        break;
		default:
			soft_error("Invalid language identifier.");
	}

	bindtextdomain('messages', $phpc_locale_path);
	textdomain('messages');
}

session_start();

header("Content-Type: text/html; charset=UTF-8");

?>
