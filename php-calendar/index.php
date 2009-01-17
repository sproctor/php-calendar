<?php
/*
   Copyright 2007 Sean Proctor

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
 * Do not modify anything under this point
 */

header("Content-Type: text/html; charset=UTF-8");

define('IN_PHPC', true);

$phpc_script = $_SERVER['PHP_SELF'];
$phpc_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https')
	. "://{$_SERVER['SERVER_NAME']}$phpc_script?{$_SERVER['QUERY_STRING']}";

// Run the installer if we have no config file
if(!file_exists($phpc_root_path . 'config.php')) {
        redirect('install.php');
}
require_once($phpc_root_path . 'config.php');
if(!defined('SQL_TYPE')) {
        redirect('install.php');
}

require_once($phpc_root_path . 'includes/phpcalendar.class.php');
require_once($phpc_root_path . 'includes/globals.php');
require_once($phpc_root_path . 'includes/common.php');

$vars = array();
if(get_magic_quotes_gpc()) {
        $vars = array_merge($vars, $_GET);
        $vars = array_merge($vars, $_POST);
} else {
        $vars = array_merge($vars, array_map('addslashes', $_GET));
        $vars = array_merge($vars, array_map('addslashes', $_POST));
}

session_start();

$calendar = phpc_get($phpc_script);
$calendar->set_vars($vars);

$output = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n"
	."        \"http://www.w3.org/TR/html4/strict.dtd\">\n";
$html = tag('html',
		tag('head',
			tag('title', $calendar->get_config('title')),
			tag('meta', attributes('http-equiv="Content-Type" '
					.'content="text/html; charset=UTF-8"')),
			tag('link', attributes('rel="stylesheet"'
					.' type="text/css" href="style.css"')),
			'<!--[if IE]><link rel="stylesheet" '
			.'type="text/css" href="all-ie.css">'
			.'<![endif]-->'),
		tag('body',
			tag('h1', $calendar->get_config('title')),
			$calendar->sidebar(),
			$calendar->display(),
			link_bar($calendar)));

echo $output . $html->toString();

// returns HTML data for the links at the bottom of the calendar
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
				tag('a', attributes(
						'href="http://validator.w3.org/'
						.'check?url='
						.rawurlencode($phpc_url)
						.'"'), _('Valid HTML 4.01')),
				'] [',
				tag('a', attributes(
						'href="http://jigsaw.w3.org/'
						.'css-validator/check/referer"'
						), _('Valid CSS2')),
				']'));
	return $html;
}

?>
