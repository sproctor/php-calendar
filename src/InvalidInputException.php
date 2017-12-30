<?php

namespace PhpCalendar;

class InvalidInputException extends \UnexpectedValueException
{
    /**
     * @var string 
     */
    var $target;

    /**
     * InvalidInputException constructor.
     *
     * @param string $msg
     * @param string $target
     */
    function __construct($msg, $target = null) 
    {
        parent::__construct($msg);
        $this->target = $target;
    }
}