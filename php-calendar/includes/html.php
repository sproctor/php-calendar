<?php
/*
   Copyright 2002 Sean Proctor

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

function is_attribute_list($var)
{
	return (!empty($var) && is_array($var) && isset($var[0])
			&& $var[0] == 'is_attribute_list');
}

function attribute_list_to_string($attrs)
{
	$output = '';
	reset($attrs);
	if(next($attrs) == false) return $output;
	while(list(,$value) = each($attrs)) {
		$output .= ' ' . $value;
	}

	return $output;
}

function is_tagname($var)
{
	return (!empty($var) && is_array($var) && isset($var[0])
			&& $var[0] == 'is_tagname');
}

function get_tag_name($html)
{
	return $html[1];
}

function remove_tag(&$html)
{
	foreach($html as $key => $val) {
		if(is_tagname($val)) {
			array_splice($html, $key, 1);
			return get_tag_name($val);
		}
	}

echo "<pre>ack\n";
var_dump($html);
echo "</pre>";
die;

	return NULL;
}

function remove_attributes(&$html)
{
	foreach($html as $key => $val) {
		if(is_attribute_list($val)) {
			array_splice($html, $key, 1);
			return $val;
		}
	}

	return NULL;
}

$new_line = true;

function html_to_string($html)
{
	global $new_line;

	$inline = array('a', 'strong');

	$tag_name = remove_tag($html);
	$attributes = remove_attributes($html);
	/*
echo '<pre>';
var_dump($html);
echo '</pre>';
die;
*/

	if(empty($tag_name)) {
		echo '<pre>';
		var_dump($html);
		echo '</pre>';
		soft_error('No tag name given');
	}

	if($new_line || in_array($tag_name, $inline)) {
		$new_line = false;
		$output = '';
	} else $output = "\n";

	$output .= "<$tag_name";

	if(isset($attributes)) {
		$output .= attribute_list_to_string($attributes);
	}

	if(sizeof($html) == 0) {
		$output .= " />\n";
		$new_line = true;
		return $output;
	}

	$output .= ">";
	//if(!in_array($tag_name, $inline)) $output .= "\n";

	foreach($html as $val) {
		if(is_array($val)) {
			$output .= html_to_string($val);
		} else {
			$output .= $val;
		}
	}

	$output .= "</$tag_name>";
	if(!in_array($tag_name, $inline)) {
		$output .= "\n";
		$new_line = true;
	}
	return $output;
}

function tag()
{
	$args = func_get_args();
	$tag_name = array_shift($args);
	return array_cons(array('is_tagname', $tag_name), $args);
}

function attributes()
{
	$attrs = func_get_args();
	array_unshift($attrs, 'is_attribute_list');

	return $attrs;
}

function array_cons($x, $xs)
{
        array_unshift($xs, $x);
	return $xs;
}

function array_head($array)
{
	return $array[0];
}

function array_tail($array)
{
	array_shift($array);
	return $array;
}

function array_append($xs, $x)
{
	$xs[] = $x;
	return $xs;
}

?>
