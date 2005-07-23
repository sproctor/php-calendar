<?php
/*
   Copyright 2005 Sean Proctor

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

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

$HtmlInline = array('a', 'strong');

/*
 * data structure to display XHTML
 * see function tag() below for usage
 */
class Html {
        var $tagName;
        var $attributeList;
        var $childElements;

        function Html() {
                $args = func_get_args();
                return call_user_func_array(array(&$this, '__construct'),
                                $args);
        }

        function __construct() {
                $args = func_get_args();
                $this->tagName = array_shift($args);
                $this->attributeList = array();
                $this->childElements = array();

                $arg = array_shift($args);
                if($arg === NULL) return;

                if(is_a($arg, 'AttributeList')) {
                        $this->attributeList = $arg;
                        $arg = array_shift($args);
                }

                while($arg !== NULL) {
                        $this->add($arg);
                        $arg = array_shift($args);
                }
        }

        function add() {
                $htmlElements = func_get_args();
                foreach($htmlElements as $htmlElement) {
                        if(is_array($htmlElement)) {
                                foreach($htmlElement as $element) {
                                        $this->add($element);
                                }
                        } elseif(is_object($htmlElement)
                                        && !is_a($htmlElement, 'Html')) {
                                soft_error(_('Invalid class') . ': '
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
                                soft_error(_('Invalid class') . ': '
                                                . get_class($htmlElement));
                        } else {
                                array_unshift($this->childElements,
                                                $htmlElement);
                        }
                }
        }

        function toString() {
                global $HtmlInline;

                $output = "<{$this->tagName}";

                if($this->attributeList != NULL) {
                        $output .= ' ' . $this->attributeList->toString();
                }

                if($this->childElements == NULL) {
                        $output .= " />\n";
                        return $output;
                }

                $output .= ">";

                foreach($this->childElements as $child) {
                        if(is_object($child)) {
                                if(is_a($child, 'Html')) {
                                        $output .= $child->toString();
                                } else {
                                        soft_error(_('Invalid class') . ': '
                                                        . get_class($child));
                                }
                        } else {
                                $output .= $child;
                        }
                }

                $output .= "</{$this->tagName}>";

                if(!in_array($this->tagName, $HtmlInline)) {
                        $output .= "\n";
                }
                return $output;
        }
}

/*
 * Data structure to display XML style attributes
 * see function attributes() below for usage
 */
class AttributeList {
        var $list;

        function AttributeList() {
                $args = func_get_args();
                return call_user_func_array(array(&$this, '__construct'),
                                $args);
        }

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

?>
