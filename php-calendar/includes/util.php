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
