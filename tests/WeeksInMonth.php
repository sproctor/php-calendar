<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../src/helpers.php";

class WeeksInMonthTest extends TestCase
{
	/**
	 * @dataProvider dateProvider
	 */
	public function testWeeksInMonth($month, $year, $week_start, $expected) {
		$this->assertEquals($expected, PhpCalendar\weeks_in_month($month, $year, $week_start));
	}
	
	public function dateProvider() {
		return [
				[1, 2016, 0, 6],
				[1, 2016, 1, 5],
				[2, 2016, 0, 5],
				[2, 2016, 1, 5],
				[2, 2015, 0, 4],
				[2, 2015, 1, 5],
				[12, 2016, 0, 5]
		];
	}
}