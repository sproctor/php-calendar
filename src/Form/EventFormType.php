<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'subject',
            TextType::class,
            [
                'label' => _('Subject'),
                'constraints' => new Assert\NotBlank(),
                'attr' => [
                    'autocomplete' => 'off',
                    'maxlength' => $context->calendar->getMaxSubjectLength(),
                    'autofocus' => ''
                ]
            ]
        )
            ->add('description', TextareaType::class, ['required' => false])
            ->add(
                'start',
                DateTimeType::class,
                ['label' => __('from-label'), 'date_widget' => 'single_text', 'time_widget' => 'single_text',
                    'data' => $default_date, 'required' => false]
            )
            ->add(
                'end',
                DateTimeType::class,
                ['label' => __('to-label'), 'date_widget' => 'single_text', 'time_widget' => 'single_text',
                    'data' => $end_datetime, 'required' => false]
            )
            ->add(
                'start_date',
                DateType::class,
                ['label' => __('from-label'), 'widget' => 'single_text', 'data' => $default_date, 'required' => false]
            )
            ->add(
                'end_date',
                DateType::class,
                ['label' => __('to-label'), 'widget' => 'single_text', 'data' => $end_datetime, 'required' => false]
            )
            ->add(
                'time_type',
                ChoiceType::class,
                ['label' => __('time-type-label'), 'choices' => [__('normal-label') => 0, __('full-day-label') => 1, __('to-be-announced-label') => 2]]
            )
            ->add(
                'repeats',
                ChoiceType::class,
                ['label' => __('repeats-label'), 'choices' => [__('never-label') => '0', __('daily-label') => 'D', __('weekly-label') => 'W', __('monthly-label') => 'M', __('yearly-label') => 'Y']]
            )
            ->add(
                'frequency',
                IntegerType::class,
                ['constraints' => new Assert\GreaterThan(0), 'data' => 1]
            )
            ->add('until', DateType::class, ['label' => __('until-label'), 'widget' => 'single_text'])
            ->add('delay_publish', CheckboxType::class, ['label' => __('delay-publish-label'), 'required' => false])
            ->add(
                'publish_datetime',
                DateTimeType::class,
                ['label' => __('publish-date-time-label'), 'date_widget' => 'single_text',
                    'time_widget' => 'single_text', 'required' => false]
            );
    }
}