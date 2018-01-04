<?php

namespace PhpCalendar;

class FailedActionException extends \Exception
{
    public function __construct($message = null)
    {
        if ($message == null) {
            $message = __('failed-action-error');
        }
        parent::__construct();
    }
}
