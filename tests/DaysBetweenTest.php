<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../src/helpers.php";

class DaysBetweenTest extends TestCase
{
	/**
	 * @dataProvider dateProvider
	 */
	public function testDaysBetween($date1, $date2, $expected) {
		$this->assertEquals($expected, PhpCalendar\days_between(new DateTime($date1), new DateTime($date2)));
	}
	
	public function dateProvider() {
		return [
				["2000-01-01", "2000-01-01", 0],
				["2000-01-01 00:00:00", "2000-01-01 23:59:59", 0],
				["2000-01-01 00:00:00", "2000-01-02 00:00:00", 1],
				["2001-02-05 01:01:01", "2001-02-01 22:00:00", -4],
				["2001-02-01 22:00:00", "2001-02-05 01:01:01", 4],
				["2001-02-01 22:00:00", "2001-02-05 22:00:00", 4],
				["2001-02-02 00:00:00", "2002-02-02 00:00:00", 365]
		];
	}
}