<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $max_subject_length = $options['maxlength'];
        $default_date = $options['date'];
        $end_datetime = $options['end'];
        $modifying = $options['modifying'];

        $builder->add(
            'subject',
            TextType::class,
            [
                'label' => new TranslatableMessage('subject-label'),
                'constraints' => new NotBlank(),
                'attr' => [
                    'autocomplete' => 'off',
                    'maxlength' => $max_subject_length,
                    'autofocus' => true,
                ]
            ]
        )
            ->add('description', TextareaType::class, ['required' => false])
            ->add(
                'start',
                DateTimeType::class,
                [
                    'label' => new TranslatableMessage('from-label'),
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text',
                    'data' => $default_date,
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'end',
                DateTimeType::class,
                [
                    'label' => new TranslatableMessage('to-label'),
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text',
                    'data' => $end_datetime,
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'start_date',
                DateType::class,
                [
                    'label' => new TranslatableMessage('from-label'),
                    'widget' => 'single_text',
                    'data' => $default_date,
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'end_date',
                DateType::class,
                [
                    'label' => new TranslatableMessage('to-label'),
                    'widget' => 'single_text',
                    'data' => $end_datetime,
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'time_type',
                ChoiceType::class,
                [
                    'label' => new TranslatableMessage('time-type-label'),
                    'choices' => [
                        'normal-label' => 0,
                        'full-day-label' => 1,
                        'to-be-announced-label' => 2,
                    ],
                    'choice_label' => function ($choice, $key) {
                        return new TranslatableMessage($key);
                    },
                    'mapped' => false,
                ]
            )
            ->add(
                'repeats',
                ChoiceType::class,
                [
                    'label' => new TranslatableMessage('repeats-label'),
                    'choices' => [
                        'never-label' => '0',
                        'daily-label' => 'D',
                        'weekly-label' => 'W',
                        'monthly-label' => 'M',
                        'yearly-label' => 'Y',
                    ],
                    'choice_label' => function ($choice, $key) {
                        return new TranslatableMessage($key);
                    },
                    'mapped' => false,
                ]
            )
            ->add(
                'frequency',
                IntegerType::class,
                [
                    'label' => new TranslatableMessage('frequency-label'),
                    'constraints' => new GreaterThan(0),
                    'data' => 1,
                    'mapped' => false,
                    ]
            )
            ->add(
                'until',
                DateType::class,
                [
                    'label' => new TranslatableMessage('until-label'),
                    'widget' => 'single_text',
                    'mapped' => false,
                ]
            )
            ->add(
                'delay_publish',
                CheckboxType::class,
                [
                    'label' => new TranslatableMessage('delay-publish-label'),
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'pubtime',
                DateTimeType::class,
                [
                    'label' => new TranslatableMessage('publish-date-time-label'),
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text',
                    'required' => false,
                ]
            );
        if ($modifying) {
            $builder->add(
                'modify',
                CheckboxType::class,
                [
                    'label' => new TranslatableMessage('change-event-date-time-label'),
                    'required' => false,
                    'mapped' => false,
                ]
            );
            $builder->add('eid', HiddenType::class);
        }
        $builder->add(
            'save',
            SubmitType::class,
            ['label' => new TranslatableMessage('save-label')]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'maxlength' => 50,
            'date' => new \DateTimeImmutable(),
            'end' => null,
            'modifying' => false,
        ]);
    }
}