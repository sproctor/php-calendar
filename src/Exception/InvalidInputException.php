<?php

namespace App\Exception;

class InvalidInputException extends \UnexpectedValueException
{
    /**
     * @var string $target
     */
    private $target;

    /**
     * InvalidInputException constructor.
     *
     * @param string $msg
     * @param string $target
     */
    public function __construct($msg, $target = null)
    {
        parent::__construct($msg);
        $this->target = $target;
    }
}
