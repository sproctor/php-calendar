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

/* this file is a generic form class
*/

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

require_once('calendar.php');

class Part {
        var $parent = NULL;

        function get_xhtml($defaults = array()) {
        }

        function get_results($vars) {
        }

        function set_parent(&$parent) {
                $this->parent = &$parent;
        }

        function get_level() {
                if($this->parent == NULL) soft_error(_('No parent'));
                return $this->parent->get_level();
        }
}

class Group extends Part {
        var $list = array();

        /* add a category or question */
        function add_part(&$item) {
                if(!is_a($item, 'Part')) soft_error();

                $this->list[] = &$item;
                $item->set_parent($this);
        }

        function get_xhtml($defaults = array()) {
                $arr = array();
                foreach($this->list as $item) {
                        $arr[] = $item->get_xhtml($defaults);
                }
                return $arr;
        }
}

class Category extends Group {
        var $name;

        function Category($name) {
                $this->name = $name;
        }

        function get_level() {
                return parent::get_level() + 1;
        }

        function get_xhtml($defaults = array()) {
                $tag = tag('div', attributes('class=phpc-form-category'),
                                tag('h' . $this->get_level(), $this->name));
                foreach($this->list as $child) {
                        $tag->add($child->get_xhtml());
                }
                return $tag;
        }
}

class Question extends Part {
}

class AtomicQuestion extends Question {
        var $qid;
        var $question;
}

class FreeQuestion extends AtomicQuestion {
        var $maxlen;

        function FreeQuestion($question, $qid, $maxlen = false) {
                $this->question = $question;
                $this->qid = $qid;
                $this->maxlen = $maxlen;
        }

        function get_xhtml($defaults = array()) {
                $attrs = attributes("name=\"$qid\"", "id=\"$qid\"",
                                'type=\"text\"');
                if(!empty($defaults[$this->qid])) {
                        $attrs->add("value=\"{$defaults[$this->qid]}\"");
                }
                if($this->maxlen !== false) {
                        $attrs->add("maxlength=\"{$this->maxlen}\"");
                        $attrs->add("size=\"{$this->maxlen}\"");
                }

                return array(tag('h' . ($this->get_level() + 1),
                                        $this->question),
                                tag('input', $attrs));
        }
}

class LongFreeQuestion extends AtomicQuestion {
        function LongFreeQuestion($question, $qid) {
                $this->question = $question;
                $this->qid = $qid;
        }

        function get_xhtml($defaults = array()) {
                return array(tag('h' . ($this->get_level() + 1),
                                        $this->question),
                                tag('div', attributes('class="phpc-form-long-free-question"'),
                                        tag('textarea', attributes('rows="8"',
                                                        "name=\"$qid}\""),
                                                '')));
        }
}

class CompoundQuestion extends Question {
        var $list;
        var $atomic_question;

        function CompoundQuestion(&$atomic_question, $list = array()) {
                $this->atomic_question = &$atomic_question;

                foreach($list as $key => $item) {
                        $this->add_conditional($key, $item);
                }
        }

        function add_conditional($key, &$item) {
                $list[$key] = &$item;
                $item->set_parent($this);
                $this->question->hook_conditional($key, $item);
        }
}

class RadioQuestion extends AtomicQuestion {

        function RadioQuestion($question, $options = array()) {
                $this->question = $question;
                foreach($options as $key => $value) {
                        $this->add_option($key, $value);
                }
        }

        function add_option($key, $value) {
        }
}

class Form extends Group {
        var $level;

        function Form($level = 1) {
                $this->level = $level;
        }

        function get_level() {
                return $this->level;
        }
}
