<?php

namespace App\Exception;

class InvalidInputException extends \UnexpectedValueException
{
    /**
     * InvalidInputException constructor.
     *
     * @param string $msg
     * @param string $target
     */
    public function __construct($msg, private $target = null)
    {
        parent::__construct($msg);
    }
}
