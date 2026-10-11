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
            ->add('title', TextType::class, ['label' => new TranslatableMessage('title-label')])
            ->add('subject_max', IntegerType::class, [
                'label' => new TranslatableMessage('max-subject-length-label'),
                'attr' => ['min' => 1, 'max' => 255],
            ])
            ->add('events_max', IntegerType::class, [
                'label' => new TranslatableMessage('max-events-display-label'),
                'attr' => ['min' => 1, 'max' => 255],
            ])
            ->add('anon_permission', ChoiceType::class, [
                'label' => new TranslatableMessage('public-permissions-label'),
                'help' => new TranslatableMessage('public-permissions-help'),
                'choices' => [
                    'no-read-no-write-events-label' => Calendar::ANON_NONE,
                    'read-no-write-events-label' => Calendar::ANON_READ,
                    'read-create-no-modify-events-label' => Calendar::ANON_CREATE,
                    'read-create-modify-events-label' => Calendar::ANON_MODIFY,
                ],
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => new TranslatableMessage('default-timezone-label'),
                'help' => new TranslatableMessage('default-timezone-help'),
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Calendar::class,
        ]);
    }
}
