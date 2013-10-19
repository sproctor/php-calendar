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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

/*
 * data structure to display XHTML
 * see function tag() below for usage
 * Cateat: this class does not understand HTML, it just approximates it.
 *	do not give an empty tag that needs to be closed without content, it
 *	won't be closed. ex tag('div') to get a closed tag use tag('div', '')
 */
class Html {
        var $tagName;
        var $attributeList;
        var $childElements;
	var $error_func;

        function __construct() {
		$this->error_func = array(&$this, 'default_error_handler');
                $args = func_get_args();
                $this->tagName = array_shift($args);
                if($this->tagName === NULL) $this->tagName = '';
                $this->attributeList = array();
                $this->childElements = array();

                $arg = array_shift($args);
                if($arg === NULL) return;

                while($arg !== NULL) {
                        $this->add($arg);
                        $arg = array_shift($args);
                }
        }

        function add() {
                $htmlElements = func_get_args();
                foreach($htmlElements as $htmlElement) {
			if(is_a($htmlElement, 'AttributeList')) {
				$this->attributeList = $htmlElement;
			} elseif(is_array($htmlElement)) {
                                foreach($htmlElement as $element) {
                                        $this->add($element);
                                }
                        } elseif(is_object($htmlElement)
                                        && !is_a($htmlElement, 'Html')) {
                                $this->html_error('Invalid class: '
                                                . get_class($htmlElement));
                        } else {
                                $this->childElements[] = $htmlElement;
                        }
                }
        }

        function prepend() {
                $htmlElements = func_get_args();
                foreach(array_reverse($htmlElements) as $htmlElement) {
                        if(is_array($htmlElement)) {
                                foreach(array_reverse($htmlElement)
                                                as $element) {
                                        $this->prepend($element);
                                }
                        } elseif(is_object($htmlElement)
                                        && !is_a($htmlElement, 'Html')) {
                                $this->html_error('Invalid class: '
                                                . get_class($htmlElement));
                        } else {
                                array_unshift($this->childElements,
                                                $htmlElement);
                        }
                }
        }

        function toString() {
		$output = '';

		if($this->tagName != '') {
			$output .= "<{$this->tagName}";

			if($this->attributeList != NULL) {
				$output .= ' '
					. $this->attributeList->toString();
			}

			if(sizeof($this->childElements) == 0) {
				$output .= "/>\n";
				return $output;
			}

			$output .= ">";
		}

                foreach($this->childElements as $child) {
                        if(is_object($child)) {
                                if(is_a($child, 'Html')) {
                                        $output .= $child->toString();
                                } else {
                                        $this->html_error('Invalid class: '
                                                        . get_class($child));
                                }
                        } else {
                                $output .= $child;
                        }
                }

		if($this->tagName != '') {
			$output .= "</{$this->tagName}>\n";
		}

                return $output;
        }

	function default_error_handler($str) {
		echo "<html><head><title>Error</title></head>\n"
			."<body><h1>Software Error</h1>\n"
			."<h2>Message:</h2>\n"
			."<pre>$str</pre>\n";
		echo "<h2>Backtrace</h2>\n";
		echo "<ol>\n";
		foreach(debug_backtrace() as $bt) {
			echo "<li>{$bt['file']}:{$bt['line']} - ";
			if(!empty($bt['class']))
				echo "{$bt['class']}{$bt['type']}";
			echo "{$bt['function']}(" . implode(', ',
						$bt['args'])
				. ")</li>\n";
		}
		echo "</ol>\n";
		echo "</body></html>\n";
		exit;
	}

	/* call this function if you want a non-default error handler */
	function html_set_error_handler($func) {
		$this->error_func = $func;
	}

	function html_error() {
		$args = func_get_args();
		return call_user_func_array($this->error_func, $args);
	}
}

/*
 * Data structure to display XML style attributes
 * see function attributes() below for usage
 */
class AttributeList {
        var $list;

        function __construct() {
                $this->list = array();
                $args = func_get_args();
                $this->add($args);
        }

        function add() {
                $args = func_get_args();
                foreach($args as $arg) {
                        if(is_array($arg)) {
                                foreach($arg as $attr) {
                                        $this->add($attr);
                                }
                        } else {
                                $this->list[] = $arg;
                        }
                }
        }

        function toString() {
                return implode(' ', $this->list);
        }
}

/*
 * creates an Html data structure
 * arguments are tagName [AttributeList] [Html | array | string] ...
 * where array contains an array, Html, or a string, same requirements for that
 * array
 */
function tag()
{
        $args = func_get_args();
        $html = new Html();
        call_user_func_array(array(&$html, '__construct'), $args);
        return $html;
}

/*
 * creates an AttributeList data structure
 * arguments are [attribute | array] ...
 * where attribute is a string of name="value" and array contains arrays or
 * attributes
 */
function attributes()
{
        $args = func_get_args();
        $attrs = new AttributeList();
        call_user_func_array(array(&$attrs, '__construct'), $args);
        return $attrs;
}

function attrs()
{
        $args = func_get_args();
        $attrs = new AttributeList();
        call_user_func_array(array(&$attrs, '__construct'), $args);
        return $attrs;
}


// creates a select tag element for a form
// returns HTML data for the element
function create_select($name, $options, $default = false, $attrs = false)
{
	if($attrs === false)
		$attrs = attrs();

	$attrs->add("name=\"$name\"");
	$attrs->add("id=\"$name\"");
	$select = tag('select', $attrs);

	foreach($options as $value => $text) {
		$attributes = attrs("value=\"$value\"");
		if($default !== false && ($value == $default ||
					is_array($default) &&
					in_array($value, $default))) {
			$attributes->add('selected');
		}
		$select->add(tag('option', $attributes, $text));
	}

	return $select;
}

// creates a two stage select input
// returns HTML data for the elements
function create_multi_select($name, $option_lists, $default = false,
		$attrs = false)
{
	if($attrs === false)
		$attrs = attrs();

	$attrs->add("name=\"$name\"");
	$attrs->add("id=\"$name\"");
	$attrs->add("class=\"phpc-multi-select\"");
	$select = tag('select', $attrs);

	foreach($option_lists as $category => $options) { 
		if(is_array($options)) { 
			$group = tag('optgroup', attrs("label=\"$category\""));
			$select->add($group);
			foreach($options as $value => $text) {
				$attributes = attrs("value=\"$value\"");
				if($value === $default)
					$attributes->add('selected');
				$text = str_replace('_', ' ', $text);
				$group->add(tag('option', $attributes, $text));
			}
		} else {
			$value = $options;
			$text = $category;
			$attributes = attrs("value=\"$value\"");
			if($value === $default)
				$attributes->add('selected');
			$select->add(tag('option', $attributes, $text));
		}
	}

	return $select;
}

// creates a select element for a form given a certain range
// returns HTML data for the element
function create_select_range($name, $lbound, $ubound, $increment = 1,
		$default = false, $name_function = false, $attrs = false)
{
	$options = array();

	for ($i = $lbound; $i <= $ubound; $i += $increment){
		if($name_function !== false) {
			$text = $name_function($i);
		} else {
			$text = $i;
		}
		$options[$i] = $text;
	}
	return create_select($name, $options, $default, $attrs);
}
?>
