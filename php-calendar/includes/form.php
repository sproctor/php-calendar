<?php
/*
 * Copyright 2010 Sean Proctor
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

        function get_html($parent, $defaults = array()) {
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

        function get_specific_html($parent, $defaults = array()) {
		$results = array();
                foreach($this->list as $child) {
                        $results[] = $child->get_html($this, $defaults);
                }
		return $results;
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
        var $qid = false;
        var $required = false;
        var $error = false;
        var $subject = false;
        var $description = false;

	function FormQuestion() {
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

/* this class is the base for all types of questions
 */
class FormAtomicQuestion extends FormQuestion {

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

        function FormFreeQuestion($qid, $subject, $description = false,
                        $maxlen = false, $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->maxlen = $maxlen;
                $this->required = $required;
		$this->class .= " form-free-question";
        }

        function get_specific_html($parent, $defaults = array()) {
                $attrs = attrs("name=\"{$this->qid}\"", "id=\"{$this->qid}\"",
				'type="text"');
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

        function FormLongFreeQuestion($qid, $subject, $description = false,
                        $rows = 8, $cols = 80, $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
		$this->rows = $rows;
		$this->cols = $cols;
                $this->required = $required;
		$this->class .= " form-long-free-question";
        }

        function get_specific_html($parent, $defaults = array()) {
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

/* this class is for date input
 */
class FormDateQuestion extends FormAtomicQuestion {

        function FormDateQuestion($qid, $subject, $description = false,
                        $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-date-question";
        }

        function get_specific_html($parent, $defaults = array()) {
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

                return tag('div', attrs("class=\"{$this->class}\""),
				$year_input, $month_input, $day_input);
        }
}

/* this class is for time input
 */
class FormTimeQuestion extends FormAtomicQuestion {

        function FormTimeQuestion($qid, $subject, $description = false,
                        $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-time-question";
        }

        function get_specific_html($parent, $defaults = array()) {
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
                return tag('div', attrs("class=\"{$this->class}\""),
				$hour_input, $minute_input, $meridiem_input);
        }
}

/* this class is for date input
 */
class FormDateTimeQuestion extends FormAtomicQuestion {
	var $hour24;

        function FormDateTimeQuestion($qid, $subject, $hour24,
			$description = false, $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-date-time-question";
		$this->hour24 = $hour24;
        }

        function get_specific_html($parent, $defaults = array()) {

		// Year Input
		if(isset($defaults["{$this->qid}-year"])) {
			$year = $defaults["{$this->qid}-year"];
		} else {
			$year = date("Y");
		}
		$year_input = create_select_range("{$this->qid}-year", 1970,
				$year + 20, 1, $year);

		// Month Input
		if(isset($defaults["{$this->qid}-month"])) {
			$month = $defaults["{$this->qid}-month"];
		} else {
			$month = date("m");
		}
		$month_input = create_select_range("{$this->qid}-month", 1, 12,
				1, $month, "month_name");

		// Day Input
		if(isset($defaults["{$this->qid}-day"])) {
			$day = $defaults["{$this->qid}-day"];
		} else {
			$day = date("d");
		}
		$day_input = create_select_range("{$this->qid}-day", 1, 31, 1,
				$day);

		// Hour Input
		if(isset($defaults["{$this->qid}-hour"])) {
			if($this->hour24)
				$hour = $defaults["{$this->qid}-hour"];
			else
				$hour = date("g", strtotime($defaults["{$this->qid}-hour"] . ':00'));
		} else {
			if($this->hour24)
				$hour = date("G");
			else
				$hour = date("g");
		}

		if($this->hour24)
			$hour_input = create_select_range("{$this->qid}-hour",
					1, 24, 1, $hour);
		else
			$hour_input = create_select_range("{$this->qid}-hour",
					1, 12, 1, $hour);

		if(isset($defaults["{$this->qid}-minute"])) {
			$minute = $defaults["{$this->qid}-minute"];
		} else {
			$minute = date("i");
		}
		$minute_input = create_select_range("{$this->qid}-minute", 0,
				59, 1, $minute);

		$time_tag = tag('span', attrs("id=\"{$this->qid}-time\""),
				_("Time") . " ", $hour_input, $minute_input);

		if(!$this->hour24) {
			if(isset($defaults["{$this->qid}-hour"])) {
				$meridiem = date("a", strtotime($defaults["{$this->qid}-hour"] . ':00'));
			} else {
				$meridiem = date("a");
			}
			$meridiem_input = create_select("{$this->qid}-meridiem",
					array("am" => _("AM"), "pm" => _("PM")),
					$meridiem);
			$time_tag->add($meridiem_input);
		}

                return array(tag('span', attrs("id=\"{$this->qid}-date\""),
					_("Date") . " ", $year_input,
					$month_input, $day_input), " ",
					$time_tag);
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

        function FormSequenceQuestion($qid, $subject, $description = false,
                        $lbound, $ubound, $increment, $default = false,
			$name_func = false, $required = false) {
		parent::FormAtomicQuestion();
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

        function get_specific_html($parent, $defaults = array()) {
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

        function FormSubmitButton($title = false) {
		parent::FormAtomicQuestion();
                $this->title = $title;
		$this->class .= " form-submit";
        }

        function get_specific_html($parent, $defaults = array()) {
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
class FormCompoundQuestion extends FormQuestion {
	var $conditionals = array();
        var $options = array();
	var $descriptions = array();

	function FormCompoundQuestion() {
		parent::FormQuestion();
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

        function FormRadioQuestion($qid, $subject = false, $required = false) {
		parent::FormCompoundQuestion();
		$this->qid = $qid;
                $this->subject = $subject;
                $this->required = $required;
		$this->class .= " form-radio-question";
        }

	function get_specific_html($parent, $defaults = array()) {
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

        function FormCheckBoxQuestion($qid, $subject = false, $desc = false,
			$required = false) {
		parent::FormAtomicQuestion();
		$this->qid = $qid;
                $this->subject = $subject;
		$this->desc = $desc;
                $this->required = $required;
		$this->class .= " form-checkbox-question";
        }

	function get_specific_html($parent, $defaults = array()) {
		$attrs = attrs('type="checkbox"', 
				"name=\"{$this->qid}\"",
				"id=\"{$this->qid}\"",
				'value="1"',
				'class="form-checkbox"');

		if(!empty($defaults[$this->qid]))
			$attrs->add("checked=\"checked\"");

		$tag = tag('div', tag('input', $attrs));
		if(!empty($this->descr))
			$tag->add(tag('span', attrs('class="form-question-description"'),
						$this->desc));

                return $tag;
        }
}

/* this class is for questions where you need to choose between
 * multiple things
 */
class FormDropdownQuestion extends FormCompoundQuestion {

        function FormDropdownQuestion($qid, $subject = false,
			$description = false, $required = false) {
		parent::FormCompoundQuestion();
		$this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
		$this->class .= " form-dropdown-question";
        }

        function get_specific_html($parent, $defaults = array()) {
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

/* this class is for colorpickerer
 */
class FormColorPicker extends FormAtomicQuestion {
        function Formcolorpicker($qid, $subject,$selectedcolor=false, $description = false,
                        $required = false) {
		parent::FormAtomicQuestion();
                $this->qid = $qid;
                $this->subject = $subject;
                $this->description = $description;
                $this->required = $required;
                $this->selectedcolor = $selectedcolor;
                $this->class .= " form-color-picker";
        }

        function get_specific_html($parent, $defaults = array()) {

		if(isset($defaults[$this->qid]))
			$value = $defaults[$this->qid];
		else 
			$value = '';

		$color_matrix = array(
				array("#FFFFFF", "#FFCCCC", "#FFCC99", "#FFFF99", "#FFFFCC", "#99FF99", "#99FFFF", "#CCFFFF", "#CCCCFF", "#FFCCFF"),
				array("#CCCCCC", "#FF6666", "#FF9966", "#FFFF66", "#FFFF33", "#66FF99", "#33FFFF", "#66FFFF", "#9999FF", "#FF99FF"),
				array("#C0C0C0", "#FF0000", "#FF9900", "#FFCC66", "#FFFF00", "#33FF33", "#66CCCC", "#33CCFF", "#6666CC", "#CC66CC"),
				array("#999999", "#CC0000", "#FF6600", "#FFCC33", "#FFCC00", "#33CC00", "#00CCCC", "#3366FF", "#6633FF", "#CC33CC"),
				array("#666666", "#990000", "#CC6600", "#CC9933", "#999900", "#009900", "#339999", "#3333FF", "#6600CC", "#993399"),
				array("#333333", "#660000", "#993300", "#996633", "#666600", "#006600", "#336666", "#000099", "#333399", "#663366"),
				array("#000000", "#330000", "#663300", "#663333", "#333300", "#003300", "#003333", "#000066", "#330099", "#330033"));

		$tbody = tag('tbody');
		foreach($color_matrix as $color_row) {
			$tr = tag('tr');
			foreach($color_row as $color) {
				$class = 'form-color';
				if($value == $color)
					$class .= " form-color-selected";
				$tr->add(tag('td', attrs("class=\"$class\"", "style=\"background-color: $color\"")));
			}
			$tbody->add($tr);
		}

		return tag('',
				tag('input', attrs('type="hidden"',
						"name=\"{$this->qid}\"",
						"value=\"$value\"",
						"id=\"{$this->qid}\"")),
				tag('table', attrs('style="border-collapse: separate"',
						'border="1"', 'text-align="left"',
						'cellspacing="1"'),
					$tbody));
        }
}



/* this is the main form class
 */
class Form extends FormGroup {
        var $vars;
        var $action;
	var $hidden = array();

        function Form($action, $title = false, $method = false) {
                parent::FormGroup($title);
                $this->action = $action;
                $this->method = $method;
		$this->class .= " form";
        }

        function get_html($defaults = array()) {
                $form_attrs = attrs("action=\"{$this->action}\"",
				'method="POST"');
                if($this->method !== false) {
                        $form_attrs->add("method=\"{$this->method}\"");
                }
		$table = tag('table', attrs("class=\"{$this->class}\""));
                foreach($this->list as $child) {
                        $table->add($child->get_html($this, $defaults));
                }
		$form = tag('form', $form_attrs,
				tag("script",
					attrs('type="text/javascript"',
						'src="static/form.js"'), ''));
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
                        if($this->vars === false) {
                                $this->error('No vars');
                        }
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
