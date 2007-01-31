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
class FormPart {
	var $class = "form-part";

        function get_html($parent, $level, $defaults = array()) {
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

	function error($str) {
		echo "<html><head><title>Error</title></head>\n"
			."<body><h1>Software Error</h1>\n"
			."<h2>Message:</h2>\n"
			."<pre>$str</pre>\n";
		if(version_compare(phpversion(), '4.3.0', '>=')) {
			echo "<h2>Backtrace</h2>\n";
			echo "<ol>\n";
			foreach(debug_backtrace() as $bt) {
				echo "<li>{$bt['file']}:{$bt['line']} - ";
				if(!empty($bt['class']))
					echo "{$bt['class']}{$bt['type']}";
				echo "{$bt['function']}(";
				if(!empty($bt['args'])) {
					echo implode(', ', $bt['args']);
				} else {
					echo "<em>&lt;unknown&gt;</em>";
				}
				echo ")</li>\n";
			}
			echo "</ol>\n";
		}
		echo "</body></html>\n";
		exit;
	}
}

/* this class is to group multiple questions together
 */
class FormGroup extends FormPart {
        var $list = array();
        var $title = false;

        function FormGroup($title = false) {
                $this->title = $title;
		$this->class .= " form-group";
        }

        /* add a category or question */
        function add_part($item) {
                global $form_error_func;

                if(!is_a($item, 'FormPart')) $this->error(
				_('Cannot add a non-form element to a form.'));

                $this->list[] = $item;
        }

        function get_html($parent, $level, $defaults = array()) {
                $tag = tag('div', attributes("class=\"{$this->class}\""));
                if($this->title !== false) {
                        $tag->add(tag("h$level", $this->title));
                        $level++;
                }
                foreach($this->list as $child) {
                        $tag->add($child->get_html($this, $level,
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
class FormQuestion extends FormPart {
	function FormQuestion() {
		$this->class .= " form-question";
	}
}

/* this class is the base for all types of questions
 */
class FormAtomicQuestion extends FormQuestion {
        var $qid = false;
        var $question = false;
        var $description = false;
        var $required = false;
        var $error = false;

	function FormAtomicQuestion() {
		parent::FormQuestion();
		$this->class .= " form-atomic-question";
	}

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
class FormFreeQuestion extends FormAtomicQuestion {
        var $maxlen;

        function FormFreeQuestion($qid, $question, $description = false,
                        $maxlen = false, $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                $this->maxlen = $maxlen;
                $this->required = $required;
		$this->class .= " form-free-question";
        }

        function get_html($parent, $level, $defaults = array()) {
                $attrs = attributes("name=\"{$this->qid}\"",
                                "id=\"{$this->qid}\"", 'type="text"');
                if(!empty($defaults[$this->qid])) {
                        $attrs->add("value=\"{$defaults[$this->qid]}\"");
                }
                if($this->maxlen !== false) {
                        $attrs->add("maxlength=\"{$this->maxlen}\"");
                        $attrs->add("size=\"{$this->maxlen}\"");
                }

                $tag = tag('div', attributes("class=\"{$this->class}\""));
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes("class=\"form-question-description\""), $this->description));
                }
                $tag->add(tag('input', $attrs));

                return $tag;
        }

}

/* this class is for longer free reponse questions
 */
class FormLongFreeQuestion extends FormAtomicQuestion {
	var $rows;

        function FormLongFreeQuestion($qid, $question, $description = false,
                        $rows = 8, $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
		$this->rows = $rows;
                $this->required = $required;
		$this->class .= " form-long-free-question";
        }

        function get_html($parent, $level, $defaults = array()) {
                $tag = tag('div', attributes("class=\"{$this->class}\""));
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes('class="form-question-description"'), $this->description));
                }
		$tag->add(tag('textarea', attributes("rows=\"{$this->rows}\"",
						"name=\"{$this->qid}\""), ''));
                return $tag;
        }
}

/* this class is for date input
 */
class FormDateQuestion extends FormAtomicQuestion {

        function FormDateQuestion($qid, $question, $description = false,
                        $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-date-question";
        }

        function get_html($parent, $level, $defaults = array()) {
                $tag = tag();
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes('class="form-question-description"'), $this->description));
                }
		if(!empty($defaults["{$this->qid}-year"])) {
			$year = $defaults["{$this->qid}-year"];
		} else {
			$year = date("Y");
		}
		$year_input = create_select_range("{$this->qid}-year", 1970,
				$year + 20, 1, $year);
		if(!empty($defaults["{$this->qid}-month"])) {
			$month = $defaults["{$this->qid}-month"];
		} else {
			$month = date("m");
		}
		$month_input = create_select_range("{$this->qid}-month", 1, 12,
				1, $month, "month_name");
		if(!empty($defaults["{$this->qid}-day"])) {
			$day = $defaults["{$this->qid}-day"];
		} else {
			$day = date("d");
		}
		$day_input = create_select_range("{$this->qid}-day", 1, 31, 1,
				$day);
                $tag->add(tag('div', attributes("class=\"{$this->class}\""),
					$year_input, $month_input, $day_input));
                return $tag;
        }
}

/* this class is for time input
 */
class FormTimeQuestion extends FormAtomicQuestion {

        function FormTimeQuestion($qid, $question, $description = false,
                        $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-time-question";
        }

        function get_html($parent, $level, $defaults = array()) {
                $tag = tag();
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes('class="form-question-description"'), $this->description));
                }
		if(!empty($defaults["{$this->qid}-hour"])) {
			$hour = $defaults["{$this->qid}-hour"];
		} else {
			$hour = date("g");
			//24 $hour = date("G");
		}
		$hour_input = create_select_range("{$this->qid}-hour", 1, 24, 1,
				$hour);
		if(!empty($defaults["{$this->qid}-minute"])) {
			$minute = $defaults["{$this->qid}-minute"];
		} else {
			$minute = date("i");
		}
		$minute_input = create_select_range("{$this->qid}-minute", "00",
				59, 1, $minute);
		if(!empty($defaults["{$this->qid}-meridiem"])) {
			$meridiem = $defaults["{$this->qid}-meridiem"];
		} else {
			$meridiem = date("a");
		}
		$meridiem_input = create_select("{$this->qid}-meridiem",
				array("am" => _("AM"), "pm" => _("PM")),
				$meridiem);
                $tag->add(tag('div', attributes("class=\"{$this->class}\""),
					$hour_input, $minute_input,
					$meridiem_input));
                return $tag;
        }
}

/* this class is for a sequence question
 */
class FormSequenceQuestion extends FormAtomicQuestion {
	var $lbound;
	var $ubound;
	var $increment;
	var $default;
	var $name_func;

        function FormSequenceQuestion($qid, $question, $description = false,
                        $lbound, $ubound, $increment, $default = false,
			$name_func = false, $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                $this->required = $required;
		$this->lbound = $lbound;
		$this->ubound = $ubound;
		$this->increment = $increment;
		$this->default = $default;
		$this->name_func = $name_func;
		$this->class .= " form-date-question";
        }

        function get_html($parent, $level, $defaults = array()) {
                $tag = tag();
                $tag->add(tag("h$level", $this->question));
                if($this->description !== false) {
                        $tag->add(tag('div', attributes('class="form-question-description"'), $this->description));
                }
		$input = create_select_range($this->qid, $this->lbound,
				$this->ubound, $this->increment, $this->default,
				$this->name_func);
                $tag->add(tag('div', attributes("class=\"{$this->class}\""),
					$day));
                return $tag;
        }
}

/* creates a hidden input
 * FIXME: make this handle a default
 */
class FormHiddenField extends FormAtomicQuestion {
        var $value = false;

        function FormHiddenField($qid, $value = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->value = $value;
        }

        function get_html($parent, $level, $defaults = array()) {
                return tag('input', attributes('type="hidden"',
                                        "name=\"{$this->qid}\"",
                                        "value=\"{$this->value}\""));
        }
}

/* creates a submit button
 */
class FormSubmitButton extends FormAtomicQuestion {
        var $title;

        function FormSubmitButton($title = false) {
		parent::FormAtomicQuestion();
                $this->title = $title;
		$this->class .= " form-submit";
        }

        function get_html($parent, $level, $defaults = array()) {
                $attrs = attributes('type="submit"');
                if($this->title !== false) {
                        $attrs->add("value=\"{$this->title}\"");
                }
                return tag('div', attributes("class=\"{$this->class}\""),
                                tag('input', $attrs));
        }
}

/* this class is for questions where depending on the answer you need
 * to answer more questions
 */
class FormCompoundQuestion extends FormQuestion {
        var $atomic_question = NULL;
        var $conditionals = array();

        function FormCompoundQuestion($atomic_question,
			$conditionals = array()) {
		parent::FormQuestion();
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

        function get_html($parent, $level, $defaults = array()) {
                return $this->atomic_question->get_html($this, $level,
                                $defaults);
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class FormRadioQuestion extends FormAtomicQuestion {
        var $options = array();
        var $descriptions = array();

        function FormRadioQuestion($qid, $question = false, $options = array(),
                        $required = false) {
		parent::FormAtomicQuestion();
		$this->qid = $qid;
                $this->question = $question;
                foreach($options as $key => $name) {
                        $this->add_option($key, $name);
                }
                $this->required = $required;
		$this->class .= " form-radio-question";
        }

        function add_option($key, $title, $description = false) {
                $this->options[$key] = $title;
                $this->descriptions[$key] = $description;
        }

        function get_html($parent, $level, $defaults = array()) {
		$results = tag('div', attributes("class=\"{$this->class}\""));
                if($this->question !== false) {
                        $results->add(tag("h$level", $this->question));
                        $level++;
                }
                foreach($this->options as $key => $name) {
                        $attrs = attributes('type="radio"', 
                                        "name=\"{$this->qid}\"",
                                        "id=\"{$this->qid}\"");
                        if($key !== NULL) {
                                $attrs->add("value=\"$key\"");
                        }
			$tag = tag('div', tag('input', $attrs), $name);
                        if(!empty($this->descriptions[$key])) {
                                $tag->add("<span class=\"form-question-description\"> - {$this->descriptions[$key]}</span>");
                        }

                        if(is_a($parent, 'FormCompoundQuestion')
                                && ($hook = $parent->get_conditional($key))) {
				$tag = tag('div', attributes('class="form-compound-question"'),
						$tag, $hook->get_html($this,
							$level, $defaults));
                        }

                        $results->add($tag);
                }
                return $results;
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class FormDropDownQuestion extends FormAtomicQuestion {
        var $options = array();

        function FormDropDownQuestion($qid, $question = false,
			$description = false, $options = array(),
			$required = false) {
		parent::FormAtomicQuestion();
		$this->qid = $qid;
                $this->question = $question;
                $this->description = $description;
                foreach($options as $key => $name) {
                        $this->add_option($key, $name);
                }
                $this->required = $required;
		$this->class .= " form-atomic-question";
        }

        /* add an option with value=$key, and text $name, and figure out
         * how to display a description
         * FIXME: implement the description
         */
        function add_option($key, $name, $description = false) {
                $this->options[$key] = $name;
        }

        function get_html($parent, $level, $defaults = array()) {
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
							("class=\"{$this->class}\"",
                                                         "value=\"$key\""),
                                                        $name));
                                /* if(is_a($this->parent, 'FormCompoundQuestion')
                                   && ($hook = $this->parent->get_conditional(
                                   $key))) {
                                   $results[] = $hook->get_html($defaults);
                                   } */
                        }
                        $results[] = $select;
                }
                return $results;
        }
}

/* this is the main form class
 */
class Form extends FormGroup {
        var $level;
        var $vars;
        var $action;

        function Form($action, $title = false, $level = 1, $method = false) {
                parent::FormGroup($title);
                $this->action = $action;
                $this->level = $level;
                $this->method = $method;
		$this->class .= " form";
        }

        function get_html($defaults = array()) {
                $attrs = attributes("action=\"{$this->action}\"");
                if($this->method !== false) {
                        $attrs->add("method=\"{$this->method}\"");
                }
                return tag('form', $attrs, parent::get_html(NULL, $this->level,
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
                                $this->error('No vars');
                        }
                        $vars = $this->vars;
                }

                return parent::get_results($vars);
        }
}
?>
