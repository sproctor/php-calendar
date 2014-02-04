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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

require_once("$phpc_includes_path/lib_autolink.php");

// called when some error happens
function soft_error($message)
{
	throw new Exception(htmlspecialchars($message, ENT_COMPAT, "UTF-8"));
}

class PermissionException extends Exception {
}

function permission_error($message)
{
	throw new PermissionException(htmlspecialchars($message, ENT_COMPAT,
				"UTF-8"));
}

function minute_pad($minute)
{
	return sprintf('%02d', $minute);
}

function redirect($page) {
	global $phpc_script, $phpc_server, $phpc_redirect;

	session_write_close();

	$phpc_redirect = true;

	if($page{0} == "/") {
		$dir = '';
	} else {
		$dir = dirname($phpc_script) . "/";
	}
	$url = "//$phpc_server$dir$page";

	header("Location: $url");
}

function message_redirect($message, $page) {
	global $phpc_prefix;

	if(empty($_SESSION["{$phpc_prefix}messages"]))
		$_SESSION["{$phpc_prefix}messages"] = array();

	$_SESSION["{$phpc_prefix}messages"][] = $message;
	redirect($page);

	$continue_url = $page . '&amp;clearmsg=1';

	if (get_class($message)=='Html') $message=$message->toString();
	
	return tag('div', attrs('class="phpc-box"'), "$message ",
 		tag('a', attrs("href=\"$continue_url\""), __("continue")));
}

function message($message) {
	global $phpc_messages;

	$phpc_messages[] = $message;
}

function stripslashes_r($var) {
	if (is_array($var))
		return array_map("stripslashes_r", $var);
	else
		return stripslashes($var);
}

function real_escape_r($var) {
	global $phpcdb;

	if(is_array($var))
		return array_map("real_escape_r", $var);
	else
		return mysqli_real_escape_string($phpcdb->dbh, $var);
}

function asbool($val)
{
	return $val ? "1" : "0";
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

// called when some error happens
function display_error($str)
{
	echo '<html><head><title>', __('Error'), "</title></head>\n",
	     '<body><h1>', __('Software Error'), "</h1>\n",
	     "<h2>", __('Message:'), "</h2>\n",
	     "<pre>$str</pre>\n",
	     "<h2>", __('Backtrace'), "</h2>\n",
	     "<ol>\n";
	foreach(debug_backtrace() as $bt) {
		echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
	}
	echo "</ol>\n",
	     "</body></html>\n";
	exit;
}

// parses a description and adds the appropriate mark-up
function parse_desc($text)
{
	// Don't allow tags and make the description HTML-safe
        $text = htmlspecialchars($text, ENT_COMPAT, "UTF-8");

        $text = nl2br($text);

	// linkify urls
	$text = autolink($text, 0);

	// linkify emails
	$text = autolink_email($text);

	return $text;
}

function days_in_year($timestamp) {
	return 365 + date('L', $timestamp);
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
		return days_in_year($ts1)
			+ days_between(add_years($ts1, 1), $ts2);

	// The years are equal, subtract day of the year of each
	return date('z', $ts2) - date('z', $ts1);
}

// Stolen from Drupal
function phpc_random_bytes($count) {
	// $random_state does not use drupal_static as it stores random bytes.
	static $random_state, $bytes, $php_compatible;
	// Initialize on the first call. The contents of $_SERVER includes a
	// mix of user-specific and system information that varies a little
	// with each page.
	if (!isset($random_state)) {
		$random_state = print_r($_SERVER, TRUE);
		if (function_exists('getmypid')) {
			// Further initialize with the somewhat random PHP process ID.
			$random_state .= getmypid();
		}
		$bytes = '';
	}
	if (strlen($bytes) < $count) {
		// PHP versions prior 5.3.4 experienced openssl_random_pseudo_bytes()
		// locking on Windows and rendered it unusable.
		if (!isset($php_compatible)) {
			$php_compatible = version_compare(PHP_VERSION, '5.3.4', '>=');
		}
		// /dev/urandom is available on many *nix systems and is
		// considered the best commonly available pseudo-random source.
		if ($fh = @fopen('/dev/urandom', 'rb')) {
			// PHP only performs buffered reads, so in reality it
			// will always read at least 4096 bytes. Thus, it costs
			// nothing extra to read and store that much so as to
			// speed any additional invocations.
			$bytes .= fread($fh, max(4096, $count));
			fclose($fh);
		}
		// openssl_random_pseudo_bytes() will find entropy in a
		// system-dependent  way.
		elseif ($php_compatible && function_exists('openssl_random_pseudo_bytes')) {
			$bytes .= openssl_random_pseudo_bytes($count - strlen($bytes));
		}
		// If /dev/urandom is not available or returns no bytes, this
		// loop will generate a good set of pseudo-random bytes on any
		// system.
		// Note that it may be important that our $random_state is
		// passed through hash() prior to being rolled into $output,
		// that the two hash()
		// invocations are different, and that the extra input into the
		// first one - the microtime() - is prepended rather than
		// appended. This is to avoid directly leaking $random_state
		// via the $output stream, which could allow for trivial
		// prediction of further "random" numbers.
		while (strlen($bytes) < $count) {
			$random_state = hash('sha256', microtime() . mt_rand() . $random_state);
			$bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
		}
	}
	$output = substr($bytes, 0, $count);
	$bytes = substr($bytes, $count);
	return $output;
}

// Adapted from Drupal
function phpc_get_private_key() {
	static $key;

	if(!isset($key))
		$key = phpc_hash_base64(phpc_random_bytes(55));

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
?>
