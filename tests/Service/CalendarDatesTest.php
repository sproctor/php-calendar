<?php

namespace App\Tests\Service;

use App\Service\CalendarDates;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CalendarDatesTest extends TestCase
{
    private CalendarDates $dates;
    private string $locale;

    protected function setUp(): void
    {
        $this->dates = new CalendarDates();
        $this->locale = \Locale::getDefault();
        date_default_timezone_set('America/New_York');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);
    }

    public function testDateRollsOver(): void
    {
        $this->assertSame('2026-09-30', $this->dates->date(2026, 10, 0)->format('Y-m-d'));
        $this->assertSame('2027-01-05', $this->dates->date(2026, 12, 36)->format('Y-m-d'));
        $this->assertSame('00:00 America/New_York', $this->dates->date(2026, 10, 15)->format('H:i e'));
    }

    public static function monthGrids(): iterable
    {
        // locale, first day of week, grid start for October 2026, weeks
        yield 'US weeks start on Sunday' => ['en_US', 7, '2026-09-27', 5];
        yield 'UK weeks start on Monday' => ['en_GB', 1, '2026-09-28', 5];
        yield 'Egypt weeks start on Saturday' => ['ar_EG', 6, '2026-09-26', 6];
    }

    #[DataProvider('monthGrids')]
    public function testMonthGrid(string $locale, int $firstDay, string $gridStart, int $weeks): void
    {
        \Locale::setDefault($locale);
        $this->assertSame($firstDay, $this->dates->firstDayOfWeek());
        $this->assertSame($gridStart, $this->dates->monthGridStart(2026, 10)->format('Y-m-d'));
        $this->assertSame($weeks, $this->dates->weeksInMonth(2026, 10));
        $this->assertSame(0, $this->dates->dayOfWeek($this->dates->monthGridStart(2026, 10)));
    }

    public function testFebruaryStartingOnTheFirstDayOfTheWeekHasFourWeeks(): void
    {
        \Locale::setDefault('en_GB');
        // February 2027 starts on a Monday and has 28 days
        $this->assertSame(4, $this->dates->weeksInMonth(2027, 2));
    }

    public static function weeks(): iterable
    {
        yield ['en_US', '2027-01-01', 1, 2027, '2026-12-27'];
        yield ['en_GB', '2027-01-01', 53, 2026, '2026-12-28'];
        yield ['en_GB', '2027-01-04', 1, 2027, '2027-01-04'];
        yield ['ar_EG', '2026-10-01', 40, 2026, '2026-09-26'];
    }

    #[DataProvider('weeks')]
    public function testWeekNumbers(string $locale, string $date, int $week, int $year, string $weekStart): void
    {
        \Locale::setDefault($locale);
        $date = new DateTimeImmutable($date);
        $this->assertSame($week, $this->dates->weekOfYear($date));
        $this->assertSame($year, $this->dates->weekYear($date));
        $this->assertSame($weekStart, $this->dates->weekStart($week, $year)->format('Y-m-d'));
    }

    public function testIsToday(): void
    {
        $this->assertTrue($this->dates->isToday(new DateTimeImmutable('today 23:59')));
        $this->assertFalse($this->dates->isToday(new DateTimeImmutable('tomorrow')));
    }

    public function testMonthName(): void
    {
        \Locale::setDefault('fr_FR');
        $this->assertSame('octobre', $this->dates->monthName($this->dates->date(2026, 10, 1)));
    }
}
