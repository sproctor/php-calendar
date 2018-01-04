<?php

namespace PhpCalendar;

use PHPUnit\Framework\TestCase;

class DaysInYearTest extends TestCase
{
    /**
     * @dataProvider yearProvider
     */
    public function testYear($year, $expected)
    {
        $this->assertEquals($expected, days_in_year($year));
    }
    
    public function yearProvider()
    {
        return [
                [2001, 365],
                [2000, 366],
                [1900, 365],
                [2011, 365],
                [2016, 366],
                [2100, 365],
                [2400, 366]
        ];
    }
}
