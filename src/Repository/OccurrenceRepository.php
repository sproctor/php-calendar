<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Occurrence;
use App\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OccurrenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Occurrence::class);
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return Occurrence[]
     */
    public function findOccurrencesByDateRange(int $cid, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $query = $this->createQueryBuilder('o')
            ->join('o.event', 'e')
            ->where(":cid = e.calendar AND o.start <= :to AND o.end >= :from")
            ->setParameter("from", $from)
            ->setParameter("to", $to)
            ->setParameter('cid', $cid)
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @param User               $user
     * @return array
     */
    public function findOccurrencesByDay(Calendar $calendar, DateTimeInterface $from, DateTimeInterface $to, User $user): array
    {
        $all_occurrences = $this->findOccurrencesByDateRange($calendar->getCid(), $from, $to);
        $occurrences_by_day = array();
        $max_events = $calendar->getMaxDisplayEvents();

        foreach ($all_occurrences as $occurrence) {
//            if (!$occurrence->canRead($user)) {
//                continue;
//            }

            $end = $occurrence->getEnd();
            $start = $occurrence->getStart();

            if ($start > $from) {
                $diff = new \DateInterval("P0D");
            } else { // the event started before the range we're showing
                $diff = $from->diff($start);
            }

            // put the event in every day until the end
            for ($date = $start->add($diff); $date < $to && $date <= $end;
                 $date = $date->add(new \DateInterval("P1D"))) {
                $key = date_index($date);
                if (!isset($occurrences_by_day[$key])) {
                    $occurrences_by_day[$key] = array();
                }
                if (sizeof($occurrences_by_day[$key]) == $max_events) {
                    $occurrences_by_day[$key][] = null;
                }
                if (sizeof($occurrences_by_day[$key]) > $max_events) {
                    continue;
                }
                $occurrences_by_day[$key][] = $occurrence;
            }
        }
        return $occurrences_by_day;
    }
}
