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
   this file contains all the re-usable functions for the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

include($phpc_root_path . 'includes/html.php');

// make sure that we have _ defined
if(!function_exists('_')) {
	function _($str) { return $str; }
	$no_gettext = 1;
}

// called when some error happens
function soft_error($str)
{
	echo '<html><head><title>'._('Error')."</title></head>\n"
		.'<body><h1>'._('Software Error')."</h1>\n"
                ."<h2>"._('Message:')."</h2>\n"
		."<pre>$str</pre>\n";
        if(version_compare(phpversion(), '4.3.0', '>=')) {
                echo "<h2>"._('Backtrace')."</h2>\n";
                echo "<ol>\n";
                foreach(debug_backtrace() as $bt) {
                        echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
                }
                echo "</ol>\n";
        }
        echo "</body></html>\n";
	exit;
}

// called when there is an error involving the DB
function db_error($str, $query = "")
{
        global $db;

        $string = "$str<br />".$db->ErrorNo().': '.$db->ErrorMsg();
        if($query != "") {
                $string .= "<br />"._('SQL query').": $query";
        }
        soft_error($string);
}

$month_names = array(
                1 => _('January'),
                _('February'),
                _('March'),
                _('April'),
                _('May'),
                _('June'),
                _('July'),
                _('August'),
                _('September'),
                _('October'),
                _('November'),
                _('December'),
                );

// takes a number of the month, returns the name
function month_name($month)
{
        global $month_names;

	$month = ($month - 1) % 12 + 1;
        return $month_names[$month];
}

$day_names = array(
                _('Sunday'),
		_('Monday'),
		_('Tuesday'),
		_('Wednesday'),
		_('Thursday'),
		_('Friday'),
		_('Saturday'),
                );

if($config['start_monday']) {
        $sunday = array_shift($day_names);
        array_push($day_names, $sunday);
        print_r($day_names);
}

//takes a day number of the week, returns a name
function day_name($day)
{
	global $config, $day_names;

	if($config['start_monday']) {
		$day = $day + 1;
	}

	$day = $day % 7;

        return $day_names[$day];
}

$short_month_names = array(
		1 => _('Jan'),
		_('Feb'),
		_('Mar'),
		_('Apr'),
		_('May'),
		_('Jun'),
		_('Jul'),
		_('Aug'),
		_('Sep'),
		_('Oct'),
		_('Nov'),
		_('Dec'),
                );

function short_month_name($month)
{
        global $short_month_names;

	$month = ($month - 1) % 12 + 1;
        return $short_month_names[$month];
}

// checks global variables to see if the user is logged in.
// if so, returns the UID, otherwise returns 0
function check_user()
{
	if(empty($_SESSION['user']) || $_SESSION['user'] == 'anonymous') {
		return false;
        } else {
                return true;
        }
}

function get_uid($user)
{
        global $calendar_name, $db;

	$query= "SELECT uid FROM ".SQL_PREFIX."users\n"
		."WHERE username = '$user'";

	$result = $db->Execute($query)
                or db_error("error checking user", $query);

	$row = $result->FetchRow();

        if(empty($row)) return 0;

	return $row['uid'];
}

function verify_user($user, $password)
{
        global $db;

        $passwd = md5($password);

	$query= "SELECT uid FROM ".SQL_PREFIX."users\n"
		."WHERE username='$user' "
                ."AND password='$passwd' ";

	$result = $db->Execute($query)
                or db_error("error checking user", $query);

        if($result->FieldCount() <= 0)
                return false;

	return true;
}

// takes a time string, and formats it according to type
// returns the formatted string
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
			return _('TBA');
                case 4:
                        return '';
	}
}

$event_types = array(
                1 => _('Normal'),
                _('Full Day'),
                _('To Be Announced'),
                _('No Time'),
                _('Weekly'),
                _('Monthly'),
                );

// takes some xhtml data fragment and adds the calendar-wide menus, etc
// returns a string containing an XHTML document ready to be output
function create_xhtml($rest)
{
	global $config, $phpc_script;

	$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		."\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
	$html = tag('html', attributes('xml:lang="en"'), 
			tag('head',
				tag('title', $config['title']),
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
				tag('h1', $config['title']),
				navbar(),
				$rest,
				link_bar()));

	return $output . $html->toString();
}

// returns XHTML data for a link for $lang
function lang_link($lang)
{
	global $phpc_script;

	$str = "$phpc_script?";
	if(!empty($_SERVER['QUERY_STRING'])) {
		$str .= htmlentities($_SERVER['QUERY_STRING']) . '&amp;';
	}
	$str .= "lang=$lang";

	return tag('a', attributes("href=\"$str\""), $lang);
}

// returns XHTML data for the links at the bottom of the calendar
function link_bar()
{
	global $config, $phpc_url;

	$html = tag('div', attributes('class="phpc-footer"'));

	if($config['translate']) {
		$html->add(tag('p', '[', lang_link('en'), '] [',
			lang_link('de'), ']'));
	}

	$html->add(tag('p', '[',
			tag('a',
				attributes('href="http://validator.w3.org/'
					.'check?url='
					.rawurlencode($phpc_url)
					.'"'), 'Valid XHTML 1.1'),
			'] [',
			tag('a', attributes('href="http://jigsaw.w3.org/'
					.'css-validator/check/referer"'),
					'Valid CSS2'),
			']'));
	return $html;
}

// returns all the events for a particular day
function get_events_by_date($day, $month, $year)
{
	global $calendar_id, $db;

/* event types:
1 - Normal event
2 - full day event
3 - unknown time event
4 - reserved
5 - weekly event
6 - monthly event
*/
        $startdate = $db->SQLDate('Y-m-d', 'occurrences.start_date');
        $enddate = $db->SQLDate('Y-m-d', 'occurrences.end_date');
        $date = "DATE '" . date('Y-m-d', mktime(0, 0, 0, $month, $day, $year))
                . "'";
        // day of week
        $dow_date = $db->SQLDate('w', $date);
        // day of month
        $dom_date = $db->SQLDate('d', $date);

        $query = 'SELECT * FROM '.SQL_PREFIX."events AS events,
                ".SQL_PREFIX."occurrences AS occurrences
                        WHERE occurrences.event_id=events.id
                        AND (occurrences.start_date IS NULL
                                        OR $date >= $startdate)
                        AND (occurrences.end_date IS NULL OR $date <= $enddate)
                        AND (occurrences.day_of_week IS NULL
                                        OR occurrences.day_of_week = $dow_date)
                        AND (occurrences.day_of_month IS NULL
                                        OR occurrences.day_of_month = $dom_date)
                        AND (occurrences.month IS NULL
                                        OR occurrences.month = $month)
                        AND (occurrences.nth_in_month IS NULL
                                        OR occurrences.nth_in_month =
                                        FLOOR(MOD($dom_date, 7)))
                        AND events.calendar_id = $calendar_id
                        ORDER BY events.time";

	$result = $db->Execute($query)
		or db_error(_('Error in get_events_by_date'), $query);

	return $result;
}

// returns the event that corresponds to $id
function get_event_by_id($id)
{
	global $calendar_id, $db;

	$query = "SELECT events.*,\n"
		.$db->SQLDate('Y', "occurrences.start_date")." AS year,\n"
		.$db->SQLDate('m', "occurrences.start_date")." AS month,\n"
		.$db->SQLDate('d', "occurrences.start_date")." AS day,\n"
		.$db->SQLDate('Y', "occurrences.end_date")." AS end_year,\n"
		.$db->SQLDate('m', "occurrences.end_date")." AS end_month,\n"
		.$db->SQLDate('d', "occurrences.end_date")." AS end_day,\n"
		."users.username\n"
		."FROM ".SQL_PREFIX."events AS events,\n"
		.SQL_PREFIX."users AS users,\n"
                .SQL_PREFIX."occurrences AS occurrences\n"
		."WHERE events.id = $id\n"
                ."AND events.uid = users.uid\n"
                ."AND occurrences.event_id = events.id\n"
		."AND events.calendar_id = $calendar_id\n"
                ."LIMIT 0,1";

	$result = $db->Execute($query);

	if(!$result) {
		db_error(_('Error in get_event_by_id'), $query);
	}

	if($result->FieldCount() == 0) {
		soft_error("item doesn't exist!");
	}

	return array_map('stripslashes', $result->FetchRow());
}

// parses a description and adds the appropriate mark-up
function parse_desc($text)
{

	// get out the crap, put in breaks
	$text = nl2br($text);

	//urls
	$text = preg_replace("/([[:alpha:]]+:\\/\\/[^<>\s]+[\\w\\/])/i",
			"<a href=\"$1\">$1</a>", $text);


	// emails
	$text = preg_replace("/([a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*"
			."[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z])/",
			"<a href=\"mailto:$1\">$1</a>", $text );

	return $text;
}

// returns the day of week number corresponding to 1st of $month
function day_of_first($month, $year)
{
	global $config;

	if(!$config['start_monday'])
		return date('w', mktime(0, 0, 0, $month, 1, $year));
	else
		return (date('w', mktime(0, 0, 0, $month, 1, $year)) + 6) % 7;
}

// returns the number of days in $month
function days_in_month($month, $year)
{
	return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year)
{
	return ceil((day_of_first($month, $year)
				+ days_in_month($month, $year)) / 7);
}

// creates a link with text $text and GET attributes corresponding to the rest
// of the arguments.
// returns XHTML data for the link
function create_id_link($text, $action, $id = false, $attribs = false)
{
	global $phpc_script;

	$url = "href=\"$phpc_script?action=$action";
	if($id !== false) $url .= "&amp;id=$id";
	$url .= '"';

        if($attribs !== false) {
                $as = attributes($url, $attribs);
        } else {
                $as = attributes($url);
        }
	return tag('a', $as, $text);
}

function create_date_link($text, $action, $year = false, $month = false,
                $day = false, $attribs = false, $lastaction = false)
{
        global $phpc_script;

	$url = "href=\"$phpc_script?action=$action";
	if($year !== false) $url .= "&amp;year=$year";
	if($month !== false) $url .= "&amp;month=$month";
	if($day !== false) $url .= "&amp;day=$day";
        if($lastaction !== false) $url .= "&amp;lastaction=$lastaction";
	$url .= '"';

        if($attribs !== false) {
                $as = attributes($url, $attribs);
        } else {
                $as = attributes($url);
        }
	return tag('a', $as, $text);
}

// takes a menu $html and appends an entry
function menu_item_append(&$html, $name, $action, $year = false, $month = false,
		$day = false, $lastaction = false)
{
        if(!is_object($html)) {
                soft_error('Html is not a valid Html class.');
        }
	$html->add(create_date_link($name, $action, $year, $month,
                                        $day, false, $lastaction));
        $html->add("\n");
}

// same as above, but prepends the entry
function menu_item_prepend(&$html, $name, $action, $year = false,
		$month = false, $day = false, $lastaction = false)
{
        $html->prepend("\n");
	$html->prepend(create_date_link($name, $action, $year, $month,
                                $day, false, $lastaction));
}

// creates a hidden input for a form
// returns XHTML data for the input
function create_hidden($name, $value)
{
	return tag('input', attributes("name=\"$name\"", "value=\"$value\"",
				'type="hidden"'));
}

// creates a submit button for a form
// return XHTML data for the button
function create_submit($value)
{
	return tag('input', attributes('name="submit"', "value=\"$value\"",
                                'type="submit"'));
}

// creates a text entry for a form
// returns XHTML data for the entry
function create_text($name, $value = false)
{
	$attributes = attributes("name=\"$name\"", 'type="text"');
	if($value !== false) {
		$attributes->add("value=\"$value\"");
	}
	return tag('input', $attributes);
}

// creates a password entry for a form
// returns XHTML data for the entry
function create_password($name)
{
	return tag('input', attributes("name=\"$name\"", 'type="password"'));
}

// creates a checkbox for a form
// returns XHTML data for the checkbox
function create_checkbox($name, $value = false, $checked = false)
{
	$attributes = attributes("name=\"$name\"", 'type="checkbox"');
	if($value !== false) $attributes->add("value=\"$value\"");
	if(!empty($checked)) $attributes->add('checked="checked"');
	return tag('input', $attributes);
}

function can_add_event()
{
        global $config;

        return $config['anon_permission'] || check_user();
}

// creates the navbar for the top of the calendar
// returns XHTML data for the navbar
function navbar()
{
	global $vars, $action, $config, $year, $month, $day;

	$html = tag('div', attributes('class="phpc-navbar"'));

	if(can_add_event() && $action != 'add') { 
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

	if($action != 'display' || !empty($vars['id'])) {
		menu_item_append($html, _('View date'), 'display', $year,
				$month, $day);
	}

	if(check_user()) {
		menu_item_append($html, _('Log out'), 'logout',
                                empty($vars['year']) ? false : $year,
                                empty($vars['month']) ? false : $month,
				empty($vars['day']) ? false : $day,
				$action);
	} else {
		menu_item_append($html, _('Log in'), 'login',
                                empty($vars['year']) ? false : $year,
                                empty($vars['month']) ? false : $month,
				empty($vars['day']) ? false : $day,
                                $action);
	}

	if(check_user() && $action != 'admin') {
		menu_item_append($html, _('Admin'), 'admin');
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

	return $html;
}

// creates an array from $start to $end, with an $interval
function create_sequence($start, $end, $interval = 1, $display = NULL)
{
        $arr = array();
        for ($i = $start; $i <= $end; $i += $interval){
                if($display) {
                        $arr[$i] = call_user_func($display, $i);
                } else {
                        $arr[$i] = $i;
                }
        }
        return $arr;
}

function minute_pad($minute)
{
        return sprintf('%02d', $minute);
}

function get_day_of_month_sequence($month, $year)
{
        $end = date('t', mktime(0, 0, 0, $month, 1, $year, 0));
        return create_sequence(0, $end);
}

// creates a select element for a form of pre-defined $type
// returns XHTML data for the element
function create_select($name, $type, $select, $attributes = NULL)
{
        if(!$attributes) $attributes = attributes();

        $attributes->add('size="1"', "name=\"$name\"");
	$html = tag('select', $attributes);

        foreach($type as $value => $text) {
		$option_attributes = attributes("value=\"$value\"");
		if ($select == $value) {
                        $option_attributes->add('selected="selected"');
                }
		$html->add(tag('option', $option_attributes, $text));
	}

	return $html;
}

?>
