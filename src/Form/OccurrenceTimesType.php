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

use App\Entity\Occurrence;
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * The time type, start and end of an occurrence. Normal occurrences use the start and end date-times;
 * full-day and TBA occurrences use the start and end dates. The data is an array with the keys
 * time_type, start, end, start_date and end_date; see fromOccurrence() and toTimes().
 */
class OccurrenceTimesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateTime = [
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'required' => false,
            'input' => 'datetime_immutable',
        ];
        $date = [
            'widget' => 'single_text',
            'required' => false,
            'input' => 'datetime_immutable',
        ];
        $builder
            ->add('time_type', ChoiceType::class, [
                'label' => new TranslatableMessage('time-type-label'),
                'choices' => [
                    'normal-label' => Occurrence::TIME_NORMAL,
                    'full-day-label' => Occurrence::TIME_FULL_DAY,
                    'to-be-announced-label' => Occurrence::TIME_TBA,
                ],
                'attr' => ['class' => 'phpc-time-type'],
            ])
            ->add('start', DateTimeType::class, ['label' => new TranslatableMessage('from-label')] + $dateTime)
            ->add('end', DateTimeType::class, ['label' => new TranslatableMessage('to-label')] + $dateTime)
            ->add('start_date', DateType::class, ['label' => new TranslatableMessage('from-label')] + $date)
            ->add('end_date', DateType::class, ['label' => new TranslatableMessage('to-label')] + $date);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'label' => false,
            'constraints' => [new Callback(self::validate(...))],
        ]);
    }

    public static function validate(?array $data, ExecutionContextInterface $context): void
    {
        if ($data === null) {
            return;
        }
        $timed = ($data['time_type'] ?? null) === Occurrence::TIME_NORMAL;
        $startField = $timed ? 'start' : 'start_date';
        $endField = $timed ? 'end' : 'end_date';
        $start = $data[$startField] ?? null;
        $end = $data[$endField] ?? null;
        if ($start === null) {
            $context->buildViolation('missing-start-error')->atPath("[$startField]")->addViolation();
        }
        if ($end === null) {
            $context->buildViolation('missing-end-error')->atPath("[$endField]")->addViolation();
        }
        if ($start !== null && $end !== null && $end < $start) {
            $context->buildViolation('end-before-start-date-time-error')->atPath("[$endField]")->addViolation();
        }
    }

    /**
     * Form data for a new occurrence on $date, 17:00–18:00
     */
    public static function defaults(DateTimeImmutable $date): array
    {
        $start = $date->setTime(17, 0);

        return [
            'time_type' => Occurrence::TIME_NORMAL,
            'start' => $start,
            'end' => $start->setTime(18, 0),
            'start_date' => $start->setTime(0, 0),
            'end_date' => $start->setTime(0, 0),
        ];
    }

    public static function fromOccurrence(Occurrence $occurrence): array
    {
        $data = self::defaults($occurrence->getStart());
        $data['time_type'] = $occurrence->getTimeType() === Occurrence::TIME_NONE
            ? Occurrence::TIME_FULL_DAY
            : $occurrence->getTimeType();
        if ($occurrence->hasTime()) {
            $data['start'] = $occurrence->getStart();
            $data['end'] = $occurrence->getEnd();
        }
        $data['start_date'] = $occurrence->getStart()->setTime(0, 0);
        $data['end_date'] = $occurrence->getEnd()->setTime(0, 0);

        return $data;
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable, 2: int} start, end and time type
     */
    public static function toTimes(array $data): array
    {
        $type = $data['time_type'];
        if ($type === Occurrence::TIME_NORMAL) {
            return [$data['start'], $data['end'], $type];
        }

        return [$data['start_date'], $data['end_date'], $type];
    }
}
