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

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\FieldDefinition;
use App\Service\OccurrenceGenerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Creates or edits an event. The subject, description, category, read-only flag and publish time map to
 * the Event; custom fields (field_<fid>), the occurrence times, and the repeat rule are unmapped and
 * handled by the controller.
 */
class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Category[] $categories */
        $categories = $options['categories'];
        /** @var FieldDefinition[] $fields */
        $fields = $options['field_definitions'];

        $builder
            ->add('subject', TextType::class, [
                'label' => new TranslatableMessage('subject-label'),
                'constraints' => [new NotBlank(), new Length(max: $options['maxlength'])],
                'attr' => [
                    'autocomplete' => 'off',
                    'maxlength' => $options['maxlength'],
                    'autofocus' => true,
                ],
            ])
            ->add('location', TextType::class, [
                'label' => new TranslatableMessage('location-label'),
                'help' => new TranslatableMessage('location-help'),
                'required' => false,
                'attr' => ['maxlength' => 255],
            ])
            ->add('description', TextareaType::class, [
                'label' => new TranslatableMessage('description-label'),
                'required' => false,
                'empty_data' => '',
                'attr' => ['rows' => 6],
            ]);

        if ($categories !== []) {
            $builder->add('category', EntityType::class, [
                'label' => new TranslatableMessage('category-label'),
                'class' => Category::class,
                'choices' => $categories,
                'choice_label' => 'name',
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => new TranslatableMessage('no-category-label'),
            ]);
        }

        foreach ($fields as $definition) {
            $constraints = [];
            if ($definition->isRequired()) {
                $constraints[] = new NotBlank();
            }
            if ($definition->getPattern() !== null) {
                $constraints[] = new Regex(pattern: $definition->getPattern(), message: 'field-format-mismatch-error');
            }
            $builder->add('field_' . $definition->getFid(), TextType::class, [
                'label' => $definition->getName(),
                'translation_domain' => false,
                'required' => $definition->isRequired(),
                'mapped' => false,
                'constraints' => $constraints,
            ]);
        }

        if ($options['can_moderate']) {
            $builder->add('readonly', CheckboxType::class, [
                'label' => new TranslatableMessage('readonly-event-label'),
                'help' => new TranslatableMessage('readonly-event-help'),
                'required' => false,
            ]);
        }

        if ($options['modifying']) {
            $builder->add('modify', CheckboxType::class, [
                'label' => new TranslatableMessage('change-event-date-time-label'),
                'help' => new TranslatableMessage('change-event-date-time-help'),
                'required' => false,
                'mapped' => false,
            ]);
        }

        $builder
            ->add('times', OccurrenceTimesType::class, [
                'mapped' => false,
                'data' => $options['times'],
            ])
            ->add('repeats', ChoiceType::class, [
                'label' => new TranslatableMessage('repeats-label'),
                'choices' => [
                    'never-label' => OccurrenceGenerator::REPEAT_NEVER,
                    'daily-label' => OccurrenceGenerator::REPEAT_DAILY,
                    'weekly-label' => OccurrenceGenerator::REPEAT_WEEKLY,
                    'monthly-label' => OccurrenceGenerator::REPEAT_MONTHLY,
                    'yearly-label' => OccurrenceGenerator::REPEAT_YEARLY,
                ],
                'data' => $options['repeat'],
                'mapped' => false,
                'attr' => ['class' => 'phpc-repeats'],
            ])
            ->add('frequency', IntegerType::class, [
                'label' => new TranslatableMessage('frequency-label'),
                'constraints' => [new Range(min: 1, max: 365)],
                'data' => $options['frequency'],
                'mapped' => false,
                'attr' => ['min' => 1],
            ])
            ->add('until', DateType::class, [
                'label' => new TranslatableMessage('until-label'),
                'widget' => 'single_text',
                'required' => false,
                'mapped' => false,
                'input' => 'datetime_immutable',
                'data' => $options['until'],
            ])
            ->add('delay_publish', CheckboxType::class, [
                'label' => new TranslatableMessage('delay-publish-label'),
                'required' => false,
                'mapped' => false,
                'data' => $options['delay_publish'],
            ])
            ->add('pubtime', DateTimeType::class, [
                'label' => new TranslatableMessage('publish-date-time-label'),
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')]);
        if ($options['calendar'] !== null) {
            OverlapGuard::addField($builder, $options['calendar']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'maxlength' => 50,
            'categories' => [],
            'field_definitions' => [],
            'can_moderate' => false,
            'modifying' => false,
            'times' => null,
            'repeat' => OccurrenceGenerator::REPEAT_NEVER,
            'frequency' => 1,
            'until' => null,
            'delay_publish' => false,
            // For its overlap policy
            'calendar' => null,
        ]);
        $resolver->setRequired('times');
    }

    /**
     * Checks the rules that span several unmapped fields, adding errors to the submitted form
     */
    public static function addCrossFieldErrors(FormInterface $form, TranslatorInterface $translator): void
    {
        $changingTimes = !$form->has('modify') || $form->get('modify')->getData();
        if ($changingTimes && $form->get('repeats')->getData() !== OccurrenceGenerator::REPEAT_NEVER) {
            $until = $form->get('until')->getData();
            $times = $form->get('times')->getData();
            if ($until === null) {
                $form->get('until')->addError(new FormError($translator->trans('missing-until-error')));
            } elseif (is_array($times)) {
                [$start] = OccurrenceTimesType::toTimes($times);
                if ($start !== null && $until->format('Y-m-d') < $start->format('Y-m-d')) {
                    $form->get('until')->addError(new FormError($translator->trans('until-before-start-error')));
                }
            }
        }
        if ($form->get('delay_publish')->getData() && $form->get('pubtime')->getData() === null) {
            $form->get('pubtime')->addError(new FormError($translator->trans('missing-publish-time-error')));
        }
    }
}
