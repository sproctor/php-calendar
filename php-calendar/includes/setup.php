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

/* FIXME: This file is a fucking mess, clean it up */

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

require_once($phpc_root_path . 'config.php');

$phpc_script = $_SERVER['SCRIPT_NAME'];
$phpc_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https')
	. "://{$_SERVER['SERVER_NAME']}$phpc_script?{$_SERVER['QUERY_STRING']}";


// SQL codes
define('BEGIN_TRANSACTION', 1);
define('END_TRANSACTION', 2);

require_once($phpc_root_path . 'includes/db.php');

foreach($_GET as $key => $value) {
	$vars[$key] = $value;
}

foreach($_POST as $key => $value) {
	$vars[$key] = $value;
}

session_start();

unset($user);
unset($password);

if(isset($_SESSION['user'])) {
  $user = $_SESSION['user'];
}
if(isset($_SESSION['password'])) {
  $password = $_SESSION['password'];
}

/*
   echo "<pre>get vars:</pre>";
   foreach ($_GET as $key=>$val){
   echo "<pre>$key: $val</pre>";
   }
   echo "<pre>post vars:</pre>";
   foreach ($_POST as $key=>$val) {
   echo "<pre>$key: $val</pre>";
   }
   echo "<pre>all vars:</pre>";
   foreach ($vars as $key=>$val) {
   echo "<pre>$key: $val</pre>";
   }
 */

$currentday = date('j');
$currentmonth = date('n');
$currentyear = date('Y');

if (!isset($vars['month'])) {
	$month = $currentmonth;
} else {
	$month = $vars['month'];
}

if(!isset($vars['year'])) {
	$year = $currentyear;
} else {
	$year = date('Y', mktime(0,0,0,$month,1,$vars['year']));
}

if(!isset($vars['day'])) {
	if($month == $currentmonth) $day = $currentday;
	else $day = 1;
} else {
	$day = ($vars['day'] - 1) % date("t", mktime(0,0,0,$month,1,$year)) + 1;
}

while($month < 1) $month += 12;
$month = ($month - 1) % 12 + 1;

if(empty($vars['action'])) {
	$action = 'display';
} else {
	$action = $vars['action'];
}

if(!empty($vars['calendar_name'])) {
        $calendar_name = $vars['calendar_name'];
}

$query = "SELECT * from ".SQL_PREFIX."calendars "
."WHERE calendar='$calendar_name'";

$result = $db->Execute($query);

if(!$result) {
	soft_error(_('Could not read configuration').": ".$db->ErrorMsg()
        .': '.$error[message].": $query");
}

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
