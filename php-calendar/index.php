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

/*
   This file sets up the basics of the calendar. It can be copied to produce
   a new calendar using the same configuration and database.
*/

/*
   modify $calendar_id when you create another calendar so that the calendars
   will not all share the same data
*/
$calendar_id = 0;

/*
   $phpc_root_path gives the location of the base calendar install.
   if you move this file to a new location, modify $phpc_root_path to point
   to the location where the support files for the callendar are located.
*/
$phpc_root_path = './';

/*
   You can modify the following defines to change the color scheme of the
   calendar
*/
define('SEPCOLOR',      '#000000');
define('BG_COLOR1',     '#FFFFFF');
define('BG_COLOR2',     'gray');
define('BG_COLOR3',     'silver');
define('BG_COLOR4',     '#CCCCCC');
define('BG_PAST',       'silver');
define('BG_FUTURE',     'white');
define('BG_INACTIVE',   'silver');
define('TEXTCOLOR1',    '#000000');
define('TEXTCOLOR2',    '#FFFFFF');

/*
 * Do not modify anything under this point
 */

define('IN_PHPC', true);

if(!empty($_GET['action']) && $_GET['action'] == 'style') {
	require_once($phpc_root_path . 'includes/style.php');
	exit;
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
require_once($phpc_root_path . 'includes/globals.php');
require_once($phpc_root_path . 'includes/common.php');

$phpc_script = $_SERVER['SCRIPT_NAME'];
$phpc_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https')
	. "://{$_SERVER['SERVER_NAME']}$phpc_script?{$_SERVER['QUERY_STRING']}";

$legal_actions = array('event_form', 'event_delete', 'display', 'event_submit',
		'search', 'login', 'logout', 'admin', 'options_submit',
                'new_user_submit');

if(!empty($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
} else {
        $action = 'display';
}

if(!in_array($action, $legal_actions, true)) {
	soft_error(_('Invalid action'));
}

$vars = array();
if(get_magic_quotes_gpc()) {
        $vars = array_merge($vars, $_GET);
        $vars = array_merge($vars, $_POST);
} else {
        $vars = array_merge($vars, array_map('addslashes', $_GET));
        $vars = array_merge($vars, array_map('addslashes', $_POST));
}

if(empty($vars['action'])) {
	$action = 'display';
} else {
	$action = $vars['action'];
}

$calendar = new Calendar($phpc_script, $vars);

$output = $calendar->$action();

echo create_xhtml($output);

// takes some xhtml data fragment and adds the calendar-wide menus, etc
// returns a string containing an XHTML document ready to be output
function create_xhtml($rest)
{
	global $phpc_script, $calendar, $action;

	$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		."\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
	$html = tag('html', attributes('xml:lang="en"'), 
			tag('head',
				tag('title', $calendar->get_config('title')),
				tag('meta',
					attributes('http-equiv="Content-Type"'
                                                .' content="text/html;'
                                                .' charset=iso-8859-1"')),
				tag('link',
					attributes('rel="stylesheet"'
						.' type="text/css" href="'
						.$phpc_script
						.'?action=style"')),
				'<!--[if IE]><link rel="stylesheet" '
				.'type="text/css" href="all-ie.css" />'
				.'<![endif]-->'),
			tag('body',
				tag('h1', $calendar->get_config('title')),
				$calendar->navbar($action),
				$rest,
				link_bar($calendar)));

	return $output . $html->toString();
}

// returns XHTML data for the links at the bottom of the calendar
function link_bar($calendar)
{
	global $phpc_url;

	$html = tag('div', attributes('class="phpc-footer"'));

	if($calendar->get_config('translate')) {
		$html->add(tag('p', '[', $calendar->create_link('en',
                                                array('lang' => 'en')), '] [',
                                        $calendar->create_link('de',
                                                array('lang' => 'de')), ']'));
        }

	$html->add(tag('p', '[',
			tag('a',
				attributes('href="http://validator.w3.org/'
					.'check?url='
					.rawurlencode($phpc_url)
					.'"'), _('Valid XHTML 1.1')),
			'] [',
			tag('a', attributes('href="http://jigsaw.w3.org/'
					.'css-validator/check/referer"'),
					_('Valid CSS2')),
			']'));
	return $html;
}


?>
