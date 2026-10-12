<?php

namespace App\Tests\Form;

use App\Entity\Occurrence;
use App\Form\OccurrenceTimesType;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

class OccurrenceTimesTypeTest extends TestCase
{
    protected function tearDown(): void
    {
        Clock::set(new \Symfony\Component\Clock\NativeClock());
    }

    public static function nowCases(): iterable
    {
        yield 'mid-afternoon' => ['14:20', '15:00', '16:00'];
        yield 'on the hour' => ['09:00', '10:00', '11:00'];
        yield 'just after midnight' => ['00:05', '01:00', '02:00'];
        yield 'late evening stays within the day' => ['22:15', '22:00', '23:00'];
        yield 'and later' => ['23:40', '22:00', '23:00'];
    }

    #[DataProvider('nowCases')]
    public function testStartsAtTheNextWholeHour(string $now, string $start, string $end): void
    {
        Clock::set(new MockClock("2026-10-12 $now"));
        // Another day than today keeps that day
        $times = OccurrenceTimesType::defaults(new DateTimeImmutable('2026-10-20'));

        $this->assertSame(Occurrence::TIME_NORMAL, $times['time_type']);
        $this->assertSame("2026-10-20 $start", $times['start']->format('Y-m-d H:i'));
        $this->assertSame($end, $times['end']->format('H:i'));
        $this->assertSame('2026-10-20', $times['start_date']->format('Y-m-d'));
    }
}
