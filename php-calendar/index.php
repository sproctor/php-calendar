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

ob_start();
session_start();

include 'miniconfig.inc.php';
include "$basedir/calendar.inc.php";

unset($user);
$user = $HTTP_SESSION_VARS['user'];

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

if (!isset($HTTP_GET_VARS['month'])) {
	$month = $currentmonth;
} else {
	$month = $HTTP_GET_VARS['month'];
}

if(!isset($HTTP_GET_VARS['year'])) {
	$year = $currentyear;
} else {
	$year = date('Y', mktime(0,0,0,$month,1,$HTTP_GET_VARS['year']));
}

if(!isset($HTTP_GET_VARS['day'])) {
	if($month == $currentmonth) $day = $currentday;
	else $day = 1;
} else {
	$day = ($HTTP_GET_VARS['day'] - 1) % date("t", mktime(0,0,0,$month,1,$year)) + 1;
}

while($month < 1) $month += 12;
$month = ($month - 1) % 12 + 1;

if(empty($HTTP_GET_VARS['action'])) {
	$action = 'main';
} else {
	$action = $HTTP_GET_VARS['action'];
}

if(isset($HTTP_POST_VARS['login'])) $action = 'login';

switch($action) {
	case 'add':

		include "$basedir/event_form.inc.php";
		$output = event_form('add');
		break;

	case 'delete':

		include "$basedir/event_delete.inc.php";
		$output = delete();
		break;

	case 'display':

		include "$basedir/display.inc.php";
		$output = display();
		break;

	case 'submit':

		include "$basedir/event_submit.inc.php";
		$output = submit_event();
		break;

	case 'modify':

		include "$basedir/event_form.inc.php";
		$output = event_form('modify');
		break;

	case 'search':

		include "$basedir/search.inc.php";
		$output = search();
		break;

	case 'login':

		include "$basedir/login.inc.php";
		$output = login();
		break;

	case 'logout':
		include "$basedir/logout.inc.php";
		$output = logout();
		break;

	case 'main':

		include "$basedir/main.inc.php";
		$output = calendar();
		break;

}

echo top() . navbar() . $output . bottom();

?>
