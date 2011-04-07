<?php
/*
 * Copyright 2011 Sean Proctor
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
require_once("$phpc_includes_path/globals.php");

$phpc_valid_actions = array('event_form', 'event_delete', 'display_month',
		'display_day', 'display_event', 'display_event_json',
		'event_submit', 'search', 'login', 'logout', 'admin',
		'cadmin_submit', 'user_create', 'cadmin',
		'create_calendar', 'calendar_delete',
		'user_delete', 'user_permissions_submit',
		'category_form', 'category_submit', 'category_delete',
		'settings', 'password_submit', 'settings_submit',
		'occurrence_delete');

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
        global $phpcdb, $phpc_token;

	// Regenerate the session in case our non-logged in version was
	//   snooped
	// TODO: Verify that this is needed, and make sure it's called in setup
	// 	 so it doesn't create issues for embedded users
	// session_regenerate_id();

	$user = $phpcdb->get_user_by_name($username);
	if(!$user || $user->password != md5($password))
		return false;

	$_SESSION["phpc_uid"] = $user->uid;
	$phpc_token = generate_token();
	$_SESSION['phpc_token'] = $phpc_token;
	if(!empty($user->admin))
		$_SESSION["phpc_admin"] = true;

	setcookie("phpc_user", "1");

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
	global $translate, $phpc_url, $phpc_locale_path, $phpc_tz, $phpc_lang;

	$html = tag('div', attributes('class="phpc-footer"'));

	if($translate) {
		$langs = get_languages();
		if(sizeof($langs) > 1) {
			$lang_links = tag('p');

			foreach($langs as $lang) {
				if($phpc_lang == $lang)
					$lang_link = $lang;
				else
					$lang_link = lang_link($lang);
				$lang_links->add('[', $lang_link, '] ');
			}
			$html->add($lang_links);
		}
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
			']',
			" [Timezone: $phpc_tz]"));
	return $html;
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
	global $phpcid;

	return get_config($phpcid, 'week_start');
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

	// week_start = 1 uses ISO 8601 and contains the Jan 4th,
	//   Most other places the first week contains Jan 1st
	//   There are a few outliers that start weeks on Monday and use
	//   Jan 1st for the first week. We'll ignore them for now.
	if(get_config($phpcid, 'week_start') == 1) {
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

	$args = array('year' => $year, 'month' => $month, 'day' => $day);

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
				array('lasturl' =>
					htmlspecialchars(urlencode($_SERVER['QUERY_STRING']))));
		if($action != 'settings')
			menu_item_append($html, _('Settings'), 'settings');
	} else {
		menu_item_append($html, _('Log in'), 'login',
				array('lasturl' =>
					htmlspecialchars(urlencode($_SERVER['QUERY_STRING']))));
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

function get_config_options()
{
	static $options = NULL;

	if($options == NULL) {
		$timezones = array("NULL" => _("System"));
		foreach(timezone_identifiers_list() as $timezone) {
			$timezones[$timezone] = $timezone;
		}
		$languages = array("NULL" => _("Default"));
		foreach(get_languages() as $language) {
			$languages[$language] = $language;
		}
		// name, text, type, value(s)
		$options = array( 
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
				array('timezone', _('Default Timezone'), PHPC_DROPDOWN, $timezones),
				array('language', _('Default Language'), PHPC_DROPDOWN, $languages),
				);
	}
	return $options;
}

function get_config($cid, $option, $default = '') {
	global $phpcdb;

	$config = $phpcdb->get_calendar_config($cid);
	if(!isset($config[$option])) {
		if(defined('PHPC_DEBUG'))
			soft_error("Undefined config option \"$option\".");
		return $default;
	}
	return $config[$option];
}

function display_phpc() {

	$navbar = false;

	try {
		$content = do_action();
		$navbar = navbar();
		return tag('', $navbar, $content, link_bar());
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
	global $action, $phpcid, $phpc_includes_path, $phpc_valid_actions,
	       $vars;

	// TODO: use the messaging system to display this
	if(!in_array($action, $phpc_valid_actions, true)) {
		soft_error(_('Invalid action'));
	}

	if(!empty($vars['clearmsg']))
		$_SESSION['messages'] = NULL;

	$have_message = false;
	if(!empty($_SESSION['messages'])) {
		$messages = tag('div', attrs('class="phpc-message"'));
		foreach($_SESSION['messages'] as $message) {
			$messages->add($message);
			$have_message = true;
		}
		$_SESSION['messages'] = NULL;
	}

	require_once("$phpc_includes_path/$action.php");

	eval("\$action_output = $action();");

	if($have_message)
		return tag('', $messages, $action_output);
	else
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
	global $vars;

	if(!empty($_SESSION["phpc_token"]) && (empty($vars["phpc_token"]) ||
				$vars["phpc_token"] != $_SESSION["phpc_token"]))
		soft_error(_("Secret token mismatch. Possible request forgery attempt."));
}

function generate_token() {
	return md5(uniqid(rand(), TRUE));
}

?>
