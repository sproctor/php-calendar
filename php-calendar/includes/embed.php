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
       die("Invalid setup");
}

try {
	require_once("$phpc_includes_path/calendar.php");
	require_once("$phpc_includes_path/setup.php");

	$calendar_title = $phpc_cal->get_title();
	$content = tag('div', attributes('class="php-calendar ui-widget"'),
			userMenu(),
			tag('br', attributes('style="clear:both;"')),
			tag('h1', attrs('class="ui-widget-header"'),
				tag('a', attributes("href='$phpc_home_url?phpcid={$phpc_cal->get_cid()}'"),
					$calendar_title)),
			display_phpc());
} catch(Exception $e) {
	$calendar_title = $e->getMessage();
	$content = tag('div', attributes('class="php-calendar"'),
			$e->getMessage());
}
$head = tag('div', attrs('class="phpc-head"'),
			get_header_tags("static"));
echo $head->toString();
echo $content->toString();
?>
