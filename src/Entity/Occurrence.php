<?php
/*
 * Copyright Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Entity;

use App\Repository\OccurrenceRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use IntlDateFormatter;
use Locale;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * One dated instance of an event.
 *
 * Normal occurrences are moments in time: start and end are stored in UTC and returned in the current
 * default timezone. Full-day and TBA occurrences are calendar dates that don't move between timezones:
 * they're stored as midnight UTC and returned as midnight of the same dates in the default timezone.
 * The end of a full-day occurrence is its last day.
 */
#[ORM\Entity(repositoryClass: OccurrenceRepository::class)]
#[ORM\Table(name: 'occurrences')]
class Occurrence
{
    public const TIME_NORMAL = 0;
    public const TIME_FULL_DAY = 1;
    public const TIME_TBA = 2;
    /** Shows no time; only comes from imported data */
    public const TIME_NONE = 3;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $oid;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'occurrences')]
    #[ORM\JoinColumn(name: 'eid', referencedColumnName: 'eid', nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $start;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $end;

    #[ORM\Column(type: 'integer')]
    private int $time_type;

    public function __construct(Event $event, DateTimeInterface $start, DateTimeInterface $end, int $time_type)
    {
        $this->event = $event;
        $this->setTimes($start, $end, $time_type);
        $event->addOccurrence($this);
    }

    public function getOid(): int
    {
        return $this->oid;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getTimeType(): int
    {
        return $this->time_type;
    }

    public function hasTime(): bool
    {
        return $this->time_type === self::TIME_NORMAL;
    }

    /**
     * Sets the start, end and time type together, since how the times are stored depends on the type.
     * For dated types only the dates of $start and $end are used.
     */
    public function setTimes(DateTimeInterface $start, DateTimeInterface $end, int $time_type): self
    {
        $this->time_type = $time_type;
        if ($time_type === self::TIME_NORMAL) {
            $this->start = DateTimeImmutable::createFromInterface($start);
            $this->end = DateTimeImmutable::createFromInterface($end);
        } else {
            $utc = new DateTimeZone('UTC');
            $this->start = new DateTimeImmutable($start->format('Y-m-d'), $utc);
            $this->end = new DateTimeImmutable($end->format('Y-m-d'), $utc);
        }

        return $this;
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->localize($this->start);
    }

    public function getEnd(): DateTimeImmutable
    {
        return $this->localize($this->end);
    }

    /**
     * The moment the occurrence is over, for overlap checks: the end time, or the end of the last day
     */
    public function getEndBoundary(): DateTimeImmutable
    {
        $end = $this->getEnd();

        return $this->hasTime() ? $end : $end->modify('+1 day');
    }

    private function localize(DateTimeImmutable $value): DateTimeImmutable
    {
        $timezone = new DateTimeZone(date_default_timezone_get());
        if ($this->time_type === self::TIME_NORMAL) {
            return $value->setTimezone($timezone);
        }

        return new DateTimeImmutable($value->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d'), $timezone);
    }

    /**
     * The time of day, or TBA, for showing next to the subject; null for full-day occurrences
     */
    public function getTimeString(): string|TranslatableInterface|null
    {
        return match ($this->time_type) {
            self::TIME_NORMAL => self::timeFormatter()->format($this->getStart()),
            self::TIME_TBA => new TranslatableMessage('to-be-announced-abbr'),
            default => null,
        };
    }

    /**
     * The start and end times, or TBA; null for full-day occurrences
     */
    public function getTimespanString(): ?TranslatableInterface
    {
        return match ($this->time_type) {
            self::TIME_NORMAL => new TranslatableMessage('time-range', [
                '%start%' => self::timeFormatter()->format($this->getStart()),
                '%end%' => self::timeFormatter()->format($this->getEnd()),
            ]),
            self::TIME_TBA => new TranslatableMessage('to-be-announced-abbr'),
            default => null,
        };
    }

    /**
     * The full date and time span
     */
    public function getDatetimeString(): TranslatableInterface
    {
        $start = $this->getStart();
        $end = $this->getEnd();
        $dateFormatter = self::formatter(IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        $startDate = $dateFormatter->format($start);
        $endDate = $dateFormatter->format($end);
        $sameDay = $start->format('Y-m-d') === $end->format('Y-m-d');

        if ($this->time_type === self::TIME_NORMAL) {
            if ($sameDay) {
                return new TranslatableMessage('date-at-time-range', [
                    '%date%' => $startDate,
                    '%start%' => self::timeFormatter()->format($start),
                    '%end%' => self::timeFormatter()->format($end),
                ]);
            }
            $formatter = self::formatter(IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);

            return new TranslatableMessage('date-time-range', [
                '%start%' => $formatter->format($start),
                '%end%' => $formatter->format($end),
            ]);
        }

        $dates = $sameDay
            ? new TranslatableMessage('passthrough', ['%value%' => $startDate])
            : new TranslatableMessage('date-time-range', ['%start%' => $startDate, '%end%' => $endDate]);
        if ($this->time_type === self::TIME_TBA) {
            return new TranslatableMessage('date-at-time-to-be-announced-abbr', ['%date%' => $dates]);
        }

        return $dates;
    }

    private static function timeFormatter(): IntlDateFormatter
    {
        return self::formatter(IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
    }

    private static function formatter(int $dateType, int $timeType): IntlDateFormatter
    {
        return new IntlDateFormatter(Locale::getDefault(), $dateType, $timeType, date_default_timezone_get());
    }
}
