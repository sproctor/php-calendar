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

    /**
     * Four Tuesdays, 7–8 PM, and the Occurrence objects they start as
     *
     * @return Occurrence[]
     */
    private function weeklySeries(): array
    {
        return $this->generator->generate($this->event, new DateTimeImmutable('2026-10-06 19:00'),
            new DateTimeImmutable('2026-10-06 20:00'), Occurrence::TIME_NORMAL, OccurrenceGenerator::REPEAT_WEEKLY, 1,
            new DateTimeImmutable('2026-10-27'));
    }

    private function updateWeekly(string $start, string $end, string $until): void
    {
        $this->generator->update($this->event, new DateTimeImmutable($start), new DateTimeImmutable($end),
            Occurrence::TIME_NORMAL, OccurrenceGenerator::REPEAT_WEEKLY, 1, new DateTimeImmutable($until));
    }

    /**
     * The event's occurrences in start order
     *
     * @return Occurrence[]
     */
    private function current(): array
    {
        $occurrences = $this->event->getOccurrences()->toArray();
        usort($occurrences, fn(Occurrence $a, Occurrence $b) => $a->getStart() <=> $b->getStart());

        return $occurrences;
    }

    public function testUpdateKeepsUnchangedOccurrences(): void
    {
        $original = $this->weeklySeries();
        // Two more weeks
        $this->updateWeekly('2026-10-06 19:00', '2026-10-06 20:00', '2026-11-10');

        $current = $this->current();
        $this->assertSame($original, array_slice($current, 0, 4));
        $this->assertSame(['2026-11-03 19:00', '2026-11-10 19:00'], $this->starts(array_slice($current, 4)));
    }

    public function testUpdateMovesTimesOfDay(): void
    {
        $original = $this->weeklySeries();
        $this->updateWeekly('2026-10-06 20:00', '2026-10-06 21:30', '2026-10-27');

        $this->assertSame($original, $this->current());
        $this->assertSame(['2026-10-06 20:00', '2026-10-13 20:00', '2026-10-20 20:00', '2026-10-27 20:00'], $this->starts($this->current()));
        $this->assertSame('21:30', $original[0]->getEnd()->format('H:i'));
    }

    public function testUpdateMovesDatesInOrder(): void
    {
        $original = $this->weeklySeries();
        // Wednesdays instead
        $this->updateWeekly('2026-10-07 19:00', '2026-10-07 20:00', '2026-10-28');

        $this->assertSame($original, $this->current());
        $this->assertSame(['2026-10-07 19:00', '2026-10-14 19:00', '2026-10-21 19:00', '2026-10-28 19:00'], $this->starts($this->current()));
    }

    public function testUpdateRemovesDatesNoLongerInTheSeries(): void
    {
        $original = $this->weeklySeries();
        $this->updateWeekly('2026-10-06 19:00', '2026-10-06 20:00', '2026-10-13');

        $this->assertSame(array_slice($original, 0, 2), $this->current());
    }

    public function testUpdateToAllDayKeepsTheOccurrence(): void
    {
        [$occurrence] = $this->generator->generate($this->event, new DateTimeImmutable('2026-10-15 17:00'),
            new DateTimeImmutable('2026-10-15 18:00'), Occurrence::TIME_NORMAL);
        $this->generator->update($this->event, new DateTimeImmutable('2026-10-15'), new DateTimeImmutable('2026-10-16'),
            Occurrence::TIME_FULL_DAY);

        $this->assertSame([$occurrence], $this->current());
        $this->assertFalse($occurrence->hasTime());
        $this->assertSame('2026-10-16', $occurrence->getEnd()->format('Y-m-d'));
    }
}
