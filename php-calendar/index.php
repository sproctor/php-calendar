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

/*
 * The following variables are intended to be modified to fit your
 * setup.
 */

/*
 * If you want different scripts with different default calendars, you can
 * copy this script and modify $default_calendar_id to contain the CID of
 * the calendar you want to be the default
 */
$default_calendar_id = 1;

/*
 * $phpc_root_path gives the location of the base calendar install.
 * if you move this file to a new location, modify $phpc_root_path to point
 * to the location where the support files for the callendar are located.
 */
$phpc_root_path = dirname(__FILE__);
$phpc_includes_path = "$phpc_root_path/includes";
$phpc_config_file = "$phpc_root_path/config.php";
$phpc_locale_path = "$phpc_root_path/locale";
$phpc_script = htmlentities($_SERVER['PHP_SELF']);

if(!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
	$phpc_protocol = "https";
else
	$phpc_protocol = "http";

$phpc_server = $_SERVER['SERVER_NAME'];
if(!empty($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != 80)
	$phpc_server .= ":{$_SERVER["SERVER_PORT"]}";

$phpc_url = "$phpc_protocol://$phpc_server$phpc_script"
		. (empty($_SERVER['QUERY_STRING']) ? ''
		   : '?' . $_SERVER['QUERY_STRING']);

// Remove this line if you must
ini_set('arg_separator.output', '&amp;');

/*
 * Do not modify anything under this point
 */

define('IN_PHPC', true);

try {
	require_once("$phpc_includes_path/setup.php");
	require_once("$phpc_includes_path/calendar.php");

	if ($vars["contentType"] == "json") {
		echo do_action();
		exit;
	}

	$calendar_title = get_config($phpcid, 'calendar_title');
	$content = tag('div', attributes('class="php-calendar"'),
			tag('h1', $calendar_title),
			display_phpc());
} catch(Exception $e) {
	$calendar_title = $e->getMessage();
	$content = tag('div', attributes('class="php-calendar"'),
			$e->getMessage());
}

if(defined('PHPC_DEBUG'))
	$jquery_path = 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.js';
else
	$jquery_path = 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js';

$html = tag('html', attrs("lang=\"$phpc_lang\""),
		tag('head',
			tag('title', $calendar_title),
			tag('link', attrs('rel="stylesheet"', 'type="text/css"',
					'href="static/style.css"')),
			tag("script", attrs('type="text/javascript"',
					"src=\"$jquery_path\""), ''),
			tag('script', attrs('type="text/javascript"',
					'src="static/script.js"'), ''),
			tag('meta', attrs('http-equiv="Content-Type"',
					   'content="text/html; charset=UTF-8"'))),
		tag('body', $content));

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">', "\n", $html->toString();
?>
