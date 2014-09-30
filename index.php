<?php
/*
 * Copyright 2013 Sean Proctor
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
 * $phpc_root_path gives the location of the base calendar install.
 * if you move this file to a new location, modify $phpc_root_path to point
 * to the location where the support files for the callendar are located.
 */
$phpc_root_path = dirname(__FILE__);
$phpc_includes_path = "$phpc_root_path/includes";
$phpc_config_file = "$phpc_root_path/config.php";
$phpc_locale_path = "$phpc_root_path/locale";

// path of index.php. ex. /php-calendar/index.php
$phpc_script = htmlentities($_SERVER['SCRIPT_NAME']);
$phpc_url_path = dirname($_SERVER['SCRIPT_NAME']);

// Port
$phpc_port = "";
if(!empty($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != 80)
	$phpc_port = ":{$_SERVER["SERVER_PORT"]}";

// ex. www.php-calendar.com
$phpc_server = $_SERVER['SERVER_NAME'] . $phpc_port;

// Protcol ex. http or https
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'
		|| $_SERVER['SERVER_PORT'] == 443
		|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
		|| isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
    $phpc_proto = "https";
} else {
    $phpc_proto = "http";
}

$phpc_home_url="$phpc_proto://$phpc_server$phpc_script";
$phpc_url = $phpc_home_url . (empty($_SERVER['QUERY_STRING']) ? ''
		: '?' . $_SERVER['QUERY_STRING']);

$phpc_static_path = "$phpc_url_path/static";

if(defined('PHPC_DEBUG'))
	$phpc_min = '';
else
	$phpc_min = '.min';
		
$phpc_theme = $phpc_cal->theme;
if(empty($phpc_theme))
	$phpc_theme = 'smoothness';
$jquery_version = "1.11.1";
$jqueryui_version = "1.11.1";
$fa_version = "4.2.0";

if(!isset($phpc_jqui_path))
	$phpc_jqui_path = "//ajax.googleapis.com/ajax/libs/jqueryui/$jqueryui_version";
if(!isset($phpc_fa_path))
	$phpc_fa_path = "//maxcdn.bootstrapcdn.com/font-awesome/$fa_version";
if(!isset($phpc_jq_file))
	$phpc_jq_file = "//ajax.googleapis.com/ajax/libs/jquery/$jquery_version/jquery$phpc_min.js";

/*
 * Do not modify anything under this point
 */
define('IN_PHPC', true);

require_once("$phpc_includes_path/calendar.php");
try {
	require_once("$phpc_includes_path/setup.php");
} catch(Exception $e) {
	header("Content-Type: text/html; charset=UTF-8");
	echo "<!DOCTYPE html>\n";
	echo display_exception($e)->toString();
	exit;
}

if ($vars["content"] == "json") {
	header("Content-Type: application/json; charset=UTF-8");
	echo do_action();
} else {
	header("Content-Type: text/html; charset=UTF-8");

	// This sets global variables that determine the title in the header
	$content = display_phpc();
	$embed_script = '';
	if($vars["content"] == "embed") {
		$underscore_version = "1.5.2";
		$embed_script = array(tag("script",
					attrs('src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/'
						."$underscore_version/underscore-min.js\""), ''),
				tag('script', attrs('src="static/embed.js"'), ''));
	}

	$html = tag('html', attrs("lang=\"$phpc_lang\""),
			tag('head',
				tag('title', $phpc_title),
				tag('link', attrs('rel="icon"',
						"href=\"$phpc_url_path/static/office-calendar.png\"")),
				tag('meta', attrs('http-equiv="Content-Type"',
						'content="text/html; charset=UTF-8"')),
				tag('link', attrs('rel="stylesheet"', "href=\"$phpc_static_path/phpc.css\"")),
				tag('link', attrs('rel="stylesheet"', "href=\"$phpc_jqui_path/themes/$phpc_theme/jquery-ui$phpc_min.css\"")),
				tag('link', attrs('rel="stylesheet"', "href=\"$phpc_static_path/jquery-ui-timepicker.css\"")),
				tag('link', attrs('rel="stylesheet"', "href=\"$phpc_fa_path/css/font-awesome$phpc_min.css\"")),
				tag("script", attrs("src=\"$phpc_jq_file\""), ''),
				tag("script", attrs("src=\"$phpc_jqui_path/jquery-ui$jq_min.js\""), ''),
				tag('script', attrs("src=\"$phpc_static_path/phpc.js\""), ''),
				tag("script", attrs("src=\"$phpc_static_path/jquery.ui.timepicker.js\""), ''),
				tag("script", attrs("src=\"$phpc_static_path/farbtastic.min.js\""), ''),
				tag('link', attrs('rel="stylesheet"', "href=\"$phpc_static_path/farbtastic.css\""))
			),
			tag('body', $embed_script, $content));

	echo "<!DOCTYPE html>\n", $html->toString();
}
?>
