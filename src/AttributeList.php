<?php

namespace PhpCalendar;

/*
 * Data structure to display XML style attributes
 * see function attributes() below for usage
 */
class AttributeList {
        var $list;

    /**
     * AttributeList constructor.
     * @param null|string $arg,...
     */
        function __construct($arg = null) {
                $this->list = array();
                if($arg !== null)
                        call_user_func_array(array($this, "add"), func_get_args());
        }

    /**
     * @param string $x,...
     */
        function add($x) {
                foreach(func_get_args() as $arg) {
                        $this->list[] = $arg;
                }
        }

        function toString() {
                return implode(' ', $this->list);
        }
}

?>
