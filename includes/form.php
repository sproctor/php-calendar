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

/* This class is the base of either a question or a group
 */

abstract class FormPart
{
    var $class = "form-part";

    /**
     * @param FormPart $parent
     * @param array $defaults
     * @return Html
     */
    abstract function get_html($parent, $defaults = array());

    /**
     * @param FormPart $parent
     * @param array $defaults
     * @return Html
     */
    abstract protected function get_specific_html($parent, $defaults);
}

/* this class is to group multiple questions together
 */

class FormGroup extends FormPart
{
    /** @var FormPart[] */
    var $list = array();
    var $title = false;

    function __construct($title = false, $class = false)
    {
        $this->title = $title;
        $this->class .= " form-group";
        if ($class !== false)
            $this->class = " $class";
    }

    /* add a category or question */
    function add_part(FormPart $item)
    {
        $this->list[] = $item;
    }

    function get_html($parent, $defaults = array())
    {
        // If a FormGroup is embedded in another FormGroup we want to
        //   start a new table. If it isn't, we assume that our parent
        //   (radio or dropdown) has already created the table for us.
        if (!is_a($parent, 'FormGroup'))
            return $this->get_specific_html($parent, $defaults);

        $tag = tag('tr', attrs("class=\"{$this->class}\""));
        $cell = tag('td');

        if ($this->title !== false)
            $tag->add(tag('th', $this->title));
        else
            $cell->add(attrs("colspan=\"2\""));

        $cell->add(tag('table', $this->get_specific_html($parent,
            $defaults)));
        $tag->add($cell);

        return $tag;
    }

    protected function get_specific_html($parent, $defaults)
    {
        $results = array();
        foreach ($this->list as $child) {
            $results[] = $child->get_html($this, $defaults);
        }
        return $results;
    }
}

/* this is the base class for all questions
 */

abstract class FormQuestion extends FormPart
{
    /** @var string|bool */
    var $qid;
    /** @var string|bool */
    var $subject;
    /** @var string|bool */
    var $description;

    /**
     * FormQuestion constructor.
     * @param string $qid
     * @param string $subject
     * @param string|bool $description
     */
    function __construct($qid, $subject, $description)
    {
        $this->class .= " form-question";
        $this->qid = $qid;
        $this->subject = $subject;
        $this->description = $description;
    }

    function get_html($parent, $defaults = array())
    {
        $tag = tag('tr', attrs("class=\"{$this->class}\""));
        $cell = tag('td');

        if ($this->subject !== false)
            $tag->add(tag('th', tag('label', attributes("for=\"{$this->qid}\""), $this->subject)));
        else
            $cell->add(attrs("colspan=\"2\""));

        if ($this->description !== false)
            $cell->add(tag('label', attrs("for=\"{$this->qid}\"", 'class="form-question-description"'),
                $this->description));

        $cell->add($this->get_specific_html($parent, $defaults));
        $tag->add($cell);

        return $tag;
    }
}

/* this class is the base for all simple types of questions
 */

abstract class FormAtomicQuestion extends FormQuestion
{
    /**
     * FormAtomicQuestion constructor.
     * @param string $qid
     * @param string $subject
     * @param string $description
     */
    function __construct($qid, $subject, $description)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-atomic-question";
    }
}

/* This class is for free response questions with responses that are a few
 * sentences long at most
 */

class FormFreeQuestion extends FormAtomicQuestion
{
    var $maxlen;
    var $type;

    function __construct($qid, $subject, $description = false, $maxlen = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->maxlen = $maxlen;
        $this->class .= " form-free-question";
        $this->type = "text";
    }

    protected function get_specific_html($parent, $defaults)
    {
        $attrs = attrs("name=\"{$this->qid}\"", "id=\"{$this->qid}\"",
            "type=\"$this->type\"");
        if (!empty($defaults[$this->qid])) {
            $attrs->add("value=\"{$defaults[$this->qid]}\"");
        }
        if ($this->maxlen !== false) {
            $attrs->add("maxlength=\"{$this->maxlen}\"");
            $size = min(50, $this->maxlen);
            $attrs->add("size=\"$size\"");
        }

        return tag('input', $attrs);
    }

}

/* this class is for longer free reponse questions
 */

class FormLongFreeQuestion extends FormAtomicQuestion
{
    var $rows;
    var $cols;

    function __construct($qid, $subject, $description = false, $rows = 8, $cols = 80)
    {
        parent::__construct($qid, $subject, $description);
        $this->rows = $rows;
        $this->cols = $cols;
        $this->class .= " form-long-free-question";
    }

    protected function get_specific_html($parent, $defaults)
    {
        if (isset($defaults[$this->qid]))
            $text = $defaults[$this->qid];
        else
            $text = '';

        $tag = tag('textarea', attrs("rows=\"{$this->rows}\"",
            "name=\"{$this->qid}\"",
	    "id=\"{$this->qid}\"",
            "cols=\"{$this->cols}\""), $text);
        return tag('div', attrs("class=\"form-textarea\""), $tag);
    }
}

function form_date_input($qid, $defaults, $dateFormat)
{
    $date_attrs = attrs('type="text"', 'class="form-date"',
        "name=\"$qid-date\"", "id=\"$qid-date\"");
    if (isset($defaults["$qid-date"]))
        $date_attrs->add("value=\"{$defaults["$qid-date"]}\"");
    return array(tag('input', $date_attrs),
        tag('script', attrs('type="text/javascript"'),
            "\$('#$qid-date').datepicker({dateFormat: \"$dateFormat\", firstDay: " . day_of_week_start() . " });"));
    /**** */
}

/* this class is for date input
 */

class FormDateQuestion extends FormAtomicQuestion
{
    var $date_format;

    function __construct($qid, $subject, $date_format, $description = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-date-question";
        $this->date_format = $date_format;
    }

    protected function get_specific_html($parent, $defaults)
    {
        switch ($this->date_format) {
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
            tag('label', attrs("for=\"{$this->qid}-date\""), __("Date") . " ($date_string): "),
            form_date_input($this->qid, $defaults,
                $dateFormat));
    }
}

function form_time_input($qid, $defaults, $hour24)
{
    $showPeriod = $hour24 ? "false" : "true";
    $time_attrs = attrs('type="text"', 'class="form-time"',
        "name=\"$qid-time\"", "id=\"$qid-time\"");
    if (isset($defaults["$qid-time"]))
        $time_attrs->add("value=\"{$defaults["$qid-time"]}\"");

    return array(tag('input', $time_attrs),
        tag('script', attrs('type="text/javascript"'),
            "\$('#$qid-time').timepicker({showPeriod: $showPeriod, showLeadingZero: false });"));
}

/* this class is for time input
 */

class FormTimeQuestion extends FormAtomicQuestion
{
    var $hour24;

    function __construct($qid, $subject, $hour24, $description = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-time-question";
        $this->hour24 = $hour24;
    }

    protected function get_specific_html($parent, $defaults)
    {
        return tag('div', attrs("class=\"{$this->class}\""),
            form_time_input($this->qid, $defaults, $this->hour24));
    }
}

/* this class is for date input
 */

class FormDateTimeQuestion extends FormAtomicQuestion
{
    var $hour24;
    var $date_format;

    function __construct($qid, $subject, $hour24, $date_format, $description = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-date-time-question";
        $this->hour24 = $hour24;
        $this->date_format = $date_format;
    }

    protected function get_specific_html($parent, $defaults)
    {
        switch ($this->date_format) {
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

        return array(tag('label', attrs("for=\"{$this->qid}-date\""), __("Date") . " ($date_string): "),
            form_date_input($this->qid, $defaults,
                $dateFormat),
            " ", tag('label', attrs("for=\"{$this->qid}-time\""), __('Time') . ": "),
            form_time_input($this->qid, $defaults,
                $this->hour24));
    }
}

/* creates a submit button
 */

class FormSubmitButton extends FormQuestion
{
    var $title;

    function __construct($title = false)
    {
        parent::__construct(false, false, false);
        $this->title = $title;
        $this->class .= " form-submit";
    }

    protected function get_specific_html($parent, $defaults)
    {
        $attrs = attrs('type="submit"');
        if ($this->title !== false) {
            $attrs->add("value=\"{$this->title}\"");
        }
        return tag('div', attrs("class=\"{$this->class}\""),
            tag('input', $attrs));
    }
}

/* this class is for questions where depending on the answer you need
 * to answer more questions
 */

abstract class FormCompoundQuestion extends FormQuestion
{
    /** @var FormPart[] */
    var $conditionals = array();
    var $options = array();
    var $descriptions = array();

    function __construct($qid, $subject, $description)
    {
        parent::__construct($qid, $subject, $description);
    }

    function add_option($key, $title, $description = NULL,
                        $conditional = NULL)
    {
        $this->options[$key] = $title;
        if ($description !== NULL)
            $this->descriptions[$key] = $description;
        if ($conditional !== NULL)
            $this->conditionals[$key] = $conditional;
    }

    function add_options($options)
    {
        foreach ($options as $key => $title) {
            $this->add_option($key, $title);
        }
    }
}

/* this class is for questions with a true/false answer
 */

class FormCheckBoxQuestion extends FormAtomicQuestion
{
    var $checkbox_description;

    function __construct($qid, $subject = false, $description = false)
    {
        parent::__construct($qid, $subject, false);
        $this->class .= " form-checkbox-question";
	$this->checkbox_description = $description;
    }

    protected function get_specific_html($parent, $defaults)
    {
        $attrs = attrs('type="checkbox"',
            "name=\"{$this->qid}\"",
            "id=\"{$this->qid}\"",
            'value="1"',
            'class="form-checkbox"');

        if (!empty($defaults[$this->qid]))
            $attrs->add("checked=\"checked\"");

        $tag = tag('div', tag('input', $attrs));
        if (!empty($this->checkbox_description))
            $tag->add(tag('label', attrs("for=\"{$this->qid}\"",
                'class="form-question-description"'),
                $this->checkbox_description));

        return $tag;
    }
}

/* this class is for questions where you need to choose between
 * multiple things
 */

class FormDropdownQuestion extends FormCompoundQuestion
{

    function __construct($qid, $subject = false, $description = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-dropdown-question";
    }

    protected function get_specific_html($parent, $defaults)
    {
        $select = tag('select', attrs("id=\"{$this->qid}\"",
            "name=\"{$this->qid}\"",
            'class="form-select"'));

        $children = array();
        foreach ($this->options as $value => $name) {
            $attrs = attrs("value=\"$value\"");
            if (!empty($defaults[$this->qid])
                && $defaults[$this->qid] == $value
            )
                $attrs->add('selected');
            $select->add(tag('option', $attrs, $name));

            if (!empty($this->conditionals[$value])) {
                $children[] = tag('table', attrs("id=\"{$this->qid}-{$value}\""),
                    $this->conditionals[$value]->get_html($this, $defaults));
            }
        }
        if (empty($children))
            return $select;
        return array($select, $children);
    }
}

/* this class is for questions where you need to choose between
 * multiple things
 */

class FormMultipleSelectQuestion extends FormCompoundQuestion
{

    function __construct($qid, $subject = false, $description = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-multi-select-question";
    }

    protected function get_specific_html($parent, $defaults)
    {
        $select = tag('select', attrs("id=\"{$this->qid}\"",
            "name=\"{$this->qid}\"",
            'multiple="multiple"',
            'class="form-select"'));

        $children = array();
        foreach ($this->options as $value => $name) {
            $attrs = attrs("value=\"$value\"");
            if (!empty($defaults[$this->qid])
                && $defaults[$this->qid] == $value
            )
                $attrs->add('selected');
            $select->add(tag('option', $attrs, $name));

            if (!empty($this->conditionals[$value])) {
                $children[] = tag('table', attrs("id=\"{$this->qid}-{$value}\""),
                    $this->conditionals[$value]->get_html($this, $defaults));
            }
        }
        if (empty($children))
            return $select;
        return array($select, $children);
    }
}

/* this class is for colorpickerer
 */

class FormColorPicker extends FormAtomicQuestion
{
    function __construct($qid, $subject, $selectedcolor = false, $description = false)
    {
        parent::__construct($qid, $subject, $description);
        $this->class .= " form-color-picker";
    }

    protected function get_specific_html($parent, $defaults)
    {

        if (isset($defaults[$this->qid]))
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

class Form extends FormGroup
{
    var $vars;
    var $action;
    var $method;
    var $hidden = array();

    function __construct($action, $title = false, $method = false)
    {
        parent::__construct($title);
        $this->action = $action;
        $this->method = $method;
        $this->class .= " form";
    }

    function get_form($defaults = array())
    {
        $form_attrs = attrs("action=\"{$this->action}\"",
            'method="POST"');
        if ($this->method !== false) {
            $form_attrs->add("method=\"{$this->method}\"");
        }
        $table = tag('table', attrs("class=\"{$this->class}\""));
        foreach ($this->list as $child) {
            $table->add($child->get_html($this, $defaults));
        }
        $form = tag('form', $form_attrs);
        $hidden_div = tag('div');
        $have_hidden = false;
        foreach ($this->hidden as $name => $value) {
            $have_hidden = true;
            $hidden_div->add(tag('input', attrs('type="hidden"',
                "name=\"$name\"",
                "value=\"$value\"",
                "id=\"$name\"")));
        }
        if ($have_hidden)
            $form->add($hidden_div);

        $form->add($table);
        return $form;
    }

    function add_hidden($name, $value)
    {
        $this->hidden[$name] = $value;
    }
}
