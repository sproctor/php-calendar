<?php
/*
 * Copyright 2012 Sean Proctor
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

// make sure that we have _ defined
if(!function_exists('_')) {
	function _($str) { return $str; }
	$phpc_translate = false;
} else {
	$phpc_translate = true;
}

require_once("$phpc_includes_path/html.php");
require_once("$phpc_includes_path/util.php");

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

function login_user($username, $password)
{
        global $phpcdb;

	// Regenerate the session in case our non-logged in version was
	//   snooped
	// TODO: Verify that this is needed, and make sure it's called in setup
	// 	 so it doesn't create issues for embedded users
	// session_regenerate_id();

	$user = $phpcdb->get_user_by_name($username);
	if(!$user || $user->password != md5($password))
		return false;

	phpc_do_login($user);

	return true;
}

function phpc_do_login($user, $series_token = false) {
        global $phpcdb, $phpc_uid;

	$phpc_uid = $user->uid;
	$login_token = phpc_get_token();
	$_SESSION["phpc_uid"] = $phpc_uid;
	$_SESSION['phpc_login'] = $login_token;

	if(!$series_token) {
		$series_token = phpc_get_token();
		$phpcdb->add_login_token($phpc_uid, $series_token,
				$login_token);
	} else {
		$phpcdb->update_login_token($phpc_uid, $series_token,
				$login_token);
	}

	// TODO: Add a remember me checkbox to the login form, and have the
	//	cookies expire at the end of the session if it's not checked

	// expire credentials in 30 days.
	$expiration_time = time() + 30 * 24 * 60 * 60;
	setcookie("phpc_uid", $phpc_uid, $expiration_time);
	setcookie("phpc_login", $login_token, $expiration_time);
	setcookie("phpc_login_series", $series_token, $expiration_time);
	if(!empty($user->admin))
		$_SESSION["phpc_admin"] = true;

	return true;
}

// returns tag data for the links at the bottom of the calendar
function link_bar()
{
	global $phpc_url, $phpc_tz, $phpc_lang;

	$links = tag('p', "[" . _('Language') . ": $phpc_lang]" .
			" [" . _('Timezone') . ": $phpc_tz]");

	if(defined('PHPC_DEBUG')) {
		$links->add(array(' [', tag('a',
						attributes('href="http://validator.w3.org/check?url='
							. rawurlencode($phpc_url)
							. '"'),
						'Validate HTML'),
					'] [',
					tag('a', attributes('href="http://jigsaw.w3.org/css-validator/check/referer"'),
						'Validate CSS'),
					']'));
	}

	return tag('div', attributes('class="phpc-footer phpc-bar"'), $links);
}

function get_languages() {
	global $phpc_locale_path;

	static $langs = NULL;

	if(!empty($langs))
		return $langs;

	// create links for each existing language translation
	$handle = opendir($phpc_locale_path);

	if(!$handle)
		soft_error("Error reading locale directory.");

	$langs = array('en');
	while(($filename = readdir($handle)) !== false) {
		$pathname = "$phpc_locale_path/$filename";
		if(strncmp($filename, ".", 1) == 0 || !is_dir($pathname))
			continue;
		if(file_exists("$pathname/LC_MESSAGES/messages.mo"))
			$langs[] = $filename;
	}

	closedir($handle);

	return $langs;
}

function day_of_week_start()
{
	global $phpc_cal;

	return $phpc_cal->get_config('week_start');
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
	$timestamp = mktime(0, 0, 0, $month, $day, $year);

	// week_start = 1 uses ISO 8601 and contains the Jan 4th,
	//   Most other places the first week contains Jan 1st
	//   There are a few outliers that start weeks on Monday and use
	//   Jan 1st for the first week. We'll ignore them for now.
	if(day_of_week_start() == 1) {
		$year_contains = 4;
		// if the week is in December and contains Jan 4th, it's a week
		// from next year
		if($month == 12 && $day - 24 >= $year_contains) {
			$year++;
			$month = 1;
			$day -= 31;
		}
	} else {
		$year_contains = 1;
	}
	
	// $day is the first day of the week relative to the current month,
	// so it can be negative. If it's in the previous year, we want to use
	// that negative value, unless the week is also in the previous year,
	// then we want to switch to using that year.
	if($day < 1 && $month == 1 && $day > $year_contains - 7) {
		$day_of_year = $day - 1;
	} else {
		$day_of_year = date('z', $timestamp);
		$year = date('Y', $timestamp);
	}

	/* Days in the week before Jan 1. */
	$days_before_year = day_of_week(1, $year_contains, $year);

	// Days left in the week
	$days_left = 8 - day_of_week_ts($timestamp) - $year_contains;

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

/*S*/
function create_plain_link($text, $action, $year = false,
		$month = false, $day = false, $attribs = false)
{
	global $phpc_script, $vars;
	$args = array();
	if($year !== false) $args["year"] = $year;
	if($month !== false) $args["month"] = $month;
	if($day !== false) $args["day"] = $day;

	$url ="".$phpc_script."?";
	if(isset($vars["phpcid"]))
		$url .= "phpcid=" . htmlentities($vars["phpcid"]) . "&amp;";
	$url .= "action=" . htmlentities($action);
	
	if (!empty($args)) {
		foreach ($args as $key => $value) {
			if(empty($value))
				continue;
			if (is_array($value)) {
				foreach ($value as $v) {
					$url .= "&amp;"
						. htmlentities("{$key}[]=$v");
				}
			} else
				$url .= "&amp;" . htmlentities("$key=$value");
		}
	}
	$url .= '';
	return $url;
}

function create_action_link($text, $action, $args = false, $attribs = false)
{
	global $phpc_script, $vars;

	$url = "href=\"$phpc_script?";
	if(isset($vars["phpcid"]))
		$url .= "phpcid=" . htmlentities($vars["phpcid"]) . "&amp;";
	$url .= "action=" . htmlentities($action);

	if (!empty($args)) {
		foreach ($args as $key => $value) {
			if(empty($value))
				continue;
			if (is_array($value)) {
				foreach ($value as $v) {
					$url .= "&amp;"
						. htmlentities("{$key}[]=$v");
				}
			} else
				$url .= "&amp;" . htmlentities("$key=$value");
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
	$name=str_replace(' ','&nbsp;',$name); /*not breaking space on menus*/

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
	$name=str_replace(' ','&nbsp;',$name);

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
	global $vars, $action, $year, $month, $day, $phpc_cal;

	$html = tag('div', attributes('class="phpc-navbar phpc-bar"'));

	$args = array('year' => $year, 'month' => $month, 'day' => $day);

	if($phpc_cal->can_write() && $action != 'add') { 
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

	if($action != 'settings')
		menu_item_append($html, _('Settings'), 'settings');

	if(is_user()) {
		menu_item_append($html, _('Log out'), 'logout',
				array('lasturl' =>
					htmlspecialchars(urlencode($_SERVER['QUERY_STRING']))));
	} else {
		menu_item_append($html, _('Log in'), 'login',
				array('lasturl' =>
					htmlspecialchars(urlencode($_SERVER['QUERY_STRING']))));
	}

	if($phpc_cal->can_admin() && $action != 'cadmin') {
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

function get_config_options()
{
	static $options = NULL;

	if($options === NULL) {
		$options = init_config_options();
	}
	return $options;
}

function init_config_options() {
	$languages = array("" => _("Default"));
	foreach(get_languages() as $language) {
		$languages[$language] = $language;
	}
	// name, text, type, value(s)
	return array( 
			array('week_start', _('Week Start'), PHPC_DROPDOWN,
				array(
					0 => _('Sunday'),
					1 => _('Monday'),
					6 => _('Saturday')
				     )),
			array('hours_24', _('24 Hour Time'), PHPC_CHECK),
			array('calendar_title', _('Calendar Title'), PHPC_TEXT),
			array('subject_max', _('Maximum Subject Length'), PHPC_TEXT),
			array('events_max', _('Events Display Daily Maximum'), PHPC_TEXT),
			array('anon_permission', _('Public Permissions'), PHPC_DROPDOWN,
				array(
					_('Cannot read nor write events'),
					_('Can read but not write events'),
					_('Can create but not modify events'),
					_('Can create and modify events')
				     )
			     ),
			array('timezone', _('Default Timezone'), PHPC_MULTI_DROPDOWN, get_timezone_list()),
			array('language', _('Default Language'), PHPC_DROPDOWN, $languages),
			array('date_format', _('Date Format'), PHPC_DROPDOWN, get_date_format_list()),
			);
}

function get_timezone_list() {
	$timezones = array();
	$timezones[_("Default")] = "";
	foreach(timezone_identifiers_list() as $timezone) {
		$sp = explode("/", $timezone, 2);
		$continent = $sp[0];
		if(empty($sp[1])) {
			$timezones[$continent] = $timezone;
		} else {
			$area = $sp[1];
			if(empty($timezones[$continent]))
				$timezones[$continent] = array();
			$timezones[$continent][$timezone] = $area;
		}
	}
	return $timezones;
}

function get_date_format_list()
{
	return array(	_("Month Day Year"),
			_("Year Month Day"),
			_("Day Month Year"));
}

function get_calendar_list() {
	global $phpc_script, $phpcdb;

	$calendar_list = tag('div', attributes('class="phpc-navbar phpc-callist"'));

	$count = 0;
	foreach($phpcdb->get_calendars() as $calendar) {
		if(!$calendar->can_read())
			continue;

		$title = $calendar->get_title();
		$cid = $calendar->get_cid();
		$count++;

		$attrs = attributes("href=\"$phpc_script?phpcid=$cid\"");
		$calendar_list->add(tag('a', $attrs, $title));
	}

	if($count <= 1)
		return '';

	return $calendar_list;
}

function display_phpc() {
	global $phpc_messages, $phpc_redirect;

	$navbar = false;

	try {
		$content = do_action();
		$navbar = navbar();

		if(sizeof($phpc_messages) > 0) {
			$messages = tag('div', attrs('class="phpc-message"'));
			foreach($phpc_messages as $message) {
				$messages->add($message);
			}
			// If we're redirecting, the messages might not get
			//   seen, so don't clear them
			if(empty($phpc_redirect))
				$_SESSION['messages'] = NULL;
		} else {
			$messages = '';
		}

		return tag('', $messages, get_calendar_list(), $navbar,
				$content, link_bar());
	} catch(PermissionException $e) {
		$results = tag('');
		// TODO: make navbar show if there is an error in do_action()
		if($navbar !== false)
			$results->add($navbar);
		$results->add(tag('div', _('You do not have permission to do that: ')
					. $e->getMessage()));
		return $results;
	} catch(Exception $e) {
		$results = tag('');
		if($navbar !== false)
			$results->add($navbar);
		$results->add(tag('div', attrs('class="phpc-main"'),
					tag('h2', _('Error')),
					tag('p', $e->getMessage()),
					tag('h3', _('Backtrace')),
					tag('pre', htmlentities($e->getTraceAsString()))));
		return $results;
	}

}

function do_action()
{
	global $action, $phpc_includes_path, $vars;

	if(!preg_match('/^\w+$/', $action))
		soft_error(_('Invalid action'));

	require_once("$phpc_includes_path/$action.php");

	eval("\$action_output = $action();");

	return $action_output;
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

function verify_token() {
	if(!is_user())
		return true;

	if(empty($_SESSION["phpc_login"]) || empty($_COOKIE["phpc_login"])
			|| $_COOKIE["phpc_login"] != $_SESSION["phpc_login"])
		soft_error(_("Secret token mismatch. Possible request forgery attempt."));
}

function get_header_tags($path)
{
	global $phpc_protocol;

	if(defined('PHPC_DEBUG'))
		$jq_min = '';
	else
		$jq_min = '.min';
		
		$theme='smoothness';
		$jquery_version="1.9.1";
		$jqueryui_version="1.10.2";

	return array(
			tag('link', attrs('rel="stylesheet"', 'type="text/css"',
					"href=\"$path/phpc.css\"")),
			tag('link', attrs('rel="stylesheet"', 'type="text/css"',
					"href=\"$phpc_protocol://ajax.googleapis.com/ajax/libs/jqueryui/$jqueryui_version/themes/$theme/jquery-ui$jq_min.css\"")),
			tag('link', attrs('rel="stylesheet"', 'type="text/css"',
					"href=\"$path/jquery-ui-timepicker.css\"")),
			tag("script", attrs('type="text/javascript"',
					"src=\"$phpc_protocol://ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery$jq_min.js\""), ''),
			tag("script", attrs('type="text/javascript"',
					"src=\"$phpc_protocol://ajax.googleapis.com/ajax/libs/jqueryui/$jqueryui_version/jquery-ui$jq_min.js\""), ''),
			tag('script', attrs('type="text/javascript"',
					"src=\"$path/phpc.js\""), ''),
			tag("script", attrs('type="text/javascript"',
					"src=\"$path/jquery.ui.timepicker.js\""), ''),
			tag("script", attributes('type="text/javascript"',
					"src=\"$path/jquery.hoverIntent.minified.js\""), ''),
			tag("script", attrs('type="text/javascript"',
					"src=\"$path/tableUI.js\""), ''),
			tag('link', attrs('rel="stylesheet"', 'type="text/css"',
					"href=\"$path/tableUI.css\"")),
		  );
}

function embed_header($path)
{
	echo tag('', get_header_tags())->toString();
}

function create_config_input($element, $default = false)
{
	$name = $element[0];
	$type = $element[2];

	switch($type) {
		case PHPC_CHECK:
			$input = create_checkbox($name, '1', $default);
			break;
		case PHPC_TEXT:
			$input = create_text($name, $default);
			break;
		case PHPC_DROPDOWN:
			$choices = $element[3];
			$input = create_select($name, $choices, $default);
			break;
		case PHPC_MULTI_DROPDOWN:
			$choices = $element[3];
			$input = create_multi_select($name, $choices, $default);
			break;
		default:
			soft_error(_('Unsupported config type') . ": $type");
	}
	return $input;
}
?>
