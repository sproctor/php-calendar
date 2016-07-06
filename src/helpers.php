<?php
/*
 * Copyright 2016 Sean Proctor
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

namespace PhpCalendar;

define('PHPC_CHECK', 1);
define('PHPC_TEXT', 2);
define('PHPC_DROPDOWN', 3);
define('PHPC_MULTI_DROPDOWN', 4);

function __($msg) {
	global $translator;

	if (empty($translator))
		return $msg;

	return $translator->trans($msg);
}

function __p($context, $msg) {
	global $translator;

	if (empty($translator))
		return $msg;

	$id = $context . "\04" . $msg;
	$result = $translator->trans($context . "\04" . $msg);
	if ($result == $id)
		return $msg;
	else
		return $result;
}

/**
 * @param string $filename
 * @return string[]
 */
function load_config(Context $context, $filename) {
	// Run the installer if we have no config file
	// This doesn't work when embedded from outside
	if(!file_exists($filename)) {
		redirect($context, 'install.php');
		exit;
	}
	$config = read_config($filename);

	if(!isset($config["sql_host"])) {
		redirect($context, 'install.php');
		exit;
	}

	return $config;
}

/**
 * @param string $filename
 * @return string[]
 */
function read_config($filename) {
	return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($filename));
}

/**
 * called when some error happens
 * @param string $message
 * @throws \Exception
 */
function soft_error($message)
{
	throw new \Exception(escape_entities($message));
}

class PermissionException extends \Exception {
}

/**
 * @param string $message
 * @throws PermissionException
 */
function permission_error($message)
{
	throw new PermissionException(htmlspecialchars($message, ENT_COMPAT, "UTF-8"));
}

class InvalidInputException extends \Exception {
	var $target;

	/**
	 * InvalidInputException constructor.
	 * @param string $msg
	 * @param int $target
     */
	function __construct($msg, $target) {
		parent::__construct($msg);
		$this->target = $target;
	}
}

/**
 * @param $message
 * @param $target
 * @throws InvalidInputException
 */
function input_error($message, $target) {
	throw new InvalidInputException(htmlspecialchars($message, ENT_COMPAT, "UTF-8"), $target);
}

function check_input($arg, $target) {
	if(!isset($_REQUEST[$arg]))
		throw new InvalidInputException(sprintf(__('Required field "%s" is not set.'), $arg), $target);
}

function minute_pad($minute)
{
	return sprintf('%02d', $minute);
}

/**
 * @param string $page
 */
function redirect($context, $page) {
	session_write_close();

	$dir = $page{0} == '/' ?  '' : dirname($context->script) . '/';
	$url = $context->proto . '://'. $context->server . $dir . $page;

	header("Location: $url", true, 303);
	exit;
}

/**
 * @param string $message
 * @param string $page
 * @param string $css_classes
 */
function message_redirect(Context $context, $message, $page, $css_classes) {
	if(empty($_SESSION[PHPC_PREFIX . "messages"]))
		$_SESSION[PHPC_PREFIX . "messages"] = array();

	$_SESSION[PHPC_PREFIX . "messages"][] = tag('div', new AttributeList("class=\"phpc-message $css_classes\""),
		$message);
	redirect($context, $page);
}

/**
 * @param string $message
 * @param string $page
 */
function error_message_redirect(Context $context, $message, $page) {
	message_redirect($context, $message, $page, 'ui-state-error');
}

function escape_entities($string) {
	return htmlspecialchars($string, ENT_NOQUOTES, "UTF-8");
}

function asbool($val)
{
	return $val ? "1" : "0";
}

function format_timestamp_string($timestamp, $date_format, $hours24) {
	$year = date('Y', $timestamp);
	$month = date('n', $timestamp);
	$day = date('j', $timestamp);
	$hour = date('H', $timestamp);
	$minute = date('i', $timestamp);

	return format_date_string($year, $month, $day, $date_format) . ' '
	. __('at') . ' ' . format_time_string($hour, $minute, $hours24);
}

function format_date_string($year, $month, $day, $date_format)
{
	$month_name = short_month_name($month);
	switch($date_format) {
		case 0: // Month Day Year
			return "$month_name $day, $year";
		case 1: // Year Month Day
			return "$year $month_name $day";
		case 2: // Day Month Year
			return "$day $month_name $year";
		default:
			soft_error("Invalid date_format");
			return "";
	}
}

function format_short_date_string($year, $month, $day, $date_format)
{
	switch($date_format) {
		case 0: // Month Day Year
			return "$month/$day/$year";
		case 1: // Year Month Day
			return "$year-$month-$day";
		case 2: // Day Month Year
			return "$day-$month-$year";
		default:
			soft_error("Invalid date_format");
			return "";
	}
}

function format_time_string($hour, $minute, $hour24)
{
	if(!$hour24) {
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

// parses a description and adds the appropriate mark-up
function parse_desc($text)
{
	return \Parsedown::instance()->parse($text);
}

function days_in_year_ts($timestamp) {
	return 365 + date('L', $timestamp);
}

function days_in_year($year) {
	return days_in_year_ts(mktime(0, 0, 0, 1, 1, $year));
}

function add_days($stamp, $days)
{
	if($stamp == NULL)
		return NULL;

	return mktime(date('H', $stamp), date('i', $stamp), date('s', $stamp),
		date('n', $stamp), date('j', $stamp) + $days,
		date('Y', $stamp));
}

function add_months($stamp, $months)
{
	if($stamp == NULL)
		return NULL;

	return mktime(date('H', $stamp), date('i', $stamp), date('s', $stamp),
		date('m', $stamp) + $months, date('d', $stamp),
		date('Y', $stamp));
}

function add_years($stamp, $years)
{
	if($stamp == NULL)
		return NULL;

	return mktime(date('H', $stamp), date('i', $stamp), date('s', $stamp),
		date('m', $stamp), date('d', $stamp),
		date('Y', $stamp) + $years);
}

function days_between($ts1, $ts2) {
	// First date always comes first
	if($ts1 > $ts2)
		return -days_between($ts2, $ts1);

	// If we're in different years, keep adding years until we're in
	//   the same year
	if(date('Y', $ts2) > date('Y', $ts1))
		return days_in_year_ts($ts1)
		+ days_between(add_years($ts1, 1), $ts2);

	// The years are equal, subtract day of the year of each
	return date('z', $ts2) - date('z', $ts1);
}

// Adapted from Drupal
function phpc_get_private_key() {
	static $key;

	if(!isset($key))
		$key = phpc_hash_base64(random_bytes(55));

	return $key;
}

function phpc_get_token($value='') {
	return phpc_hmac_base64($value, session_id() . phpc_get_private_key()
		. phpc_get_hash_salt());
}

// Stolen from Drupal
function phpc_hmac_base64($data, $key) {
	$hmac = base64_encode(hash_hmac('sha256', $data, $key, TRUE));
	// Modify the hmac so it's safe to use in URLs.
	return strtr($hmac, array('+' => '-', '/' => '_', '=' => ''));
}

// Stolen from Drupal
function phpc_hash_base64($data) {
	$hash = base64_encode(hash('sha256', $data, TRUE));
	// Modify the hash so it's safe to use in URLs.
	return strtr($hash, array('+' => '-', '/' => '_', '=' => ''));
}

// Adapted from Drupal
function phpc_get_hash_salt() {
	return hash('sha256', SQL_HOST . SQL_USER . SQL_PASSWD . SQL_DATABASE . SQL_PREFIX);
}

function login_user(Context $context, $username, $password)
{
	// Regenerate the session in case our non-logged in version was
	//   snooped
	// TODO: Verify that this is needed, and make sure it's called in setup
	// 	 so it doesn't create issues for embedded users
	// session_regenerate_id();

	$user = $context->db->get_user_by_name($username);
	if(!$user || $user->get_password() != md5($password))
		return false;

	phpc_do_login($context, $user, false);

	return true;
}

function phpc_do_login(Context $context, User $user, $series_token = false) {
	$uid = $user->get_uid();
	$login_token = phpc_get_token();
	$_SESSION['uid'] = $uid;
	$_SESSION['login'] = $login_token;

	if(!$series_token) {
		$series_token = phpc_get_token();
		$context->db->add_login_token($uid, $series_token, $login_token);
	} else {
		$context->db->update_login_token($uid, $series_token, $login_token);
	}

	// TODO: Add a remember me checkbox to the login form, and have the
	//	cookies expire at the end of the session if it's not checked

	// expire credentials in 30 days.
	$expiration_time = time() + 30 * 24 * 60 * 60;
	setcookie('uid', $uid, $expiration_time);
	setcookie('login', $login_token, $expiration_time);
	setcookie('login_series', $series_token, $expiration_time);

	return true;
}

function phpc_do_logout() {
   	session_destroy();
	setcookie('uid', "", time() - 3600);
	setcookie('login', "", time() - 3600);
}

// returns tag data for the links at the bottom of the calendar
function footer(Context $context)
{
	$tag = tag('div', new AttributeList('class="phpc-bar ui-widget-content"'),
			"[" . __('Language') . ": {$context->getLang()}]" .
			" [" . __('Timezone') . ": {$context->getTimezone()}]");

	if(defined('PHPC_DEBUG')) {
		$tag->add(tag('a', new AttributeList('href="http://validator.w3.org/check/referer"'), 'Validate HTML'));
		$tag->add(tag('a', new AttributeList('href="http://jigsaw.w3.org/css-validator/check/referer"'),
					'Validate CSS'));
		$tag->add(tag('span', "Internal Encoding: " . mb_internal_encoding() . " Output Encoding: " . mb_http_output()));
	}

	return $tag;
}

function get_languages()
{
	static $langs = NULL;

	if(!empty($langs))
		return $langs;

	// create links for each existing language translation
	$handle = opendir(PHPC_ROOT_PATH . '/locale');

	if(!$handle)
		soft_error("Error reading locale directory.");

	$langs = array('en');
	while(($filename = readdir($handle)) !== false) {
		$pathname = PHPC_ROOT_PATH . "/locale/$filename";
		if(strncmp($filename, ".", 1) == 0 || !is_dir($pathname))
			continue;
		if(file_exists("$pathname/LC_MESSAGES/messages.mo"))
			$langs[] = $filename;
	}

	closedir($handle);

	return $langs;
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week($month, $day, $year, $week_start)
{
	return day_of_week_ts(mktime(0, 0, 0, $month, $day, $year), $week_start);
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week_ts($timestamp, $week_start)
{
	$days = date('w', $timestamp);

	return ($days + 7 - $week_start) % 7;
}

// returns the number of days in $month
function days_in_month($month, $year)
{
	return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year, $week_start)
{
	$days = days_in_month($month, $year);

	// days not in this month in the partial weeks
	$days_before_month = day_of_week($month, 1, $year, $week_start);
	$days_after_month = 6 - day_of_week($month, $days, $year, $week_start);

	// add up the days in the month and the outliers in the partial weeks
	// divide by 7 for the weeks in the month
	return ($days_before_month + $days + $days_after_month) / 7;
}

function weeks_in_year($year, $week_start) {
	// This is true for ISO, not US
	if($week_start == 1)
		return date("W", mktime(0, 0, 0, 12, 28, $year));
	// else
	return ceil((day_of_week(1, 1, $year, $week_start) + days_in_year($year)) / 7.0);
}

/**
 * @param int $month
 * @param int $day
 * @param int $year
 * @param int $week_start
 * @return float
 */
// return the week number corresponding to the $day.
function week_of_year($month, $day, $year, $week_start)
{
	$timestamp = mktime(0, 0, 0, $month, $day, $year);

	// week_start = 1 uses ISO 8601 and contains the Jan 4th,
	//   Most other places the first week contains Jan 1st
	//   There are a few outliers that start weeks on Monday and use
	//   Jan 1st for the first week. We'll ignore them for now.
	if($week_start == 1) {
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
	$days_before_year = day_of_week(1, $year_contains, $year, $week_start);

	// Days left in the week
	$days_left = 8 - day_of_week_ts($timestamp, $week_start) - $year_contains;

	/* find the number of weeks by adding the days in the week before
	 * the start of the year, days up to $day, and the days left in
	 * this week, then divide by 7 */
	return ($days_before_year + $day_of_year + $days_left) / 7;
}

/**
 * @param Context $context
 * @param ActionItem $item
 * @param string $eid
 * @return Html
 */
function create_event_link(Context $context, ActionItem $item, $eid)
{
	$item->addArgument("eid", $eid);
	return create_action_link($context, $item);
}

/**
 * @param Context $context
 * @param ActionItem $item
 * @param string $oid
 * @return Html
 */
function create_occurrence_link(Context $context, ActionItem $item, $oid)
{
	$item->addArgument("oid", $oid);
	return create_action_link($context, $item);
}

/**
 * @param Context $context
 * @param ActionItem $item
 * @param null|string $year
 * @param null|string $month
 * @param null|string $day
 * @return Html
 */
function create_action_link_with_date(Context $context, ActionItem $item, $year = null, $month = null, $day = null)
{
	if($year !== null)
		$item->addArgument("year", $year);
	if($month !== null)
		$item->addArgument("month", $month);
	if($day !== null)
		$item->addArgument("day", $day);

	return create_action_link($context, $item);
}

/**
 * @param Context $context
 * @param ActionItem $item
 * @return Html
 */
function create_action_link(Context $context, ActionItem $item)
{
	$url = 'href="' . $context->script . '?action=' . htmlentities($item->getAction());

	$args = $item->getArguments();
	if (!$args) {
		$args = array();
	}
	if (!array_key_exists("phpcid", $args)) {
		$args["phpcid"] = htmlentities($context->getCalendar()->cid);
	}

	foreach ($args as $key => $value) {
		if(empty($value))
			continue;
		if (is_array($value)) {
			foreach ($value as $v) {
				$url .= "&amp;" . htmlentities("{$key}[]=$v");
			}
		} else {
			$url .= "&amp;" . htmlentities("$key=$value");
		}
	}
	$url .= '"';

	$attributes = $item->getAttributes();
	if($attributes != null) {
		$attributes->add($url);
	} else {
		$attributes = new AttributeList($url);
	}
	return tag('a', $attributes, $item->getText());
}

// takes a menu $html and appends an entry
/**
 * @param Context $context
 * @param Html $html
 * @param ActionItem $item
 */
function menu_item_append(Context $context, Html &$html, ActionItem $item)
{
	$html->add(create_action_link($context, $item));
	$html->add("\n");
}

// creates a hidden input for a form
// returns tag data for the input
/**
 * @param string $name
 * @param string $value
 * @return Html
 */
function create_hidden($name, $value)
{
	return tag('input', new AttributeList("name=\"$name\"", "value=\"$value\"", 'type="hidden"'));
}

// creates a submit button for a form
// return tag data for the button
/**
 * @param string $value
 * @return Html
 */
function create_submit($value)
{
	return tag('input', new AttributeList("value=\"$value\"", 'type="submit"'));
}

// creates a text entry for a form
// returns tag data for the entry
/**
 * @param string $name
 * @param null|string $value
 * @return Html
 */
function create_text($name, $value = null)
{
	$attributes = new AttributeList("name=\"$name\"", 'type="text"');
	if($value !== null) {
		$attributes->add("value=\"$value\"");
	}
	return tag('input', $attributes);
}

// creates a password entry for a form
// returns tag data for the entry
/**
 * @param string $name
 * @return Html
 */
function create_password($name)
{
	return tag('input', new AttributeList("name=\"$name\"", 'type="password"'));
}

// creates a checkbox for a form
// returns tag data for the checkbox
/**
 * @param string $name
 * @param string $value
 * @param bool $checked
 * @param null|string $label
 * @return array|Html
 */
function create_checkbox($name, $value, $checked = false, $label = null)
{
	$attrs = new AttributeList("id=\"$name\"", "name=\"$name\"", 'type="checkbox"', "value=\"$value\"");
	if($checked)
		$attrs->add('checked="checked"');
	$input = tag('input', $attrs);
	if($label !== null)
		return array($input, tag('label', new AttributeList("for=\"$name\""), $label));
	else
		return $input;
}

// $title - string or html element displayed by default
// $values - Array of URL => title
// returns an html structure for a dropdown box that will change the page
//		to the URL from $values when an element is selected
/**
 * @param string $title
 * @param string[] $values
 * @return Html
 */
function create_dropdown_list($title, $values) {
	$list = tag('ul');
	foreach($values as $key => $value) {
		$list->add(tag('li', tag('a', new AttributeList("href=\"$key\""), $value)));
	}
	return tag('div', new AttributeList('class="phpc-dropdown-list"'),
			tag('span', new AttributeList('class="phpc-dropdown-list-header"'),
				tag('span', new AttributeList('class="phpc-dropdown-list-title"'),
					$title)),
			$list);
}

function fa($name)
{
	return "<span class=\"fa fa-$name\"></span>";
}

// creates the user menu
// returns tag data for the menu
/**
 * @param Context $context
 * @return Html
 */
function user_menu(Context $context)
{
	if($context->getUser()->is_user()) {
		$welcome = __('Welcome') . '&nbsp;' . $context->getUser()->get_username();
	} else {
		$welcome = "";
	}

	$span = tag('span');
	
	$html = tag('div', new AttributeList('class="phpc-logged ui-widget-content"'), $welcome, $span);

	if($context->getAction() != 'user_settings')
		$span->add(create_action_link($context, new ActionItem(__('Settings'), 'user_settings', false, false, 'cog')));
		
	if($context->getUser()->is_user()) {
		menu_item_append($context, $span, new ActionItem(__('Log out'), 'logout',
				array('lasturl' => escape_entities(urlencode($_SERVER['QUERY_STRING']))),
				null, 'sign-out'));
	} else {
		menu_item_append($context, $span, new ActionItem(__('Log in'), 'login',
				array('lasturl' => escape_entities(urlencode($_SERVER['QUERY_STRING']))),
				null, 'sign-in'));
	}
	return $html;
}

// creates the navbar for the top of the calendar
// returns tag data for the navbar
/**
 * @param Context $context
 * @return Html
 */
function navbar(Context $context)
{
	$cal = $context->getCalendar();
	$action = $context->getAction();

	$html = tag('div', new AttributeList('class="phpc-bar ui-widget-header"'));

	$args = array('year' => $context->getYear(),
			'month' => $context->getMonth(),
			'day' => $context->getDay());

	// TODO There needs to be a better way to decide what to show
	if($cal->can_write($context->getUser()) && $action != 'add') {
		menu_item_append($context, $html, new ActionItem(__('Add Event'), 'event_form', $args));
	}

	if($action != 'search') {
		menu_item_append($context, $html, new ActionItem(__('Search'), 'search', $args));
	}

	if($action != 'display_month') {
		menu_item_append($context, $html, new ActionItem(__('View Month'), 'display_month', $args));
	}

	if($action == 'display_event') {
		menu_item_append($context, $html, new ActionItem(__('View date'), 'display_day', $args));
	}

	if($cal->can_admin($context->getUser()) && $action != 'cadmin') {
		menu_item_append($context, $html, new ActionItem(__('Calendar Admin'), 'cadmin'));
	}

	if($context->getUser()->is_admin() && $action != 'admin') {
		menu_item_append($context, $html, new ActionItem(__('Admin'), 'admin'));
	}

	return $html;
}

// creates an array from $start to $end, with an $interval
/**
 * @param int $start
 * @param int $end
 * @param int $interval
 * @param null|callable $display
 * @return array
 */
function create_sequence($start, $end, $interval = 1, $display = null)
{
	$arr = array();
	for ($i = $start; $i <= $end; $i += $interval){
		if(is_callable($display)) {
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
	$languages = array("" => __("Default"));
	foreach(get_languages() as $language) {
		$languages[$language] = $language;
	}
	// name, text, type, value(s)
	return array( 
			array('week_start', __('Week Start'), PHPC_DROPDOWN,
				array(
					0 => __('Sunday'),
					1 => __('Monday'),
					6 => __('Saturday')
				     ), 0),
			array('hours_24', __('24 Hour Time'), PHPC_CHECK),
			array('title', __('Calendar Title'), PHPC_TEXT),
			array('subject_max', __('Maximum Subject Length'), PHPC_TEXT, 50),
			array('events_max', __('Events Display Daily Maximum'), PHPC_TEXT, 8),
			array('anon_permission', __('Public Permissions'), PHPC_DROPDOWN,
				array(
					__('Cannot read nor write events'),
					__('Can read but not write events'),
					__('Can create but not modify events'),
					__('Can create and modify events')
				     )
			     ),
			array('timezone', __('Default Timezone'), PHPC_MULTI_DROPDOWN, get_timezone_list()),
			array('language', __('Default Language'), PHPC_DROPDOWN,
				$languages),
			array('date_format', __('Date Format'), PHPC_DROPDOWN,
					get_date_format_list()),
			array('theme', __('Theme'), PHPC_DROPDOWN,
					get_theme_list()),
	);
}

/**
 * @return string[]
 */
function get_theme_list() {
	$themes = [
			'black-tie',
			'blitzer',
			'cupertino',
			'dark-hive',
			'dot-luv',
			'eggplant',
			'excite-bike',
			'flick',
			'hot-sneaks',
			'humanity',
			'le-frog',
			'mint-choc',
			'overcast',
			'pepper-grinder',
			'redmond',
			'smoothness',
			'south-street',
			'start',
			'sunny',
			'swanky-purse',
			'trontastic',
			'ui-darkness',
			'ui-lightness',
			'vader'];

	$theme_list = [NULL => __('Default')];
	foreach($themes as $theme) {
		$theme_list[$theme] = $theme;
	}
	return $theme_list;	
}

/**
 * @return string[]
 */
function get_timezone_list() {
	$timezones = array();
	$timezones[__("Default")] = "";
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

/**
 * @return string[]
 */
function get_date_format_list()
{
	return [__("Month Day Year"),
			__("Year Month Day"),
			__("Day Month Year")];
}

/**
 * @param Context $context
 * @return Html
 */
function display_phpc(Context $context)
{
	$navbar = false;

	try {
		$calendars = $context->db->get_calendars();
		$list = array();
		$cal = $context->getCalendar();
		if(!empty($cal)) {
			$title = $cal->get_title();
			$title_link = tag('a', new AttributeList("href=\"{$context->script}?phpcid={$cal->get_cid()}\"",
						'class="phpc-dropdown-list-title"'), $title);
		} else {
			$title = __("(No calendars)");
			$title_link = $title;
		}
		foreach($calendars as $calendar) {
			$list[$context->script . '?phpcid=' . $calendar->get_cid()] = $calendar->get_title();
		}
		if (sizeof($calendars) > 1) {
			$title_tag = create_dropdown_list($title_link, $list);
		} else {
			$title_tag = $title_link;
		}
		$content = get_page($context->getAction())->display($context);
		$messages = $context->getMessages();
		if(sizeof($messages) > 0) {
			$messageHtml = tag('div');
			foreach($messages as $message) {
				$messageHtml->add($message);
			}

			$_SESSION['messages'] = NULL;
		} else {
			$messageHtml = '';
		}

		return tag('div', new AttributeList('class="php-calendar ui-widget"'),
				user_menu($context),
				tag('br', new AttributeList('style="clear:both;"')),
				tag('div', new AttributeList('class="phpc-title ui-widget-header"'), $title_tag),
				navbar($context), $messages, $content, footer($context));

	} catch(PermissionException $e) {
		$msg = __('You do not have permission to do that: ') . $e->getMessage();
		if($context->getUser()->is_user())
			return error_message_redirect($context, $msg, $script);
		else
			return error_message_redirect($context, $msg,
					"{$context->script}?action=login");
	} catch(InvalidInputException $e) {
		return error_message_redirect($context, $e->getMessage(), $e->target);
	} catch(\Exception $e) {
		return display_exception($e, $navbar);
	}
}

function display_exception(\Exception $e, $navbar = false)
{
	$results = tag('');
	if($navbar !== false)
		$results->add($navbar);
	$backtrace = tag("ol");
	foreach($e->getTrace() as $bt) {
		$filename = basename($bt["file"]);
		$args = array();
		if(isset($bt["args"])) { 
			foreach($bt["args"] as $arg) {
				if(is_string($arg)) {
					$args[] = "'$arg'";
				} elseif(is_object($arg)) {
					$args[] = get_class($arg);
				} else {
					$args[] = $arg;
				}
			}
			$args_string = implode(", ", $args);
		} else {
			$args_string = "...";
		}
		$backtrace->add(tag("li", "$filename({$bt["line"]}): {$bt["function"]}($args_string)"));
	}
	$results->add(tag('div', new AttributeList('class="php-calendar"'),
				tag('h2', __('Error')),
				tag('p', $e->getMessage()),
				tag('h3', __('Backtrace')),
				$backtrace));
	return $results;
}

function get_page($action)
{
	switch($action) {
		case 'display_month':
			return new MonthPage;
		case 'display_day':
			return new DayPage;
		case 'login':
			return new LoginPage;
		default:
			soft_error(__('Invalid action'));
	}
}

// takes a number of the month, returns the name
function month_name($month)
{
	$month = ($month - 1) % 12 + 1;
	switch($month) {
		case 1:
			return __('January');
		case 2:
			return __('February');
		case 3:
			return __('March');
		case 4:	
			return __('April');
		case 5:	
			return __('May');
		case 6:
			return __('June');
		case 7:
			return __('July');
		case 8:
			return __('August');
		case 9:
			return __('September');
		case 10:
			return __('October');
		case 11:
			return __('November');
		case 12:
			return __('December');
	}
}

//takes a day number of the week, returns a name (0 for the beginning)
function day_name($day)
{
	$day = $day % 7;

	switch($day) {
		case 0:
			return __('Sunday');
		case 1:
			return __('Monday');
		case 2:
			return __('Tuesday');
		case 3:	
			return __('Wednesday');
		case 4:
			return __('Thursday');
		case 5:	
			return __('Friday');
		case 6:
			return __('Saturday');
	}
}

function short_month_name($month)
{
	$month = ($month - 1) % 12 + 1;

	switch($month) {
		case 1:
			return __('Jan');
		case 2:
			return __('Feb');
		case 3:
			return __('Mar');
		case 4:
			return __('Apr');
		case 5:
			return __('May');
		case 6:
			return __('Jun');
		case 7:
			return __('Jul');
		case 8:
			return __('Aug');
		case 9:
			return __('Sep');
		case 10:
			return __('Oct');
		case 11:
			return __('Nov');
		case 12:
			return __('Dec');
	}
}

function verify_token(Context $context)
{
	if(!$context->user->is_user())
		return true;

	if(empty($_REQUEST["phpc_token"]) || $_REQUEST["phpc_token"] != $context->token) {
		//echo "<pre>real token: $token\n";
		//echo "form token: {$_REQUEST["phpc_token"]}</pre>";
		soft_error(__("Secret token mismatch. Possible request forgery attempt."));
	}
}

// $element: { name, text, type, value(s) }
function create_config_input($element, $default = false)
{
	$name = $element[0];
	$text = $element[1];
	$type = $element[2];
	$value = false;
	if(isset($element[3]))
		$value = $element[3];

	switch($type) {
		case PHPC_CHECK:
			if($default == false)
				$default = $value;
			$input = create_checkbox($name, '1', $default, $text);
			break;
		case PHPC_TEXT:
			if($default == false)
				$default = $value;
			$input = create_text($name, $default);
			break;
		case PHPC_DROPDOWN:
			$input = create_select($name, $value, $default);
			break;
		case PHPC_MULTI_DROPDOWN:
			$input = create_multi_select($name, $value, $default);
			break;
		default:
			soft_error(__('Unsupported config type') . ": $type");
	}
	return $input;
}

/* Make a timestamp from the input fields $prefix-time and $prefix-date
   uses $cal->date_format to determine the format of the date
   if there's no $prefix-time, uses values passed as parameters
*/
function get_timestamp($context, $prefix, $hour = 0, $minute = 0, $second = 0)
{
	check_input("$prefix-date");

	if(!empty($_REQUEST["$prefix-time"])) {
		if(!preg_match('/(\d+)[:\.](\d+)\s?(\w+)?/', $_REQUEST["$prefix-time"],
					$time_matches)) {
			throw new \Exception(sprintf(__("Malformed \"%s\" time: \"%s\""), $prefix, $_REQUEST["$prefix-time"]));
		}
		$hour = $time_matches[1];
		$minute = $time_matches[2];
		if(isset($time_matches[3])) {
			$period = $time_matches[3];
			if($hour == 12)
				$hour = 0;
			if(strcasecmp("am", $period) == 0) {
				// AM
			} else if(strcasecmp("pm", $period) == 0) {
				$hour += 12;
			} else {
				throw new \Exception(__("Unrecognized period: ") . $period);
			}
		}
	}

	if(!preg_match('/(\d+)[\.\/\-\ ](\d+)[\.\/\-\ ](\d+)/', $_REQUEST["$prefix-date"], $date_matches)) {
		throw new \Exception(sprintf(__("Malformed \"%s\" date: \"%s\""), $prefix, $_REQUEST["$prefix-date"]));
	}
	
	switch($context->calendar->date_format) {
		case 0: // Month Day Year
			$month = $date_matches[1];
			$day = $date_matches[2];
			$year = $date_matches[3];
			break;
		case 1: // Year Month Day
			$year = $date_matches[1];
			$month = $date_matches[2];
			$day = $date_matches[3];
			break;
		case 2: // Day Month Year
			$day = $date_matches[1];
			$month = $date_matches[2];
			$year = $date_matches[3];
			break;
		default:
			throw new \Exception(__("Invalid date_format."));
	}

	return mktime($hour, $minute, $second, $month, $day, $year);
}

function print_update_form() {
	global $script;

	echo "<!DOCTYPE html>
<html>
  <head>
    <title>PHP-Calendar Update</title>
  </head>
  <body>
    <h2>PHP-Calendar Updater</h2>
    <p>Your PHP-Calendar database needs to be updated. You should make a backup of your existing database before running the updater.
    <p><a href=\"$script?update=1\">Update now</a>
  </body>
</html>";
}

/*
 * creates an Html data structure
 * arguments are tagName [AttributeList] [Html | array | string] ...
 * where array contains an array, Html, or a string, same requirements for that
 * array
 */
function tag()
{
        $args = func_get_args();
        $html = new Html();
        call_user_func_array(array(&$html, '__construct'), $args);
        return $html;
}

function setup(Context $context)
{
	ini_set('arg_separator.output', '&amp;');
	mb_internal_encoding('UTF-8');
	mb_http_output('pass');

	if(defined('PHPC_DEBUG')) {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		ini_set('html_errors', 1);
	}

	session_start();

	require_once(PHPC_ROOT_PATH . '/src/schema.php');
	if ($context->db->get_config('version') < PHPC_DB_VERSION) {
		if(isset($_GET['update'])) {
			phpc_updatedb($context);
		} else {
			print_update_form();
		}
		exit;
	}

	if(empty($_SESSION["uid"])) {
		if(!empty($_COOKIE["login"])
			&& !empty($_COOKIE["uid"])) {
			// Cleanup before we check their token so they can't login with
			//   an ancient token
			$context->db->cleanup_login_tokens();

			// FIXME should this be _SESSION below?
			$uid = $_COOKIE["uid"];
			if($context->db->validate_login_token($uid, $_COOKIE["login"])) {
				$user = $context->db->get_user($uid);
				phpc_do_login($context, $user);
			} else {
				$uid = 0;
			}
		}
	} else {
		$token = $_SESSION["{$prefix}login"];
	}

	if(empty($token))
		$token = '';

	if(empty($vars['content']))
		$vars['content'] = "html";

	if(!empty($vars['clearmsg']))
		$_SESSION["{$prefix}messages"] = NULL;

	$messages = array();

	if(!empty($_SESSION["{$prefix}messages"])) {
		foreach($_SESSION["{$prefix}messages"] as $message) {
			$messages[] = $message;
		}
	}

}

?>
