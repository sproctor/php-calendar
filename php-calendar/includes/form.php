<?php
/*
 * Copyright 2013 Sean Proctor
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

/* this file is a generic form class
 * after an object is added to any part, you can modify that object without
 * affecting the form
 * Requires gettext and jqueryui
 */

/*
if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}
*/

require_once('html.php');

$required_error_message = '('.__('required').')';

/* This class is the base of either a question or a group
 */
abstract class FormPart {
	var $class = "form-part";

	abstract function get_html($parent, $defaults = array());
	abstract protected function get_specific_html($parent, $defaults);

	function get_results($vars) {
		return array();
	}

        function process($vars) {
                if($this->get_results($vars) === false)
			return false;
                else
			return true;
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

        function __construct($title = false, $class = false) {
                $this->title = $title;
		$this->class .= " form-group";
		if($class !== false)
			$this->class = " $class";
        }

        /* add a category or question */
        function add_part($item) {
                global $form_error_func;

                if(!is_a($item, 'FormPart')) $this->error(
				__('Cannot add a non-form element to a form.'));

                $this->list[] = $item;
        }

        function get_html($parent, $defaults = array()) {
		// If a FormGroup is embedded in another FormGroup we want to
		//   start a new table. If it isn't, we assume that our parent
		//   (radio or dropdown) has already created the table for us.
		if(!is_a($parent, 'FormGroup'))
			return $this->get_specific_html($parent, $defaults);

                $tag = tag('tr', attrs("class=\"{$this->class}\""));
		$cell = tag('td');

                if($this->title !== false)
			$tag->add(tag('th', $this->title));
                else
			$cell->add(attrs("colspan=\"2\""));

                $cell->add(tag('table', $this->get_specific_html($parent,
						$defaults)));
		$tag->add($cell);

                return $tag;
        }

        protected function get_specific_html($parent, $defaults) {
		$results = array();
                foreach($this->list as $child) {
                        $results[] = $child->get_html($this, $defaults);
                }
		return $results;
        }

        function process($vars) {
                $result = true;
                foreach($this->list as $item) {
                        if(!$item->process($vars))
				$result = false;
                }
                return $result;
        }

        function get_results($vars) {
                if(!$this->process($vars))
			 return false;

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
abstract class FormQuestion extends FormPart {
        var $qid = false;
        var $required = false;
        var $error = false;
        var $subject = false;
        var $description = false;

	function __construct() {
		$this->class .= " form-question";
	}

        function get_html($parent, $defaults = array()) {
                $tag = tag('tr', attrs("class=\"{$this->class}\""));
		$cell = tag('td');

                if($this->subject !== false)
			$tag->add(tag('th', $this->subject));
                else
			$cell->add(attrs("colspan=\"2\""));

                if($this->description !== false)
                        $cell->add(tag('p', attrs('class="form-question-description"'),
						$this->description));

                $cell->add($this->get_specific_html($parent, $defaults));
		$tag->add($cell);

                return $tag;
        }
}

/* this class is the base for all simple types of questions
 */
abstract class FormAtomicQuestion extends FormQuestion {

	function __construct() {
		parent::__construct();
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
                if(!$this->process($vars))
			return false;

                return array($this->qid => $vars[$this->qid]);
        }

        function mark_errors($vars) {
                global $required_error_message;

                if(!$this->process($vars)) {
                        $this->error = true;
                        $this->subject .= " $required_error_message";
                        $this->subject .= " Error!!";
                }

                return $this;
        }
}

/* This class is for free response questions with responses that are a few
 * sentences long at most
 */
class FormFreeQuestion extends FormAtomicQuestion {
        var $maxlen;
	var $type;

        function __construct($qid, $subject, $description = false,
                        $maxlen = false, $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->maxlen = $maxlen;
                $this->required = $required;
		$this->class .= " form-free-question";
		$this->type = "text";
        }

        protected function get_specific_html($parent, $defaults) {
                $attrs = attrs("name=\"{$this->qid}\"", "id=\"{$this->qid}\"",
				"type=\"$this->type\"");
                if(!empty($defaults[$this->qid])) {
                        $attrs->add("value=\"{$defaults[$this->qid]}\"");
                }
                if($this->maxlen !== false) {
                        $attrs->add("maxlength=\"{$this->maxlen}\"");
			$size = min(50, $this->maxlen);
                        $attrs->add("size=\"$size\"");
                }

                return tag('input', $attrs);
        }

}

/* this class is for longer free reponse questions
 */
class FormLongFreeQuestion extends FormAtomicQuestion {
	var $rows;
	var $cols;

        function __construct($qid, $subject, $description = false,
                        $rows = 8, $cols = 80, $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
		$this->rows = $rows;
		$this->cols = $cols;
                $this->required = $required;
		$this->class .= " form-long-free-question";
        }

        protected function get_specific_html($parent, $defaults) {
		if(isset($defaults[$this->qid]))
			$text = $defaults[$this->qid];
		else 
			$text = '';

		$tag = tag('textarea', attrs("rows=\"{$this->rows}\"",
					"name=\"{$this->qid}\"",
					"cols=\"{$this->cols}\""), $text);
		return tag('div', attrs("class=\"form-textarea\""), $tag);
        }
}

function form_date_input($qid, $defaults, $dateFormat) {
	$date_attrs = attrs('type="text"', 'class="form-date"',
			"name=\"$qid-date\"", "id=\"$qid-date\"");
	if(isset($defaults["$qid-date"]))
		$date_attrs->add("value=\"{$defaults["$qid-date"]}\"");
	return array(tag('input', $date_attrs),
			tag('script', attrs('type="text/javascript"'),
				"\$('#$qid-date').datepicker({dateFormat: \"$dateFormat\", firstDay: ".day_of_week_start()." });")); /**** */
}

/* this class is for date input
 */
class FormDateQuestion extends FormAtomicQuestion {
	var $date_format;

        function __construct($qid, $subject, $date_format, $description = false,
                        $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-date-question";
		$this->date_format = $date_format;
        }

        protected function get_specific_html($parent, $defaults) {
		switch($this->date_format) {
			case 0: // Month Day Year
				$dateFormat = "mm/dd/yy";
				$date_string = "MM/DD/YYYY";
				break;
			case 1: // Year Month Day
				$dateFormat = "yy-mm-dd";
				$date_string = "YYYY-MM-DD";
				break;
			case 2: // Day Month Year
				$dateFormat = "dd-mm-yy";
				$date_string = "DD-MM-YYYY";
				break;
			default:
				soft_error("Unrecognized date format.");
		}

                return tag('div', attrs("class=\"{$this->class}\""),
				form_date_input($this->qid, $defaults,
					$dateFormat));
        }
}

function form_time_input($qid, $defaults, $hour24) {
	$showPeriod = $hour24 ? "false" : "true";
	$time_attrs = attrs('type="text"', 'class="form-time"',
			"name=\"$qid-time\"", "id=\"$qid-time\"");
	if(isset($defaults["$qid-time"]))
		$time_attrs->add("value=\"{$defaults["$qid-time"]}\"");

	return array(tag('input', $time_attrs),
			tag('script', attrs('type="text/javascript"'),
				"\$('#$qid-time').timepicker({showPeriod: $showPeriod, showLeadingZero: false });"));
}

/* this class is for time input
 */
class FormTimeQuestion extends FormAtomicQuestion {
	var $hour24;

        function __construct($qid, $subject, $hour24, $description = false,
                        $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-time-question";
		$this->hour24 = $hour24;
        }

        protected function get_specific_html($parent, $defaults) {
                return tag('div', attrs("class=\"{$this->class}\""),
				form_time_input($this->qid, $defaults, $this->hour24));
        }
}

/* this class is for date input
 */
class FormDateTimeQuestion extends FormAtomicQuestion {
	var $hour24;
	var $date_format;

        function __construct($qid, $subject, $hour24, $date_format,
			$description = false, $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-date-time-question";
		$this->hour24 = $hour24;
		$this->date_format = $date_format;
        }

        protected function get_specific_html($parent, $defaults) {
		switch($this->date_format) {
			case 0: // Month Day Year
				$dateFormat = "mm/dd/yy";
				$date_string = "MM/DD/YYYY";
				break;
			case 1: // Year Month Day
				$dateFormat = "yy-mm-dd";
				$date_string = "YYYY-MM-DD";
				break;
			case 2: // Day Month Year
				$dateFormat = "dd-mm-yy";
				$date_string = "DD-MM-YYYY";
				break;
			default:
				soft_error("Unrecognized date format.");
		}

                return array(__("Date") . " ($date_string): ",
				form_date_input($this->qid, $defaults,
					$dateFormat),
				" " . __('Time') . ": ",
				form_time_input($this->qid, $defaults,
					$this->hour24));
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

        function __construct($qid, $subject, $description = false,
                        $lbound, $ubound, $increment, $default = false,
			$name_func = false, $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->lbound = $lbound;
		$this->ubound = $ubound;
		$this->increment = $increment;
		$this->default = $default;
		$this->name_func = $name_func;
		$this->class .= " form-sequence-question";
        }

        protected function get_specific_html($parent, $defaults) {
		$input = create_select_range($this->qid, $this->lbound,
				$this->ubound, $this->increment, $this->default,
				$this->name_func);
                return tag('div', attrs("class=\"{$this->class}\""), $day);
        }
}

/* creates a submit button
 */
class FormSubmitButton extends FormAtomicQuestion {
        var $title;

        function __construct($title = false) {
		parent::__construct();
                $this->title = $title;
		$this->class .= " form-submit";
        }

        protected function get_specific_html($parent, $defaults) {
                $attrs = attrs('type="submit"');
                if($this->title !== false) {
                        $attrs->add("value=\"{$this->title}\"");
                }
                return tag('div', attrs("class=\"{$this->class}\""),
                                tag('input', $attrs));
        }
}

/* this class is for questions where depending on the answer you need
 * to answer more questions
 */
abstract class FormCompoundQuestion extends FormQuestion {
	var $conditionals = array();
        var $options = array();
	var $descriptions = array();

	function __construct() {
		parent::__construct();
	}

        function add_option($key, $title, $description = NULL,
			$conditional = NULL) {
		$this->options[$key] = $title;
		if($description !== NULL)
			$this->descriptions[$key] = $description;
		if($conditional !== NULL)
			$this->conditionals[$key] = $conditional;
        }

	function add_options($options) {
		foreach($options as $key => $title) {
			$this->add_option($key, $title);
		}
	}
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class FormRadioQuestion extends FormCompoundQuestion {

        function __construct($qid, $subject = false, $required = false) {
		parent::__construct();
		$this->qid = $qid;
                $this->subject = $subject;
                $this->required = $required;
		$this->class .= " form-radio-question";
        }

	protected function get_specific_html($parent, $defaults) {
		$results = tag('div');
		foreach($this->options as $key => $name) {
			$attrs = attrs('type="radio"', 
					"name=\"{$this->qid}\"",
					"id=\"{$this->qid}\"",
					'class="form-select"');
			if($key !== NULL) {
				$attrs->add("value=\"$key\"");
				if(isset($defaults[$this->qid]) &&
						$defaults[$this->qid] == $key)
					$attrs->add("checked=\"checked\"");
                        }
			$tag = tag('div', tag('input', $attrs), $name);
			if(!empty($this->descriptions[$key])) {
				$tag->add("<span class=\"form-question-description\"> - {$this->descriptions[$key]}</span>");
			}

			if(!empty($this->conditionals[$key])) {
				$conditional = $this->conditionals[$key];
				$tag->add(tag('table', attrs("id=\"{$this->qid}-{$key}\""),
							$conditional->get_html
							($this, $defaults)));
			}

			$results->add($tag);
                }
                return $results;
        }
}

/* this class is for questions with a true/false answer
 */
class FormCheckBoxQuestion extends FormAtomicQuestion {
	var $value;
	var $desc;

        function __construct($qid, $subject = false, $desc = false,
			$required = false) {
		parent::__construct();
		$this->qid = $qid;
                $this->subject = $subject;
		$this->desc = $desc;
                $this->required = $required;
		$this->class .= " form-checkbox-question";
        }

	protected function get_specific_html($parent, $defaults) {
		$attrs = attrs('type="checkbox"', 
				"name=\"{$this->qid}\"",
				"id=\"{$this->qid}\"",
				'value="1"',
				'class="form-checkbox"');

		if(!empty($defaults[$this->qid]))
			$attrs->add("checked=\"checked\"");

		$tag = tag('div', tag('input', $attrs));
		if(!empty($this->desc))
			$tag->add(tag('label', attrs("for=\"{$this->qid}\"",
							'class="form-question-description"'),
						$this->desc));

                return $tag;
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class FormDropdownQuestion extends FormCompoundQuestion {

        function __construct($qid, $subject = false,
			$description = false, $required = false) {
		parent::__construct();
		$this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-dropdown-question";
        }

        protected function get_specific_html($parent, $defaults) {
		$select = tag('select', attrs("id=\"{$this->qid}\"",
					"name=\"{$this->qid}\"",
					'class="form-select"'));

		$children = array();
		foreach($this->options as $value => $name) {
			$attrs = attrs("value=\"$value\"");
			if(!empty($defaults[$this->qid])
					&& $defaults[$this->qid] == $value)
				$attrs->add('selected');
			$select->add(tag('option', $attrs, $name));

			if(!empty($this->conditionals[$value])) {
				$children[] = tag('table', attrs("id=\"{$this->qid}-{$value}\""),
						$this->conditionals[$value]->get_html($this, $defaults));
			}
                }
		if(empty($children))
			return $select;
		return array($select, $children);
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class FormMultipleSelectQuestion extends FormCompoundQuestion {

        function __construct($qid, $subject = false,
			$description = false, $required = false) {
		parent::__construct();
		$this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-multi-select-question";
        }

        protected function get_specific_html($parent, $defaults) {
		$select = tag('select', attrs("id=\"{$this->qid}\"",
					"name=\"{$this->qid}\"",
					'multiple="multiple"',
					'class="form-select"'));

		$children = array();
		foreach($this->options as $value => $name) {
			$attrs = attrs("value=\"$value\"");
			if(!empty($defaults[$this->qid])
					&& $defaults[$this->qid] == $value)
				$attrs->add('selected');
			$select->add(tag('option', $attrs, $name));

			if(!empty($this->conditionals[$value])) {
				$children[] = tag('table', attrs("id=\"{$this->qid}-{$value}\""),
						$this->conditionals[$value]->get_html($this, $defaults));
			}
                }
		if(empty($children))
			return $select;
		return array($select, $children);
        }
}

/* this class is for colorpickerer
 */
class FormColorPicker extends FormAtomicQuestion {
        function __construct($qid, $subject,$selectedcolor=false, $description = false,
                        $required = false) {
		parent::__construct();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
                $this->selectedcolor = $selectedcolor;
                $this->class .= " form-color-picker";
        }

        protected function get_specific_html($parent, $defaults) {

		if(isset($defaults[$this->qid]))
			$value = $defaults[$this->qid];
		else 
			$value = '';

		return tag('input', attrs('type="text"',
					'class="form-color-input"',
					"name=\"{$this->qid}\"",
					"value=\"$value\"",
					"id=\"{$this->qid}\""));
        }
}



/* this is the main form class
 */
class Form extends FormGroup {
        var $vars;
        var $action;
	var $hidden = array();

        function __construct($action, $title = false, $method = false) {
                parent::__construct($title);
                $this->action = $action;
                $this->method = $method;
		$this->class .= " form";
        }

        function get_form($defaults = array()) {
                $form_attrs = attrs("action=\"{$this->action}\"",
				'method="POST"');
                if($this->method !== false) {
                        $form_attrs->add("method=\"{$this->method}\"");
                }
		$table = tag('table', attrs("class=\"{$this->class}\""));
                foreach($this->list as $child) {
                        $table->add($child->get_html($this, $defaults));
                }
		$form = tag('form', $form_attrs);
		$hidden_div = tag('div');
		$have_hidden = false;
		foreach($this->hidden as $name => $value) {
			$have_hidden = true;
			$hidden_div->add(tag('input', attrs('type="hidden"',
							"name=\"$name\"",
							"value=\"$value\"",
							"id=\"$name\"")));
		}
		if($have_hidden)
			$form->add($hidden_div);

		$form->add($table);
		return $form;
        }

        function process($vars) {
                $this->vars = $vars;
                return parent::process($vars);
        }

        function get_results($vars = false) {
                global $form_error_func;

                if($vars === false) {
                        if($this->vars === false)
                                $this->error('No vars');
                        $vars = $this->vars;
                }

                return parent::get_results($vars);
        }

	function add_hidden($name, $value)
	{
		$this->hidden[$name] = $value;
	}
}
?>
