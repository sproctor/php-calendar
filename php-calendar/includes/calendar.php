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

include($phpc_root_path . 'includes/html.php');

function soft_error($str)
{
	echo "<html><head><title>Error</title></head>\n"
		."<body><h1>Software Error</h1><pre>$str</pre></body></html>";
	exit;
}

function db_error($str, $query = "")
{
        global $db;

        $string = "$str<br />".$db->ErrorNo().': '.$db->ErrorMsg();
        if($query != "") {
                $string .= "<br>sql: $query";
        }
        soft_error($string);
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

	$result = $db->Execute($query);
	if(!$result) {
                db_error("error checking user", $query);
	}

	if($result->FieldCount() == 0) return 0;

	$row = $result->FetchRow();

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
				if($hour >= 12) {
                                        if($hour != 12) {
                                                $hour -= 12;
                                        }
					$pm = ' PM';
                                } else {
                                        if($hour == 0) {
                                                $hour = 12;
                                        }
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
	$html = tag('html', attributes('xml:lang="en"'), 
			tag('head',
				tag('title', $config['calendar_title']),
				tag('meta',
					attributes('http-equiv="Content-Type"'
                                                .' content="text/html;'
                                                .' charset=iso-8859-1"')),
				tag('link',
                                        attributes('rel="stylesheet"'
                                                .' type="text/css" href="'
						.$_SERVER['SCRIPT_NAME']
                                                .'?action=style"'))),
			tag('body',
				tag('h1', $config['calendar_title']),
				navbar(),
				$rest,
				link_bar()));

	return $output . html_to_string($html);
}

function lang_link($lang)
{
	$str = $_SERVER['SCRIPT_NAME'] . '?';
	if(!empty($_SERVER['QUERY_STRING'])) {
		$str .= htmlentities($_SERVER['QUERY_STRING']) . '&amp;';
	}
	$str .= "lang=$lang";

	return tag('a', attributes("href=\"$str\""), $lang);
}

function link_bar()
{
	global $config;

	$html = tag('div', attributes('class="phpc-footer"'));

	if($config['translate']) {
		$html[] = tag('p', '[', lang_link('en'), '] [',
			lang_link('de'), ']');
	}

	$html[] = tag('p', '[',
			tag('a',
				attributes('href="http://validator.w3.org/'
				.'check?url='
				.rawurlencode("http://$_SERVER[SERVER_NAME]"
                                ."$_SERVER[SCRIPT_NAME]?$_SERVER[QUERY_STRING]")
                                .'"'), 'Valid XHTML 1.1'),
			'] [',
			tag('a', attributes('href="http://jigsaw.w3.org/'
					.'css-validator/check/referer"'),
					'Valid CSS2'),
			']');
	return $html;
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

	$result = $db->Execute($query);

	if(!$result) {
		db_error(_('Error in get_events_by_date'), $query);
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

	$result = $db->Execute($query);

	if(!$result) {
		db_error(_('Error in get_event_by_id'), $query);
	}

	if($result->FieldCount() == 0) {
		soft_error("item doesn't exist!");
	}

	return $result->FetchRow();
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

function create_action_link($name, $action, $id = 0, $year = 0, $month = 0,
		$day = 0)
{
	$url = "href=\"$_SERVER[SCRIPT_NAME]?action=$action";
	if($id) {
		$url .= "&amp;id=$id";
	}
	if($year) {
		$url .= "&amp;year=$year";
	}
	if($month) {
		$url .= "&amp;month=$month";
	}
	if($day) {
		$url .= "&amp;day=$day";
	}
	$url .= '"';
	return tag('a', attributes($url), $name);
}

function menu_item_append(&$html, $name, $action, $year = 0, $month = 0,
		$day = 0)
{
	$html = array_append(array_append($html,
				create_action_link($name, $action, 0, $year,
					$month, $day)), "\n");
}

function menu_item_prepend(&$html, $name, $action, $year = 0, $month = 0,
		$day = 0)
{
	$html = array_cons(create_action_link($name, $action, 0, $year, $month,
				$day), array_cons("\n", $html));
}

function create_hidden($name, $value)
{
	return tag('input', attributes("name=\"$name\"", "value=\"$value\"",
				'type="hidden"'));
}

function create_submit($value)
{
	return tag('input', attributes('name="submit"', "value=\"$value\"",
                                'type="submit"'));
}

function create_text($name, $value = NULL)
{
	$attributes = attributes("name=\"$name\"", 'type="text"');
	if($value != NULL) $attributes[] = "value=\"$value\"";
	return tag('input', $attributes);
}

function create_password($name)
{
	return tag('input', attributes("name=\"$name\"", 'type="password"'));
}

function create_checkbox($name, $value = NULL, $checked = false)
{
	$attributes = attributes("name=\"$name\"", 'type="checkbox"');
	if($value) $attributes[] = "value=\"$value\"";
	if($checked) $attributes[] = 'checked="checked"';
	return tag('input', $attributes);
}

function navbar()
{
	global $vars, $user, $action, $config, $year, $month, $day;

	$html = array();

	if(($config['anon_permission'] || isset($user)) && $action != 'add') { 
		menu_item_append($html, _('Add Event'), 'event_form', $year,
				$month, $day);
	}

	if($action != 'search') {
		menu_item_append($html, _('Search'), 'search', $year, $month,
				$day);
	}

	if(!empty($vars['day']) || !empty($vars['id']) || $action != 'display') {
		menu_item_append($html, _('Back to Calendar'), 'display',
				$year, $month);
	}

	if($action != 'display' || isset($vars['id'])) {
		menu_item_append($html, _('View date'), 'display', $year,
				$month, $day);
	}

	if(isset($user)) {
		menu_item_append($html, _('Log out'), 'logout', $year,
				$month, $day);
	} else {
		menu_item_append($html, _('Log in'), 'login', $year, $month,
				$day);
	}

	if(isset($user) && $action != 'options') {
		menu_item_append($html, _('Options'), 'options');
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

		menu_item_prepend($html, "$lastmonthname $lastday",
					'display', $lastyear, $lastmonth,
					$lastday);
		menu_item_append($html, "$nextmonthname $nextday",
				'display', $nextyear, $nextmonth, $nextday);
	}

	return array_merge(tag('div', attributes('class="phpc-navbar"')),
			$html);
}

function create_select($name, $type, $select)
{
	$html = tag('select', attributes('size="1"', "name=\"$name\""));

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
		case 'anon_perm':
			$lbound = 0;
			$ubound = 2;
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
			case 'anon_perm':
				if($i == 0) $text = _('Cannot add events');
				elseif($i == 1) $text = _('Can add but not modify events');
				elseif($i == 2) $text = _('Can add and modify events');
				break;
			default:
				$text = $i;
		}
		$attributes = attributes("value=\"$i\"");
		if ($i == $select) $attributes[] = 'selected="selected"';

		$html[] = tag('option', $attributes, $text);
	}

	return $html;
}

?>
