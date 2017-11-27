<?php

class InvalidInputException extends \Exception {
	/** @var string */
	var $target;

	/**
	 * InvalidInputException constructor.
	 * @param string $msg
	 * @param string $target
	 */
	function __construct($msg, $target = null) {
		parent::__construct($msg);
		$this->target = $target;
	}
}
