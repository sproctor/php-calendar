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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class CalendarFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('subject_max', IntegerType::class, ['label' => new TranslatableMessage('max-subject-length-label')])
            ->add('events_max', IntegerType::class, ['label' => new TranslatableMessage('max-events-display-label')])
            ->add(
                'anon_permission',
                ChoiceType::class,
                [
                    'label' => new TranslatableMessage('public-permissions-label'),
                    'choices' => [
                        'no-read-no-write-events-label' => 0,
                        'read-no-write-events-label' => 1,
                        'read-create-no-modify-events-label' => 2,
                        'read-create-modify-events-label' => 3,
                    ],
                    'choice_label' => function ($choice, $key) {
                        return new TranslatableMessage($key);
                    },
                    'mapped' => false,
                ]
            )
            ->add(
                'timezone',
                TimezoneType::class,
                ['label' => new TranslatableMessage('default-timezone-label'), 'preferred_choices' => ['America/New_York']]
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => new TranslatableMessage('save-label')]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Calendar::class,
        ]);
    }
}