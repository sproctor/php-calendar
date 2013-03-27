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

$phpc_home_url="$phpc_protocol://$phpc_server$phpc_script";
$phpc_url = $phpc_home_url
		. (empty($_SERVER['QUERY_STRING']) ? ''
		   : '?' . $_SERVER['QUERY_STRING']);

// Remove this line if you must
ini_set('arg_separator.output', '&amp;');

/*
 * Do not modify anything under this point
 */

define('IN_PHPC', true);

try {
	require_once("$phpc_includes_path/calendar.php");
	require_once("$phpc_includes_path/setup.php");

	$welcome='Welcome anonymous user';
	if (isset($phpc_user)) $welcome='Welcome '.$phpc_user->username;
	
	$calendar_title = $phpc_cal->get_title();
	$content = tag('div', attributes('class="php-calendar ui-widget"'),
			tag('div', attributes('class="phpc-logged ui-widget-content"'), $welcome),
			tag('h1', attrs('class="ui-widget-header"'),
				tag('a', attributes("href='$phpc_home_url?phpcid=$phpc_cal->get_cid()'"),
					$calendar_title)),
			display_phpc());
} catch(Exception $e) {
	$calendar_title = $e->getMessage();
	$content = tag('div', attributes('class="php-calendar"'),
			$e->getMessage());
}

$html = tag('html', attrs("lang=\"$phpc_lang\""),
		tag('head',
			tag('title', $calendar_title),
			tag('link', attrs('rel="icon"',
					"href=\"static/office-calendar.png\"")),
			get_header_tags("static"),
			tag('meta', attrs('http-equiv="Content-Type"',
					   'content="text/html; charset=UTF-8"'))),
		tag('body', $content));

echo '<!DOCTYPE html>', "\n", $html->toString();
?>
