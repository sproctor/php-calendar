<?php
/*
 * Copyright 2010 Sean Proctor
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
	global $phpc_script, $phpc_server, $phpc_protocol;

	if($page{0} == "/") {
		$dir = '';
	} else {
		$dir = dirname($phpc_script) . "/";
	}

	header("Location: $phpc_protocol://$phpc_server$dir$page");
}

function message($message, $page) {
	if(empty($_SESSION['messages']))
		$_SESSION['messages'] = array();

	$_SESSION['messages'][] = $message;
	redirect($page);

	$continue_url = $page . '&amp;clearmsg=1';

	return tag('div', attrs('class="phpc-box"'), "$message ",
 		tag('a', attrs("href=\"$continue_url\""), _("continue")));
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
			+ days_between(add_year($ts1, 1), $ts2);

	// The years are equal, subtract day of the year of each
	return date('z', $ts2) - date('z', $ts1);
}
?>
