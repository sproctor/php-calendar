<?php
/*
 * Copyright 2009 Sean Proctor
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
   This file sets up the basics of the calendar. It can be copied to produce
   a new calendar using the same configuration and database.
*/

/*
   copy index.php to a new location and increment $calendar_id when you create
   another calendar so that the calendars will not all share the same data
*/
$default_calendar_id = 1;

$phpc_root_path = dirname($_SERVER["DOCUMENT_ROOT"] . $_SERVER["PHP_SELF"]);
$phpc_includes_path = "$phpc_root_path/includes";

/*
 * Do not modify anything under this point
 */

require_once("$phpc_includes_path/setup.php");

$calendar_title = get_config($phpcid, 'calendar_title');

$html = tag('html', attributes("lang=\"$lang\""),
		tag('head',
			tag('title', $calendar_title),
			tag('link',
				attributes('rel="stylesheet" type="text/css"'
					." href=\"static/style.css\"")
			   )),
		tag('body',
			tag('div', attributes('class="php-calendar"'),
				tag('h1', $calendar_title),
				navbar(),
				do_action(),
				link_bar())));

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">', "\n", $html->toString();
?>
