<?php
/*
   Copyright 2002 Sean Proctor

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

/* FIXME: This file is a fucking mess, clean it up */

include($phpc_root_path . 'config.php');

// SQL codes
define('BEGIN_TRANSACTION', 1);
define('END_TRANSACTION', 2);

include($phpc_root_path . 'includes/db.php');

if(!function_exists('_')) {
	function _($str) { return $str; }
	$no_gettext = 1;
}

foreach($HTTP_GET_VARS as $key => $value) {
	$vars[$key] = $value;
}

foreach($HTTP_POST_VARS as $key => $value) {
	$vars[$key] = $value;
}

session_start();

ini_set('arg_separator.output', "&amp;");

unset($user);
unset($password);

if(isset($HTTP_SESSION_VARS['user'])) $user = $HTTP_SESSION_VARS['user'];
if(isset($HTTP_SESSION_VARS['password']))
$password = $HTTP_SESSION_VARS['password'];

/*
   echo "<pre>get vars:</pre>";
   foreach ($HTTP_GET_VARS as $key=>$val){
   echo "<pre>$key: $val</pre>";
   }
   echo "<pre>post vars:</pre>";
   foreach ($HTTP_POST_VARS as $key=>$val) {
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
	$action = 'main';
} else {
	$action = $vars['action'];
}

$query = "SELECT * from ".SQL_PREFIX."calendars "
."WHERE calno='$calno'";

$result = $db->sql_query($query);

if(!$result) {
	$error = $db->sql_error();
	soft_error(_('Could not read configuration').": $error[code]: $error[message]: $query");
}

$config = $db->sql_fetchrow($result);

if($config['translate'] && empty($no_gettext)) {

	if(isset($vars['lang'])) {
		$lang = substr($vars['lang'], 0, 2);
		setcookie('lang', $lang);
	} elseif(isset($HTTP_COOKIE_VARS['lang'])) {
		$lang = substr($HTTP_COOKIE_VARS['lang'], 0, 2);
	} elseif(isset($HTTP_ACCEPT_LANGUAGE)) {
		$lang = substr($HTTP_ACCEPT_LANGUAGE, 0, 2);
	} else {
		$lang = 'en';
	}

	switch($lang) {
		case 'de':
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
