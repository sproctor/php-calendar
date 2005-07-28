<?php
/*
   Copyright 2002 - 2005 Sean Proctor, Nathan Poiro

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/* this file contains global functions for use in the calendar */

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

require_once($phpc_root_path . 'includes/globals.php');

// make sure that we have _ defined
if(!function_exists('_')) {
	function _($str) { return $str; }
	$no_gettext = 1;
}

// called when some error happens
function soft_error($str)
{
	echo '<html><head><title>'._('Error')."</title></head>\n"
		.'<body><h1>'._('Software Error')."</h1>\n"
                ."<h2>"._('Message:')."</h2>\n"
		."<pre>$str</pre>\n";
        if(version_compare(phpversion(), '4.3.0', '>=')) {
                echo "<h2>"._('Backtrace')."</h2>\n";
                echo "<ol>\n";
                foreach(debug_backtrace() as $bt) {
                        echo "<li>$bt[file]:$bt[line] - $bt[function]</li>\n";
                }
                echo "</ol>\n";
        }
        echo "</body></html>\n";
	exit;
}

// takes a number of the month, returns the name
function month_name($month)
{
        global $month_names;

	$month = ($month - 1) % 12 + 1;

        return $month_names[$month];
}

//takes a day number of the week, returns a name
function day_name($day)
{
	global $config, $day_names;

	if($config['start_monday']) {
		$day = $day + 1;
	}

	$day = $day % 7;

        return $day_names[$day];
}

function short_month_name($month)
{
        global $short_month_names;

	$month = ($month - 1) % 12 + 1;
        return $short_month_names[$month];
}

        // takes a time string, and formats it according to type
        // returns the formatted string
function formatted_time_string($time, $type)
{
	global $config;

	switch($type) {
		default:
			preg_match('/(\d+):(\d+)/', $time, $matches);
			$hour = $matches[1];
			$minute = $matches[2];

			if(!$config['hours_24']) {
				if($hour >= 12) {
                                        if($hour != 12) {
                                                $hour -= 12;
                                        }
					$pm = ' PM';
                                } else {
                                        if($hour == 0) {
                                                $hour = 12;
                                        }
					$pm = ' AM';
				}
			} else {
				$pm = '';
			}

			return sprintf('%d:%02d%s', $hour, $minute, $pm);
		case 2:
			return _('FULL DAY');
		case 3:
			return _('TBA');
                case 4:
                        return '';
	}
}

// parses a description and adds the appropriate mark-up
function parse_desc($text)
{

	// get out the crap, put in breaks
	$text = nl2br($text);

	//urls
	$text = preg_replace("/([[:alpha:]]+:\\/\\/[^<>\s]+[\\w\\/])/i",
			"<a href=\"$1\">$1</a>", $text);


	// emails
	$text = preg_replace("/([a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*"
			."[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z])/",
			"<a href=\"mailto:$1\">$1</a>", $text );

	return $text;
}

// returns the day of week number corresponding to 1st of $month
function day_of_first($month, $year)
{
	global $config;

	if(!$config['start_monday'])
		return date('w', mktime(0, 0, 0, $month, 1, $year));
	else
		return (date('w', mktime(0, 0, 0, $month, 1, $year)) + 6) % 7;
}

// returns the number of days in $month
function days_in_month($month, $year)
{
	return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year)
{
	return ceil((day_of_first($month, $year)
				+ days_in_month($month, $year)) / 7);
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

function minute_pad($minute)
{
        return sprintf('%02d', $minute);
}

function get_day_of_month_sequence($month, $year)
{
        $end = date('t', mktime(0, 0, 0, $month, 1, $year, 0));
        return create_sequence(0, $end);
}

// creates an input of the specified type for a form
// returns XHTML data for the input
function create_input($name = false, $value = false, $type = 'text',
                $extra_attrs = false) {
        $attributes = attributes("type=\"$type\"");
        if($name !== false) {
                $attributes->add("name=\"$name\"");
        }
        if($value !== false) {
                $attributes->add("value=\"$value\"");
        }
        if($extra_attrs !== false) {
                $attributes->add($extra_attrs);
        }
        return tag('input', $attributes);
}

// creates a hidden input for a form
// returns XHTML data for the input
function create_hidden($name, $value, $extra_attrs = false) {
        return create_input($name, $value, 'hidden', $extra_attrs);
}

// creates a submit button for a form
// return XHTML data for the button
function create_submit($value, $extra_attrs = false) {
        return create_input(false, $value, 'submit', $extra_attrs);
}

// creates a password entry for a form
// returns XHTML data for the entry
function create_password($name, $value = false, $extra_attrs = false) {
        return create_input($name, $value, 'password', $extra_attrs);
}

// creates a checkbox for a form
// returns XHTML data for the checkbox
function create_checkbox($name, $value = false, $checked = false,
                $extra_attrs = false) {
        if(!empty($checked)) {
                $attributes = attributes('checked="checked"');
                $attributes->add($extra_attrs);
        } else {
                $attributes = $extra_attrs;
        }
        return create_input($name, $value, 'checkbox', $attributes);
}

// creates a radio button for a form
// returns XHTML data for the checkbox
function create_radio($name, $value = false, $checked = false,
                $extra_attrs = false) {
        if(!empty($checked)) {
                $attributes = attributes('checked="checked"');
                $attributes->add($extra_attrs);
        } else {
                $attributes = $extra_attrs;
        }
        return create_input($name, $value, 'radio', $attributes);
}

// creates a select element for a form of pre-defined $type
// returns XHTML data for the element
function create_select($name, $type, $select, $attributes = false)
{
        if(!$attributes) $attributes = attributes();

        $attributes->add('size="1"', "name=\"$name\"");
        $html = tag('select', $attributes);

        foreach($type as $value => $text) {
                $option_attributes = attributes("value=\"$value\"");
                if ($select == $value) {
                        $option_attributes->add('selected="selected"');
                }
                $html->add(tag('option', $option_attributes, $text));
        }

        return $html;
}

?>
