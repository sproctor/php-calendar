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
namespace App\Form;

use App\Entity\Calendar;
use App\Entity\Event;
use App\Entity\Occurrence;
use App\Security\Voter\EventVoter;
use App\Service\OverlapChecker;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Applies a calendar's overlap policy to the event and occurrence forms: on calendars that warn, the
 * form gets a "Save anyway" checkbox; on calendars that don't allow overlaps, it can't be saved.
 */
class OverlapGuard
{
    public const FIELD = 'allow_overlap';

    /** Overlapping events named in the message; more are counted */
    private const LISTED = 5;

    public function __construct(
        private OverlapChecker $checker,
        private TranslatorInterface $translator,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public static function addField(FormBuilderInterface $builder, Calendar $calendar): void
    {
        if ($calendar->getOverlapPolicy() === Calendar::OVERLAP_WARN) {
            $builder->add(self::FIELD, CheckboxType::class, [
                'label' => new TranslatableMessage('save-anyway-label'),
                'mapped' => false,
                'required' => false,
            ]);
        }
    }

    /**
     * Whether $times may be saved; if not, adds an error to $form naming the events they overlap
     *
     * @param list<array{0: \DateTimeInterface, 1: \DateTimeInterface, 2: int}> $times
     */
    public function allows(FormInterface $form, Calendar $calendar, array $times, ?Event $event): bool
    {
        $policy = $calendar->getOverlapPolicy();
        if ($policy === Calendar::OVERLAP_ALLOW
            || ($policy === Calendar::OVERLAP_WARN && $form->has(self::FIELD) && $form->get(self::FIELD)->getData())) {
            return true;
        }
        $overlaps = $this->checker->findOverlaps($calendar, $times, $event);
        if ($overlaps === []) {
            return true;
        }

        $form->addError(new FormError($this->translator->trans(
            $policy === Calendar::OVERLAP_WARN ? 'overlap-warning' : 'overlap-error',
            ['%events%' => $this->describe($overlaps)],
        )));

        return false;
    }

    /**
     * @param Occurrence[] $overlaps
     */
    private function describe(array $overlaps): string
    {
        $names = array_map(function (Occurrence $occurrence): string {
            $event = $occurrence->getEvent();
            // Without giving away events the person can't see
            $subject = $this->authorizationChecker->isGranted(EventVoter::VIEW, $event)
                ? $event->getSubject()
                : $this->translator->trans('hidden-event-label');

            return $subject . ' (' . $occurrence->getDatetimeString()->trans($this->translator) . ')';
        }, array_slice($overlaps, 0, self::LISTED));
        if (count($overlaps) > self::LISTED) {
            $names[] = $this->translator->trans('more-overlaps', ['%number%' => count($overlaps) - self::LISTED]);
        }

        return implode('; ', $names);
    }
}
