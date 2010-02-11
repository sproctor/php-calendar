<?php
/*
 * Copyright 2009 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
   this file contains all the re-usable functions for the calendar
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/html.php");
require_once("$phpc_includes_path/lib_autolink.php");

// called when some error happens
function soft_error($str)
{
	echo '<html><head><title>', _('Error'), "</title></head>\n",
	     '<body><h1>', _('Software Error'), "</h1>\n",
	     "<h2>", _('Message:'), "</h2>\n",
	     "<pre>$str</pre>\n",
	     "<h2>", _('Backtrace'), "</h2>\n",
	     "<ol>\n";
	foreach(debug_backtrace() as $bt) {
		echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
	}
	echo "</ol>\n",
	     "</body></html>\n";
	exit;
}

// takes a number of the month, returns the name
function month_name($month)
{
        global $month_names;

	$month = ($month - 1) % 12 + 1;
        return $month_names[$month];
}

//takes a day number of the week, returns a name (0 for the beginning)
function day_name($day)
{
	global $day_names;

	$day = $day % 7;

        return $day_names[$day];
}

function short_month_name($month)
{
        global $short_month_names;

	$month = ($month - 1) % 12 + 1;
        return $short_month_names[$month];
}

// checks global variables to see if the user is logged in.
function is_user()
{
	return isset($_SESSION["phpc_uid"]);
}

function get_uid()
{
	return isset($_SESSION["phpc_uid"]) ? $_SESSION["phpc_uid"] : 0;
}

function is_admin()
{
	return !empty($_SESSION["phpc_admin"]);
}

function is_owner($event)
{
	if (empty($_SESSION["phpc_uid"]))
		return false;
	
	return $_SESSION["phpc_uid"] == $event->get_uid();
}

function can_admin_calendar($cid)
{
	global $phpcdb;

	if (!is_user())
		return false;

	$perms = $phpcdb->get_permissions($cid, $_SESSION["phpc_uid"]);

	return is_admin() || !empty($perms["admin"]);
}

function can_write($cid)
{
	global $phpcdb;

	if (get_config($cid, 'anon_permission') >= 2)
		return true;

	if (!is_user())
		return false;

	$perms = $phpcdb->get_permissions($cid, $_SESSION["phpc_uid"]);

	return can_admin_calendar($cid) || !empty($perms["write"]);
}

function can_modify($cid)
{
	global $phpcdb;

	if (get_config($cid, 'anon_permission') >= 3)
		return true;
	
	if (!is_user())
		return false;

	$perms = $phpcdb->get_permissions($cid, $_SESSION["phpc_uid"]);

	return can_admin_calendar($cid) || !empty($perms["modify"]);
}

function can_read($cid)
{
	global $phpcdb;

	if (get_config($cid, 'anon_permission') >= 1)
		return true;
	
	if (!is_user())
		return false;

	$perms = $phpcdb->get_permissions($cid, $_SESSION["phpc_uid"]);

	return can_admin_calendar($cid) || !empty($perms["read"]);
}

function can_create_readonly($cid)
{
	global $phpcdb;

	if (!is_user())
		return false;

	$perms = $phpcdb->get_permissions($cid, $_SESSION["phpc_uid"]);

	return can_admin_calendar($cid) || !empty($perms["readonly"]);
}

// returns whether or not the current user can modify $event
function can_modify_event($event)
{
	$cid = $event->get_cid();

	return can_admin_calendar($cid) || is_owner($event)
		|| (can_modify($cid) && !$event->is_readonly());
}

// returns whether or not the current user can read $event
function can_read_event($event)
{
	return can_read($event->get_cid());
}

function login_user($username, $password)
{
        global $phpcdb;

	// Regenerate the session in case our non-logged in version was
	//   snooped
	session_regenerate_id();

	$user = $phpcdb->get_user_by_name($username);
	if(!$user)
		return false;

	if($user->password != md5($password))
		return false;

	$_SESSION["phpc_uid"] = $user->uid;
	if(!empty($user->admin))
		$_SESSION["phpc_admin"] = true;

	session_write_close();

	return true;
}

// returns tag data for a link for $lang
function lang_link($lang)
{
	global $phpc_script;

        $str = $_SERVER['QUERY_STRING'];
        $str = preg_replace("/&lang=\\w*/", '', $str);
        $str = preg_replace("/lang=\\w*&/", '', $str);
        $str = preg_replace("/lang=\\w*/", '', $str);
	if(!empty($str)) {
		$str = htmlentities($str) . '&amp;';
	}
	$str = "{$phpc_script}?{$str}lang=$lang";

	return tag('a', attributes("href=\"$str\""), $lang);
}

// returns tag data for the links at the bottom of the calendar
function link_bar()
{
	global $translate, $phpc_url, $phpc_root_path, $languages;

	$html = tag('div', attributes('class="phpc-footer"'));

	if($translate) {
		$lang_links = tag('p', '[', lang_link('en'), '] ');
                foreach($languages as $lang) {
                        if(file_exists("$phpc_root_path/locale/$lang/LC_MESSAGES/messages.mo")) {
                                $lang_links->add('[', lang_link($lang), '] ');
                        }
                }
                $html->add($lang_links);
	}

	$html->add(tag('p', '[',
			tag('a',
				attributes('href="http://validator.w3.org/'
					.'check?url='
					.rawurlencode($phpc_url)
					.'"'), 'Valid HTML 4.01 Strict'),
			'] [',
			tag('a', attributes('href="http://jigsaw.w3.org/'
					.'css-validator/check/referer"'),
					'Valid CSS2'),
			']'));
	return $html;
}

// parses a description and adds the appropriate mark-up
function parse_desc($text)
{
	global $urlmatch;

	// get out the crap, put in breaks
        $text = strip_tags($text);
	// if you want to allow some tags, change the previous line to:
	// $text = strip_tags($text, "a"); // change "a" to the list of tags
        $text = htmlspecialchars($text, ENT_NOQUOTES);
	// then uncomment the following line
	// $text = preg_replace("/&lt;(.+?)&gt;/", "<$1>", $text);
        $text = nl2br($text);

	// linkify urls
	$text = autolink($text, 0);

	// linkify emails
	$text = autolink_email($text);

	return $text;
}

function day_of_week_start()
{
	global $phpcid;

	switch(get_config($phpcid, 'week_start')) {
		// start Monday
		case 0:
		case 1:
			return 1;

			// Start Sunday
		case 2:
			return 0;

			// Start Saturday
		case 3:
			return 6;

		default:
			soft_error("Unsupported start day.");
	}
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week($month, $day, $year)
{
	return day_of_week_ts(mktime(0, 0, 0, $month, $day, $year));
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week_ts($timestamp)
{
	$days = date('w', $timestamp);

	return ($days + 7 - day_of_week_start()) % 7;
}

// returns the number of days in $month
function days_in_month($month, $year)
{
	return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year)
{
	$days = days_in_month($month, $year);

	// days not in this month in the partial weeks
	$days_before_month = day_of_week($month, 1, $year);
	$days_after_month = 6 - day_of_week($month, $days, $year);

	// add up the days in the month and the outliers in the partial weeks
	// divide by 7 for the weeks in the month
	return ($days_before_month + $days + $days_after_month) / 7;
}

// return the week number corresponding to the $day.
function week_of_year($month, $day, $year)
{
	global $phpcid;

	$timestamp = mktime(0, 0, 0, $month, $day, $year);

	// week_start = 0 uses ISO 8601
	if(get_config($phpcid, 'week_start') == 0)
		return date('W', $timestamp);
	
	// For calendars where the first partial week is always the first week

	$day_of_year = date('z', $timestamp);

	/* Days in the week before Jan 1. If you want weeks to start on Monday
	 * make this (x + 6) % 7 */
	$days_before_year = day_of_week(1, 1, $year);

	// Days left in the week
	$days_left = 7 - day_of_week_ts($timestamp);

	/* find the number of weeks by adding the days in the week before
	 * the start of the year, days up to $day, and the days left in
	 * this week, then divide by 7 */
	return ($days_before_year + $day_of_year + $days_left) / 7;
}

function create_event_link($text, $action, $eid, $attribs = false)
{
	return create_action_link($text, $action, array('eid' => $eid),
			$attribs);
}

function create_occurrence_link($text, $action, $oid, $attribs = false)
{
	return create_action_link($text, $action, array('oid' => $oid),
			$attribs);
}

function create_action_link_with_date($text, $action, $year = false,
		$month = false, $day = false, $attribs = false)
{
	$args = array();
	if($year !== false) $args["year"] = $year;
	if($month !== false) $args["month"] = $month;
	if($day !== false) $args["day"] = $day;

	return create_action_link($text, $action, $args, $attribs);
}

function create_action_link($text, $action, $args = false, $attribs = false)
{
	global $phpc_script, $vars;

	$url = "href=\"$phpc_script?";
	if(isset($vars["phpcid"]))
		$url .= "phpcid={$vars["phpcid"]}&amp;";
	$url .= "action=$action";

	if (!empty($args)) {
		foreach ($args as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$url .= "&amp;{$key}[]=$v";
				}
			} else
				$url .= "&amp;$key=$value";
		}
	}
	$url .= '"';

	if($attribs !== false) {
		$as = attributes($url, $attribs);
	} else {
		$as = attributes($url);
	}
	return tag('a', $as, $text);
}

// takes a menu $html and appends an entry
function menu_item_append(&$html, $name, $action, $args = false,
		$attribs = false)
{
	if(!is_object($html)) {
		soft_error('Html is not a valid Html class.');
	}
	$html->add(create_action_link($name, $action, $args, $attribs));
	$html->add("\n");
}

// takes a menu $html and appends an entry with the date
function menu_item_append_with_date(&$html, $name, $action, $year = false,
		$month = false, $day = false, $attribs = false)
{
	if(!is_object($html)) {
		soft_error('Html is not a valid Html class.');
	}
	$html->add(create_action_link_with_date($name, $action, $year, $month,
			$day, $attribs));
	$html->add("\n");
}

// same as above, but prepends the entry
function menu_item_prepend(&$html, $name, $action, $args = false,
		$attribs = false)
{
	if(!is_object($html)) {
		soft_error('Html is not a valid Html class.');
	}
	$html->prepend("\n");
	$html->prepend(create_action_link($name, $action, $args, $attribs));
}

// creates a hidden input for a form
// returns tag data for the input
function create_hidden($name, $value)
{
	return tag('input', attributes("name=\"$name\"", "value=\"$value\"",
				'type="hidden"'));
}

// creates a submit button for a form
// return tag data for the button
function create_submit($value)
{
	return tag('input', attributes('name="submit"', "value=\"$value\"",
				'type="submit"'));
}

// creates a text entry for a form
// returns tag data for the entry
function create_text($name, $value = false)
{
	$attributes = attributes("name=\"$name\"", 'type="text"');
	if($value !== false) {
		$attributes->add("value=\"$value\"");
	}
	return tag('input', $attributes);
}

// creates a password entry for a form
// returns tag data for the entry
function create_password($name)
{
	return tag('input', attributes("name=\"$name\"", 'type="password"'));
}

// creates a checkbox for a form
// returns tag data for the checkbox
function create_checkbox($name, $value, $checked = false)
{
	$attributes = attributes("name=\"$name\"", 'type="checkbox"',
			"value=\"$value\"");
	if(!empty($checked)) $attributes->add('checked="checked"');
	return tag('input', $attributes);
}

// creates the navbar for the top of the calendar
// returns tag data for the navbar
function navbar()
{
	global $vars, $action, $year, $month, $day, $phpcid;

	$html = tag('div', attributes('class="phpc-navbar"'));

	$args = array();
	if(!empty($vars['year']))
		$args['year'] = $year;
	
	if(!empty($vars['month']))
		$args['month'] = $month;

	if(!empty($vars['day']))
		$args['day'] = $day;

	if(can_write($phpcid) && $action != 'add') { 
		menu_item_append($html, _('Add Event'), 'event_form', $args);
	}

	if($action != 'search') {
		menu_item_append($html, _('Search'), 'search', $args);
	}

	if($action != 'display_month') {
		menu_item_append($html, _('View Month'), 'display_month',
			$args);
	}

	if($action != 'display_day' && !empty($vars['day'])) {
		menu_item_append($html, _('View date'), 'display_day', $args);
	}

	if(is_user()) {
		menu_item_append($html, _('Log out'), 'logout',
				array_merge($args,
					array('lastaction' => $action)));
	} else {
		menu_item_append($html, _('Log in'), 'login',
				array_merge($args,
					array('lastaction' => $action)));
	}

	if(can_admin_calendar($phpcid) && $action != 'cadmin') {
		menu_item_append($html, _('Calendar Admin'), 'cadmin');
	}

	if(is_admin() && $action != 'admin') {
		menu_item_append($html, _('Admin'), 'admin');
	}

	if($action == 'display_day') {
		$monthname = month_name($month);

		$lasttime = mktime(0, 0, 0, $month, $day - 1, $year);
		$lastday = date('j', $lasttime);
		$lastmonth = date('n', $lasttime);
		$lastyear = date('Y', $lasttime);
		$lastmonthname = month_name($lastmonth);

		$last_args = array('year' => $lastyear, 'month' => $lastmonth,
				'day' => $lastday);

		menu_item_prepend($html, "$lastmonthname $lastday",
				'display_day', $last_args);

		$nexttime = mktime(0, 0, 0, $month, $day + 1, $year);
		$nextday = date('j', $nexttime);
		$nextmonth = date('n', $nexttime);
		$nextyear = date('Y', $nexttime);
		$nextmonthname = month_name($nextmonth);

		$next_args = array('year' => $nextyear, 'month' => $nextmonth,
				'day' => $nextday);

		menu_item_append($html, "$nextmonthname $nextday",
				'display_day', $next_args);
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

function redirect($page) {
	global $phpc_script, $phpc_server, $phpc_protocol;

	if($page{0} == "/") {
		$dir = '';
	} else {
		$dir = dirname($phpc_script) . "/";
	}

	header("Location: $phpc_protocol://$phpc_server$dir$page");
}

function get_config_options()
{
	// name, text, type, value(s)
	return array( 
			array('week_start', _('Week Start'), PHPC_DROPDOWN,
				array(
					_('Monday (non-UK)'),
					_('Monday (UK)'),
					_('Sunday (USA)'),
					_('Saturday')
				     )),
			array('hours_24', _('24 Hour Time'), PHPC_CHECK),
			array('calendar_title', _('Calendar Title'), PHPC_TEXT),
			array('subject_max', _('Maximum Subject Length'), PHPC_TEXT),
			array('anon_permission', _('Public Permissions'), PHPC_DROPDOWN,
				array(
					_('Cannot read nor write events'),
					_('Can read but not write events'),
					_('Can create but not modify events'),
					_('Can create and modify events')
				     )
			     ),
		    );
}

function get_config($cid, $option)
{
	global $phpcdb;

	$config = $phpcdb->get_calendar_config($cid);
	return $config[$option];
}

function do_action()
{
	global $action, $phpcid, $phpc_includes_path;

	$legal_actions = array('event_form', 'event_delete', 'display_month',
			'display_day', 'display_event', 'display_event_json',
			'event_submit', 'search', 'login', 'logout', 'admin',
			'options_submit', 'user_create', 'cadmin',
			'create_calendar', 'calendar_delete',
			'user_delete', 'user_permissions_submit',
			'category_form', 'category_submit', 'category_delete');

	if(!in_array($action, $legal_actions, true)) {
		soft_error(_('Invalid action'));
	}

	require_once("$phpc_includes_path/$action.php");

	eval("\$action_output = $action();");

	return $action_output;
}

function addslashes_r($var) {
	if (is_array($var)) {
		foreach ($var as $key => $val) {
			$var[$key] = addslashes_r($val);
		}
		return $var;
	} else
		return addslashes($var);
}

function asbool($val)
{
	if ($val) return "1";
	return "0";
}

function format_time_string($hour, $minute, $hour24)
{
	if($hour24) {
		if($hour >= 12) {
			$hour -= 12;
			$pm = ' PM';
		} else {
			$pm = ' AM';
		}
		if($hour == 0) {
			$hour = 12;
		}
	} else {
		$pm = '';
	}

	return sprintf('%d:%02d%s', $hour, $minute, $pm);
}
?>
