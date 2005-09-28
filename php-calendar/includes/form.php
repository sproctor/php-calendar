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
 * after an object is added to any part, you can modify that object without
 * affecting the form
 */

/*
if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}
*/

require_once('html.php');

$required_error_message = "(required)";

/* This class is the base of either a question or a group
 */
class Part {
        function get_xhtml($parent, $level, $defaults = array()) {
                return tag();
        }

        function get_results($vars) {
                return array();
        }

        function process($vars) {
                if($this->get_results($vars) === false) return false;
                else return true;
        }

        function mark_errors($vars) {
                return $this;
        }
}

/* this class is to group multiple questions together
 */
class Group extends Part {
        var $list = array();
        var $title = false;

        function Group($title = false) {
                $this->title = $title;
        }

        /* add a category or question */
        function add_part($item) {
                global $form_error_func;

                if(!is_a($item, 'Part')) html_error();

                $this->list[] = $item;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                $tag = tag('div', attributes('class=form-group'));
                if($this->title !== false) {
                        $tag->add(tag("h$level", $this->title));
                        $level++;
                }
                foreach($this->list as $child) {
                        $tag->add($child->get_xhtml($this, $level,
                                                $defaults));
                }
                return $tag;
        }

        function process($vars) {
                $result = true;
                foreach($this->list as $item) {
                        if(!$item->process($vars)) $result = false;
                }
                return $result;
        }

        function get_results($vars) {
                if(!$this->process($vars)) return false;

                $results = array();
                foreach($this->list as $item) {
                        $results = array_merge($results, $item->get_results());
                }
                return $results;
        }

        function mark_errors($vars) {
                $new_list = array();
                foreach($this->list as $item) {
                        $new_list[] = $item->mark_errors($vars);
                }
                $this->list = $new_list;
                return $this;
        }
}

/* this is the base class for all questions
 */
class Question extends Part {
}

/* this class is the base for all types of questions
 */
class AtomicQuestion extends Question {
        var $qid = false;
        var $question = false;
        var $description = false;
        var $required = false;
        var $error = false;

        function process($vars) {
                if(empty($vars[$this->qid])) {
                        if($this->required) {
                                return false;
                        }
                        else return true;
                }

                // TODO: add pattern checking here
                return true;
        }

        function get_results($vars) {
                if(!$this->process($vars)) return false;

                return array($this->qid => $vars[$this->qid]);
        }

        function mark_errors($vars) {
                global $required_error_message;

                if(!$this->process($vars)) {
                        $this->error = true;
                        $this->question .= " $required_error_message";
                        $this->question .= " Error!!";
                }

                return $this;
        }

}

/* This class is for free response questions with responses that are a few
 * sentences long at most
 */
class FreeQuestion extends AtomicQuestion {
        var $maxlen;

        function FreeQuestion($qid, $question, $description = false,
                        $maxlen = false, $required = false) {
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                $this->maxlen = $maxlen;
                $this->required = $required;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                $attrs = attributes("name=\"{$this->qid}\"",
                                "id=\"{$this->qid}\"", 'type=\"text\"');
                if(!empty($defaults[$this->qid])) {
                        $attrs->add("value=\"{$defaults[$this->qid]}\"");
                }
                if($this->maxlen !== false) {
                        $attrs->add("maxlength=\"{$this->maxlen}\"");
                        $attrs->add("size=\"{$this->maxlen}\"");
                }

                $tag = tag('');
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes('class="form-question-description"'), $this->description));
                }
                $tag->add(tag('input', $attrs));

                return $tag;
        }

}

/* this class is for longer free reponse questions
 */
class LongFreeQuestion extends AtomicQuestion {
	var $rows;

        function LongFreeQuestion($qid, $question, $description = false,
                        $rows = 8, $required = false) {
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
		$this->rows = $rows;
                $this->required = $required;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                $tag = tag('');
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes('class="form-question-description"'), $this->description));
                }
                $tag->add(tag('div', attributes('class="form-long-free-question"'),
                                        tag('textarea', attributes
                                                ("rows=\"{$this->rows}\"",
                                                 "name=\"{$this->qid}\""),
                                                '')));
                return $tag;
        }
}

/* creates a hidden input
 * FIXME: make this handle a default
 */
class HiddenField extends AtomicQuestion {
        var $value = false;

        function HiddenField($qid, $value = false) {
                $this->qid = $qid;
                $this->value = $value;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                return tag('input', attributes('type="hidden"',
                                        "name=\"{$this->qid}\"",
                                        "value=\"{$this->value}\""));
        }
}

/* creates a submit button
 */
class SubmitButton extends AtomicQuestion {
        var $title;

        function SubmitButton($title = false) {
                $this->title = $title;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                $attrs = attributes('type="submit"');
                if($this->title !== false) {
                        $attrs->add("value=\"{$this->title}\"");
                }
                return tag('div', attributes('class="form-submit"'),
                                tag('input', $attrs));
        }
}

/* this class is for questions where depending on the answer you need
 * to answer more questions
 */
class CompoundQuestion extends Question {
        var $atomic_question = NULL;
        var $conditionals = array();

        function CompoundQuestion($atomic_question, $conditionals = array()) {
                $this->atomic_question = $atomic_question;

                foreach($conditionals as $key => $item) {
                        $this->add_conditional($key, $item);
                }
        }

        function add_conditional($key, $item) {
                $this->conditionals[$key] = $item;
        }

        function get_conditional($key) {
                return $this->conditionals[$key];
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                return $this->atomic_question->get_xhtml($this, $level,
                                $defaults);
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class RadioQuestion extends AtomicQuestion {
        var $options = array();
        var $descriptions = array();

        function RadioQuestion($qid, $question = false, $options = array(),
                        $required = false) {
                $this->question = $question;
                foreach($options as $key => $name) {
                        $this->add_option($key, $name);
                }
                $this->required = $required;
        }

        function add_option($key, $title, $description = false) {
                $this->options[$key] = $title;
                $this->descriptions[$key] = $description;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                $results = tag('');
                if($this->question !== false) {
                        $results->add(tag("h$level", $this->question));
                        $level++;
                }
                foreach($this->options as $key => $name) {
                        $attrs = attributes('type=radio', 
                                        "name=\"{$this->qid}\"",
                                        "id={$this->qid}\"");
                        if($key !== NULL) {
                                $attrs->add("value=\"$key\"");
                        }
                        $tag = tag('div', attributes('class=form-radio-option'),
                                        tag('input', $attrs), $name);
                        if(!empty($this->descriptions[$key])) {
                                $tag->add("<span class=\"form-question-description\"> - {$this->descriptions[$key]}</span>");
                        }
                        $results->add($tag);

                        if(is_a($parent, 'CompoundQuestion')
                                && ($hook = $parent->get_conditional($key))) {
                                $results->add($hook->get_xhtml($this, $level,
                                                        $defaults));
                        }
                }
                return $results;
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class DropDownQuestion extends AtomicQuestion {
        var $options = array();

        function DropDownQuestion($qid, $question = false, $description = false,
                        $options = array(), $required = false) {
		$this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                foreach($options as $key => $name) {
                        $this->add_option($key, $name);
                }
                $this->required = $required;
        }

        /* add an option with value=$key, and text $name, and figure out
         * how to display a description
         * FIXME: implement the description
         */
        function add_option($key, $name, $description = false) {
                $this->options[$key] = $name;
        }

        function get_xhtml($parent, $level, $defaults = array()) {
                $results = array();
                if($this->question !== false) {
                        $results[] = tag("h$level", $this->question);
                }
                if($this->description !== false) {
                        $results[] = tag('div', attributes
                                        ('class="form-question-description"'),
                                        $this->description);
                }
                if(!empty($this->options)) {
                        $select = tag('select', attributes
                                        ("id=\"{$this->qid}\"",
                                         "name=\"{$this->qid}\""));
                        foreach($this->options as $key => $name) {
                                $select->add(tag('option', attributes
                                                        ('class="form-drop-down-option"',
                                                         "value=\"$key\""),
                                                        $name));
                                /* if(is_a($this->parent, 'CompoundQuestion')
                                   && ($hook = $this->parent->get_conditional(
                                   $key))) {
                                   $results[] = $hook->get_xhtml($defaults);
                                   } */
                        }
                        $results[] = $select;
                }
                return $results;
        }
}

/* this is the main form class
 */
class Form extends Group {
        var $level;
        var $vars;
        var $action;

        function Form($action, $title = false, $level = 1, $method = false) {
                parent::Group($title);
                $this->aciton = $action;
                $this->level = $level;
                $this->method = $method;
        }

        function get_xhtml($defaults = array()) {
                $attrs = attributes("action=\"{$this->action}\"");
                if($this->method !== false) {
                        $attrs->add("method=\"{$this->method}\"");
                }
                return tag('form', $attrs, parent::get_xhtml(NULL, $this->level,
                                        $defaults));
        }

        function process($vars) {
                $this->vars = $vars;
                return parent::process($vars);
        }

        function get_results($vars = false) {
                global $form_error_func;

                if($vars === false) {
                        if($this->vars === false) {
                                html_error('No vars');
                        }
                        $vars = $this->vars;
                }

                return parent::get_results($vars);
        }
}
?>
