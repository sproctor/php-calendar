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

include('config.inc.php');

function soft_error($str)
{
	echo "<html><head><title>Error</title></head><body><h1>Software Error</h1><p>$str</p></body></html>";
	exit;
}

function browser()
{
	global $HTTP_USER_AGENT, $BName, $BVersion;

	if(eregi('opera/?([0-9]+(\.[0-9]+)*)?', $HTTP_USER_AGENT, $match)) {
		$BName = 'Opera';
		$BVersion = $match[1];
	} elseif(eregi('konqueror/([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
		$BName = "Konqueror";
		$BVersion = $match[1];
	} elseif(eregi('lynx/([0-9]+.[0-9]+.[0-9]+)', $HTTP_USER_AGENT,
				$match)) {
		$BName = 'Lynx';
		$BVersion = $match[1];
	} elseif(eregi("links\(([0-9]+.[0-9]+)", $HTTP_USER_AGENT, $match)) {
		$BName = 'Links';
		$BVersion = $match[1];
	} elseif(eregi('msie ?([0-9]+.[0-9]+)', $HTTP_USER_AGENT, $match)) {
		$BName = 'MSIE';
		$BVersion = $match[1];
	} elseif(eregi('(netscape6|mozilla)/([0-9]+.[0-9]+)',
				$HTTP_USER_AGENT, $match)) {
		$BName = 'Netscape';
		$BVersion = $match[2];
	} elseif(eregi('w3m', $HTTP_USER_AGENT)) {
		$BName = 'w3m';
		$BVersion = 'Unknown';
	} else {
		$BName = 'Unknown';
		$BVersion = 'Unknown';
	}
}

function connect_to_database()
{
	$database = mysql_connect(SQL_HOSTNAME, SQL_USERNAME, SQL_PASSWORD)
		or soft_error(_('Couldn\'t connect to database server'));
	mysql_select_db(SQL_DATABASE, $database)
		or soft_error(_('Couldn\'t open database'));

	return $database;
}

function translate()
{
	global $HTTP_ACCEPT_LANGUAGE, $HTTP_GET_VARS, $HTTP_COOKIE_VARS;

	if(!function_exists('_')) {
		function _($str) { return $str; }
		return;
	}

	if(!TRANSLATE) {
		return;
	}

	if(isset($HTTP_GET_VARS['lang'])) {
		$lang = substr($HTTP_GET_VARS['lang'], 0, 2);
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
			setlocale('LC_ALL', 'de_DE');
			break;
		case 'en':
			setlocale('LC_ALL', 'en_US');
			break;
	}

	bindtextdomain('messages', './locale');
	textdomain('messages');
}

function month_name($month)
{
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:  return _('January');
		case 2:  return _('February');
		case 3:  return _('March');
		case 4:  return _('April');
		case 5:  return _('May');
		case 6:  return _('June');
		case 7:  return _('July');
		case 8:  return _('August');
		case 9:  return _('September');
		case 10: return _('October');
		case 11: return _('November');
		case 12: return _('December');
	}
}

function short_month_name($month)
{
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:  return _('Jan');
		case 2:  return _('Feb');
		case 3:  return _('Mar');
		case 4:  return _('Apr');
		case 5:  return _('May');
		case 6:  return _('Jun');
		case 7:  return _('Jul');
		case 8:  return _('Aug');
		case 9:  return _('Sep');
		case 10: return _('Oct');
		case 11: return _('Nov');
		case 12: return _('Dec');
	}
}

function top()
{
	global $BName, $BVersion;
	translate();
	browser();
	$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		."\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		."<html xml:lang=\"en\">\n"
		."<head>\n"
		.'<title>'.TITLE."</title>\n"
		.'<meta http-equiv="Content-Type" '
		."content=\"text/html; charset=iso-8859-1\" />\n";

	$output .= "<!-- Your browser: $BName $BVersion -->";
	$output .= '<link rel="stylesheet" type="text/css" href="style.css.php"'
		." />\n";
	if($BName == 'MSIE') {
		$output .= '<link rel="stylesheet" type="text/css" '
			."href=\"style-ie.css\" />\n";
	}

	return $output."</head>\n<body>\n<h1>".TITLE."</h1>\n";
}

function lang_link($lang)
{
	global $PHP_SELF, $QUERY_STRING;

	$str = '[<a href="' . $PHP_SELF . '?';
	if(!empty($QUERY_STRING)) {
		$str .= $QUERY_STRING . '&amp;';
	}

	$str .= "lang=$lang\">$lang</a>]\n";
	return $str;
}

function print_footer()
{
	global $translate, $SERVER_NAME, $SCRIPT_NAME, $QUERY_STRING;
	$output = '';

	if(!empty($translate)) {
		$output .= "<div>\n"
			.lang_link('en')
			.lang_link('de')
			."</div>\n";
	}

	return $output . "<p class=\"phpc-footer\">\n"
		.'[<a href="http://validator.w3.org/check?url='
		. rawurlencode("http://$SERVER_NAME$SCRIPT_NAME?$QUERY_STRING")
		.'">'._('Valid XHTML 1.1').'</a>]'
		.' [<a href="http://jigsaw.w3.org/css-validator/check/referer">'
		._('Valid CSS2')."</a>]\n</p>\n";
}

function bottom()
{
	return print_footer() . '</body>
		</html>';
}

function get_events_by_date($day, $month, $year)
{
	$database = connect_to_database();

	$result = mysql_query('SELECT UNIX_TIMESTAMP(stamp) as start_since_epoch,
			UNIX_TIMESTAMP(duration) as end_since_epoch, username, subject,
			description, eventtype, id
			FROM ' . SQL_PREFIX . "events
			WHERE duration >= \"$year-$month-$day 00:00:00\" 
			AND stamp <= \"$year-$month-$day 23:59:59\" ORDER BY stamp", $database)
		or soft_error(_('get_events_by_date failed'));

	return $result;
}

function get_event_by_id($id)
{
	$database = connect_to_database();

	$result = mysql_query('SELECT UNIX_TIMESTAMP(stamp) AS start_since_epoch,
			UNIX_TIMESTAMP(duration) AS end_since_epoch, username, subject,
			description, eventtype FROM ' . SQL_PREFIX . "events
			WHERE id = '$id'", $database)
		or soft_error(_('get_event_by_id failed'));
	if(mysql_num_rows($result) == 0) {
		soft_error(_('item doesn\'t exist!'));
	}

	return $result;
}

function back_to_calendar()
{
	global $HTTP_GET_VARS;

	if(!isset($HTTP_GET_VARS['day'])) $day = date("j");
	else $day = $HTTP_GET_VARS['day'];

	if(!isset($HTTP_GET_VARS['month'])) $month = date("n");
	else $month = $HTTP_GET_VARS['month'];

	if(!isset($HTTP_GET_VARS['year'])) $year = date("Y");
	else $year = $HTTP_GET_VARS['year'];

	return "<div class=\"phpc-navbar\">\n"
		."<a href=\"display.php?month=$month&amp;year=$year&amp;"
		."day=$day\">"._('View date')."</a>\n"
		."<a href=\"index.php?month=$month&amp;year=$year\">"
		._('Back to Calendar')."</a>\n"
		."</div>\n";
}
?>
