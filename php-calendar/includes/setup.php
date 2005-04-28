<?php
/*
   Copyright 2002 - 2005 Sean Proctor, Nathan Poiro

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/*
   This file sets up the global variables to be used later
*/

// Modify these if you need to
$phpc_script = $_SERVER['SCRIPT_NAME'];
$phpc_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https')
	. "://{$_SERVER['SERVER_NAME']}$phpc_script?{$_SERVER['QUERY_STRING']}";

/* FIXME: This file is a fucking mess, clean it up */

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

// Run the installer if we have no config file
if(!file_exists($phpc_root_path . 'config.php')) {
        header('Location: install.php');
        exit;
}
require_once($phpc_root_path . 'config.php');
if(!defined('SQL_TYPE')) {
        header('Location: install.php');
        exit;
}

require_once($phpc_root_path . 'includes/calendar.php');
require_once($phpc_root_path . 'adodb/adodb.inc.php');

// Make the database connection.
$db = NewADOConnection(SQL_TYPE);
if(!$db->Connect(SQL_HOST, SQL_USER, SQL_PASSWD, SQL_DATABASE)) {
        db_error(_("Could not connect to the database"));
}

session_start();

$vars = array();
if(get_magic_quotes_gpc()) {
        $vars = array_merge($vars, $_GET);
        $vars = array_merge($vars, $_POST);
} else {
        $vars = array_merge($vars, array_map('addslashes', $_GET));
        $vars = array_merge($vars, array_map('addslashes', $_POST));
}

if (!isset($vars['month'])) {
	$month = date('n');
} else {
	$month = $vars['month'];
}

if(!isset($vars['year'])) {
	$year = date('Y');
} else {
        if($vars['year'] > 2037) {
                soft_error(_('That year is too far in the future')
                        . ": {$vars['year']}");
        } elseif($vars['year'] < 1970) {
                soft_error(_('That year is too far in the past')
                        . ": {$vars['year']}");
        }
	$year = date('Y', mktime(0, 0, 0, $month, 1, $vars['year']));
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

if(empty($vars['action'])) {
	$action = 'display';
} else {
	$action = $vars['action'];
}

if(!empty($vars['calendar_id'])) {
        $calendar_id = $vars['calendar_id'];
}

$query = "SELECT * from ".SQL_PREFIX."calendars\n"
."WHERE id=$calendar_id\n"
."LIMIT 0,1";

$result = $db->Execute($query)
        or db_error(_('Could not read configuration') . ": $query");

$config = $result->FetchRow($result);

if($config['translate'] && empty($no_gettext)) {

	if(isset($vars['lang'])) {
		$lang = substr($vars['lang'], 0, 2);
		setcookie('lang', $lang);
	} elseif(isset($_COOKIE['lang'])) {
		$lang = substr($_COOKIE['lang'], 0, 2);
	} elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	} else {
		$lang = 'en';
	}

	switch($lang) {
		case 'de':
                        putenv("LANGUAGE=de_DE");
			putenv("LANG=de_DE");
			setlocale(LC_ALL, 'de_DE');
			break;
		case 'en':
			setlocale(LC_ALL, 'en_US');
			break;
	}

	bindtextdomain('messages', $phpc_root_path . 'locale');
	textdomain('messages');
}
?>
