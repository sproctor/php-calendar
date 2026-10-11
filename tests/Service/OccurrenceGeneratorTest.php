<?php

namespace App\Tests\Service;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Service\OccurrenceGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class OccurrenceGeneratorTest extends TestCase
{
    private OccurrenceGenerator $generator;
    private Event $event;

    protected function setUp(): void
    {
        date_default_timezone_set('America/New_York');
        $this->generator = new OccurrenceGenerator();
        $this->event = new Event(new Calendar(), null);
    }

    private function starts(array $occurrences): array
    {
        return array_map(fn(Occurrence $o) => $o->getStart()->format('Y-m-d H:i'), $occurrences);
    }

    public function testSingleOccurrence(): void
    {
        $occurrences = $this->generator->generate(
            $this->event,
            new DateTimeImmutable('2026-10-15 17:00'),
            new DateTimeImmutable('2026-10-15 18:00'),
            Occurrence::TIME_NORMAL,
        );
        $this->assertSame(['2026-10-15 17:00'], $this->starts($occurrences));
        $this->assertSame('18:00', $occurrences[0]->getEnd()->format('H:i'));
        $this->assertCount(1, $this->event->getOccurrences());
    }

    public function testWeeklyKeepsLocalTimeAcrossDst(): void
    {
        $occurrences = $this->generator->generate(
            $this->event,
            new DateTimeImmutable('2026-10-25 17:00'),
            new DateTimeImmutable('2026-10-25 18:00'),
            Occurrence::TIME_NORMAL,
            OccurrenceGenerator::REPEAT_WEEKLY,
            1,
            new DateTimeImmutable('2026-11-08'),
        );
        // US daylight saving time ends 2026-11-01
        $this->assertSame(['2026-10-25 17:00', '2026-11-01 17:00', '2026-11-08 17:00'], $this->starts($occurrences));
    }

    public function testMonthlySkipsMissingDays(): void
    {
        $occurrences = $this->generator->generate(
            $this->event,
            new DateTimeImmutable('2027-01-31'),
            new DateTimeImmutable('2027-01-31'),
            Occurrence::TIME_FULL_DAY,
            OccurrenceGenerator::REPEAT_MONTHLY,
            1,
            new DateTimeImmutable('2027-05-31'),
        );
        $this->assertSame(
            ['2027-01-31 00:00', '2027-03-31 00:00', '2027-05-31 00:00'],
            $this->starts($occurrences),
        );
    }

    public function testFullDayDatesDoNotShiftWithTimezone(): void
    {
        [$occurrence] = $this->generator->generate(
            $this->event,
            new DateTimeImmutable('2026-10-15'),
            new DateTimeImmutable('2026-10-16'),
            Occurrence::TIME_FULL_DAY,
        );
        date_default_timezone_set('Asia/Tokyo');
        $this->assertSame('2026-10-15', $occurrence->getStart()->format('Y-m-d'));
        $this->assertSame('2026-10-16', $occurrence->getEnd()->format('Y-m-d'));
        $this->assertSame('2026-10-17 00:00', $occurrence->getEndBoundary()->format('Y-m-d H:i'));
    }

    public function testCapsOccurrences(): void
    {
        $occurrences = $this->generator->generate(
            $this->event,
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-01'),
            Occurrence::TIME_FULL_DAY,
            OccurrenceGenerator::REPEAT_DAILY,
            1,
            new DateTimeImmutable('2036-01-01'),
        );
        $this->assertCount(OccurrenceGenerator::MAX_OCCURRENCES, $occurrences);
    }

    public function testInferRepeat(): void
    {
        $occurrences = $this->generator->generate(
            $this->event,
            new DateTimeImmutable('2026-10-15 17:00'),
            new DateTimeImmutable('2026-10-15 18:00'),
            Occurrence::TIME_NORMAL,
            OccurrenceGenerator::REPEAT_WEEKLY,
            2,
            new DateTimeImmutable('2026-12-31'),
        );
        $this->assertSame([OccurrenceGenerator::REPEAT_WEEKLY, 2], $this->generator->inferRepeat($occurrences));

        $monthly = $this->generator->generate(
            new Event(new Calendar(), null),
            new DateTimeImmutable('2026-01-15'),
            new DateTimeImmutable('2026-01-15'),
            Occurrence::TIME_FULL_DAY,
            OccurrenceGenerator::REPEAT_MONTHLY,
            1,
            new DateTimeImmutable('2026-06-30'),
        );
        $this->assertSame([OccurrenceGenerator::REPEAT_MONTHLY, 1], $this->generator->inferRepeat($monthly));
        $this->assertSame([OccurrenceGenerator::REPEAT_NEVER, 1], $this->generator->inferRepeat([$monthly[0]]));
    }
}
