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

function soft_error($str)
{
	echo "<html><head><title>Error</title></head>\n"
		."<body><h1>Software Error</h1><pre>$str</pre></body></html>";
	exit;
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

function day_name($day)
{
	global $config;

	if($config['start_monday']) {
		$day = $day + 1;
	}

	$day = $day % 7;

	switch($day) {
		case 0: return _('Sunday');
		case 1: return _('Monday');
		case 2: return _('Tuesday');
		case 3: return _('Wednesday');
		case 4: return _('Thursday');
		case 5: return _('Friday');
		case 6: return _('Saturday');
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

function check_user()
{
	global $user, $password, $db, $calendar_name;

	if(!isset($user) || !isset($password) || $user == 'anonymous')
		return 0;

	$passwd = md5($password);

	$query= "SELECT uid FROM ".SQL_PREFIX."users\n"
		."WHERE username = '$user' "
		."AND password = '$passwd' "
		."AND calendar = '$calendar_name'";

	$result = $db->sql_query($query);
	if(!$result) {
		$error = $db->sql_error();
		soft_error("$error[code]: $error[message]");
	}

	if(!$db->sql_numrows($result)) return 0;

	$row = $db->sql_fetchrow($result);

	return $row['uid'];
}

function formatted_time_string($time, $type)
{
	global $config;

	switch($type) {
		default:
			preg_match('/(\d+):(\d+)/', $time, $matches);
			$hour = $matches[1];
			$minute = $matches[2];

			if(!$config['hours_24']) {
				if($hour > 12) {
					$hour -= 12;
					$pm = ' PM';
				} else {
					$pm = ' AM';
				}
			} else {
				$pm = '';
			}

			return sprintf('%d:%02d%s', $hour, $minute, $pm);
		case 2:
			return _('FULL DAY');
		case 3:
			return '??:??';
	}
}

function event_type($num)
{
	switch($num) {
		case 1:
			return _('Normal');
		case 2:
			return _('Full Day');
		case 3:
			return _('Unknown Time');
		case 4:
			//return _('Daily');
			return false;
		case 5:
			return _('Weekly');
		case 6:
			return _('Monthly');
	}

	return false;
}

function create_xhtml($rest)
{
	global $config;

	$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		."\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
	$html = array('html', attributes('xml:lang="en"'), 
			array('head',
				array('title', $config['calendar_title']),
				array('meta',
					attributes('http-equiv="Content-Type"'
						.' content="text/html;'
						.' charset=iso-8859-1"')),
				array('link',
					attributes('rel="stylesheet"'
						.' type="text/css"'
						.' href="index.php?action='
						.'style"'))),
			array('body',
				array('h1', $config['calendar_title']),
				$rest,
				link_bar()));

	return $output;
}

function lang_link($lang)
{
	global $SCRIPT_NAME, $QUERY_STRING;

	$str = $SCRIPT_NAME . '?';
	if(!empty($QUERY_STRING)) {
		$str .= htmlentities($QUERY_STRING) . '&amp;';
	}
	$str .= "lang=$lang";

	return array('a', attributes("href=\"$str\""), $lang);
}

function link_bar()
{
	global $SERVER_NAME, $SCRIPT_NAME, $QUERY_STRING, $config;

	$html = array();

	if($config['translate']) {
		$html[] = array('p', '[', lang_link('en'), '] [',
			lang_link('de'), ']');
	}

	$html[] = array('p', '[',
			array('a',
				attributes('href="http://validator.w3.org/'
				.'check?url='
				.rawurlencode("http://$SERVER_NAME$SCRIPT_NAME"
				."?$QUERY_STRING") . '"'), 'Valid XHTML 1.1'),
			'] [',
			array('a', attributes('href="http://jigsaw.w3.org/'
					.'css-validator/check/referer"'),
					'Valid CSS2'),
			']');

	return array('div', attributes('class="phpc-footer"'), $html);
}

function get_events_by_date($day, $month, $year)
{
	global $calendar_name, $db;

/* event types:
1 - Normal event
2 - full day event
3 - unknown time event
4 - reserved
5 - weekly event
6 - monthly event
*/
	$query = 'SELECT * FROM '.SQL_PREFIX."events\n"
		."WHERE (startdate <= '$year-$month-$day'\n"
		."AND enddate >= '$year-$month-$day'"
//		."AND (eventtype = 4 OR eventtype = 5"
//		." OR eventtype = 6)"
//		." OR startdate = '$year-$month-$day')\n"
		.")\n"
		."AND calendar = '$calendar_name'\n"
		."AND (eventtype != 5 OR DAYOFWEEK(startdate) = "
		."DAYOFWEEK(DATE '$year-$month-$day'))\n"
		."AND (eventtype != 6 OR DAYOFMONTH(startdate) = '$day')\n"
		."ORDER BY starttime";

	$result = $db->sql_query($query);

	if(!$result) {
		$error = $db->sql_error();
		soft_error(_('Error in get_events_by_date').": $error[code]: $error[message]\nsql:\n$query");
	}

	return $result;
}

function get_event_by_id($id)
{
	global $calendar_name, $db;

	$events_table = SQL_PREFIX . 'events';
	$users_table = SQL_PREFIX . 'users';

	$query = "SELECT $events_table.*,\n"
		."YEAR($events_table.startdate) AS year,\n"
		."MONTH($events_table.startdate) AS month,\n"
		."DAYOFMONTH($events_table.startdate) AS day,\n"
		."$users_table.username\n"
		."FROM $events_table\n"
		."LEFT JOIN $users_table\n"
		."ON ($events_table.uid = $users_table.uid)\n"
		."WHERE $events_table.id = '$id'\n"
		."AND $events_table.calendar = '$calendar_name';";

	$result = $db->sql_query($query);

	if(!$result) {
		$error = $db->sql_error();
		soft_error(_('Error in get_event_by_id')
				." $error[code]: $error[message]\n"
				."sql:\n$query");
	}

	if($db->sql_numrows($result) == 0) {
		soft_error("item doesn't exist!");
	}

	return $db->sql_fetchrow($result);
}

function parse_desc($text)
{

	// get out the crap, put in breaks
	$text = nl2br(stripslashes($text));

	//urls
	$text = preg_replace("/([[:alpha:]]+:\\/\\/[^<>\s]+[\\w\\/])/i",
			"<a href=\"$1\">$1</a>", $text);


	// emails
	$text = preg_replace("/([a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*"
			."[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z])/",
			"<a href=\"mailto:$1\">$1</a>", $text );

	return $text;
}

function day_of_first($month, $year)
{
	global $config;

	if(!$config['start_monday'])
		return date('w', mktime(0, 0, 0, $month, 1, $year));
	else
		return (date('w', mktime(0, 0, 0, $month, 1, $year)) + 6) % 7;
}

function days_in_month($month, $year)
{
	return date('t', mktime(0, 0, 0, $month, 1, $year));
}

function weeks_in_month($month, $year)
{
	return ceil((day_of_first($month, $year)
				+ days_in_month($month, $year)) / 7);
}

function navbar()
{
	global $vars, $year, $month, $day, $user, $action, $config, $PHP_SELF;

	$html = array();

	if(($config['anon_permission'] || isset($user)) && $action != 'add') { 
		$html[] = "<a href=\"index.php?action=event_form&amp;day=$day"
			."&amp;month=$month&amp;year=$year\">"._('Add Item')
			."</a>\n";
	}

	if($action != 'search') {
		$output .= "<a href=\"index.php?action=search&amp;day=$day"
			."&amp;month=$month&amp;year=$year\">"._('Search')
			."</a>\n";
	}

	if($action != 'display' || !empty($vars['display'])
			|| !empty($vars['id'])) {
		$output .= "<a href=\"index.php?month=$month&amp;year=$year\">"
			._('Back to Calendar')."</a>\n";
	}

	if($action != 'display' || isset($vars['id'])) {
		$output .= "<a href=\"index.php?action=display&amp;day=$day"
			."&amp;month=$month&amp;year=$year\">"._('View date')
			."</a>\n";
	}

	if(isset($user)) {
		$output .= "<a href=\"index.php?action=logout&amp;"
			."lastaction=$action&amp;day=$day&amp;month=$month&amp;"
			."year=$year\">"._('Log out')."</a>\n";
	} else {
		$output .= "<a href=\"index.php?action=login&amp;"
			."lastaction=$action&amp;day=$day&amp;month=$month&amp;"
			."year=$year\">"._('Log in')."</a>\n";
	}

	if(isset($user) && $action != 'options') {
		$output .= "<a href=\"index.php?action=options\">"._('Options')
			."</a>\n";
	}

	if(isset($var['display']) && $var['display'] == 'day') {
		$monthname = month_name($month);

		$lasttime = mktime(0, 0, 0, $month, $day - 1, $year);
		$lastday = date('j', $lasttime);
		$lastmonth = date('n', $lasttime);
		$lastyear = date('Y', $lasttime);
		$lastmonthname = month_name($lastmonth);

		$nexttime = mktime(0, 0, 0, $month, $day + 1, $year);
		$nextday = date('j', $nexttime);
		$nextmonth = date('n', $nexttime);
		$nextyear = date('Y', $nexttime);
		$nextmonthname = month_name($nextmonth);

		$output = "<a href=\"index.php?action=display&amp;day=$lastday"
			."&amp;month=$lastmonth&amp;year=$lastyear\">"
			."$lastmonthname $lastday</a>\n"
			.$output
			."<a href=\"index.php?action=display&amp;day=$nextday"
			."month=$nextmonth&amp;day=$nextday&amp;year=$nextyear"
			."\">$nextmonthname $nextday</a>\n";
	}

	$output = "<div class=\"phpc-navbar\">$output</div>\n";

	if($action == 'display' && empty($vars['display'])
			&& empty($vars['id'])) {
		$output = month_navbar() . $output;
	}

	return $output;
}

function create_select($name, $type, $select)
{
	$output = "<select size=\"1\" name=\"$name\">\n";

	switch($type){
		case 'minute':
			$lbound = 0;
			$ubound = 59;
			$increment = 5;
			break;
		case '24hour':
			$lbound = 0;
			$ubound = 23;
			$increment = 1;
			break;
		case '12hour':
			$lbound = 1;
			$ubound = 12;
			$increment = 1;
			break;
		case 'day':
			$lbound = 1;
			$ubound = 31;
			$increment = 1;
			break;
		case 'month':
			$lbound = 1;
			$ubound = 12;
			$increment = 1;
			break;
		case 'year':
			$lbound = $select - 2;
			$ubound = $select + 3;
			$increment = 1;
			break;
		case 'event':
			$lbound = 1;
			$ubound = 6;
			$increment = 1;
			break;
		default:
			soft_error('Invalid select type');
	}

	for ($i = $lbound; $i <= $ubound; $i += $increment){
		switch($type) {
			case 'month':
				$text = month_name($i);
				break;
			case 'event':
				$text = event_type($i) . ' ' . _('Event');
				//nasty hack because 4 is reserved.
				if($i == 4) continue(2);
				break;
			case 'minute':
				$text = sprintf('%02d', $i);
				break;
			default:
				$text = $i;
		}
		if ($i == $select) {
			$output .= "<option value=\"$i\" selected="
				."\"selected\">$text</option>\n";
		} else {
			$output .= "<option value=\"$i\">$text</option>\n";
		}
	}

	$output .= "</select>\n";

	return $output;
}

?>
