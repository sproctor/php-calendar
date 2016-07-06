<?php
/**
 * Created by PhpStorm.
 * User: sproctor
 * Date: 4/1/16
 * Time: 9:47 AM
 */

namespace PhpCalendar;


class ActionItem
{
    private $text;
    private $action;
    private $arguments;
    private $attributes;
    private $icon;

    /**
     * ActionItem constructor.
     * @param string $text
     * @param string $action
     * @param null|string[] $arguments
     * @param null|AttributeList $attributes
     * @param null|string $icon
     */
    public function __construct($text, $action, $arguments = null, $attributes = null, $icon = null) {
        $this->text = $text;
        $this->action = $action;
        $this->arguments = $arguments;
        $this->attributes = $attributes;
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getText() {
        $text = str_replace(' ', '&nbsp;', $this->text);
        if ($this->icon != null)
            $text = fa($this->icon) . "&nbsp;$text";
        return $text;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @return null|string[]
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * @return null|AttributeList
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addArgument($key, $value) {
        if(empty($this->arguments))
            $this->arguments = array();
        $this->arguments[$key] = $value;
    }
}