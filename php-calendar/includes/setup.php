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

// Modify these if you need to
// If you need to changed them on a per-script basis, you can move them into
//   index.php or your script.
$phpc_script = $_SERVER['PHP_SELF'];

if(!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
	$phpc_protocol = "https";
else
	$phpc_protocol = "http";

$phpc_server = $_SERVER['SERVER_NAME'];
if(!empty($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != 80)
	$phpc_server .= ":{$_SERVER["SERVER_PORT"]}";

$phpc_url = "$phpc_protocol://$phpc_server$phpc_script?"
	. $_SERVER['QUERY_STRING'];

/*
   $phpc_root_path gives the location of the base calendar install.
   if you move this file to a new location, modify $phpc_root_path to point
   to the location where the support files for the callendar are located.
*/
if(!isset($phpc_root_path)) {
	$phpc_includes_path = dirname(__FILE__);
	$phpc_root_path = dirname($phpc_includes_path);
} elseif(!isset($phpc_includes_path)) {
	$phpc_includes_path = "$phpc_root_path/includes";
}

// Remove this line if you must
ini_set('arg_separator.output', '&amp;');

// Don't touch anything below here

define('IN_PHPC', true);

// make sure that we have _ defined
if(!function_exists('_')) {
	function _($str) { return $str; }
	$translate = FALSE;
} else {
	$translate = TRUE;
}

require_once("$phpc_includes_path/calendar.php");

// Run the installer if we have no config file
// This doesn't work when embedded from outside
if(!file_exists("$phpc_root_path/config.php")) {
        redirect('install/install.php');
        exit;
}
require_once("$phpc_root_path/config.php");
if(!defined('SQL_TYPE')) {
        redirect('install/install.php');
        exit;
}

if(file_exists("$phpc_root_path/install")) {
	soft_error(_("You must remove the install directory."));
}

// Make the database connection.
require_once("$phpc_includes_path/phpcdatabase.class.php");
$phpcdb = new PhpcDatabase;

session_start();

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

require_once("$phpc_includes_path/globals.php");

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
if($translate) {
	if(isset($vars['lang']) && in_array($vars['lang'], $languages)) {
		$lang = $vars['lang'];
		setcookie('lang', $vars['lang']);
	} elseif(isset($_COOKIE['lang'])) {
		$lang = $_COOKIE['lang'];
	} elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && in_array(
				substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2),
				$languages)) {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	} else {
		$lang = 'en';
	}

	switch($lang) {
		case 'de':
			setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
			break;
		case 'en':
			setlocale(LC_ALL, 'en_US', 'C');
			break;
                case 'es':
                        setlocale(LC_ALL, 'es_ES@euro', 'es_ES', 'es');
			break;
                case 'it':
                        setlocale(LC_ALL, 'it_IT@euro', 'it_IT', 'it');
			break;
                case 'ja':
                        setlocale(LC_ALL, 'ja_JP', 'ja');
                        break;
                case 'nl':
                        setlocale(LC_ALL, 'nl_NL@euro', 'nl_NL', 'nl');
                        break;
	}

	bindtextdomain('messages', "$phpc_root_path/locale");
	textdomain('messages');
}

if ($vars["contentType"] == "json") {
	echo do_action();
	exit;
}
?>
