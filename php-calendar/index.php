<?php
/*
   Copyright 2002 Sean Proctor, Nathan Poiro

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

$calendar_name = '0';
$phpc_root_path = './';

define('SEPCOLOR',     '#000000');
define('BG_COLOR1',    '#FFFFFF');
define('BG_COLOR2',    'gray');
define('BG_COLOR3',    'silver');
define('BG_COLOR4',    '#CCCCCC');
define('BG_PAST',      'silver');
define('BG_FUTURE',    'white');
define('TEXTCOLOR1',   '#000000');
define('TEXTCOLOR2',   '#FFFFFF');

define('IN_PHPC', 1);

if(!empty($_GET) && array_key_exists('action', $_GET)
                        && $_GET['action'] == 'style') {
	include($phpc_root_path . 'includes/style.php');
	exit;
}

include($phpc_root_path . 'includes/calendar.php');
include($phpc_root_path . 'includes/setup.php');

$legal_actions = array('event_form', 'event_delete', 'display', 'event_submit',
		'search', 'login', 'logout', 'options');

if(!is_int(array_search($action, $legal_actions, true))) {
	soft_error(_('Invalid action'));
}

include($phpc_root_path . "includes/$action.php");

eval("\$output = $action();");

echo create_xhtml($output);

?>
