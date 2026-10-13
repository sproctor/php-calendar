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

use App\Entity\CalendarRole;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class CategoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => new TranslatableMessage('name-label')])
            ->add('color', ColorType::class, ['label' => new TranslatableMessage('color-label')])
            ->add('marksDay', CheckboxType::class, [
                'label' => new TranslatableMessage('marks-day-label'),
                'help' => new TranslatableMessage('marks-day-help'),
                'required' => false,
            ])
            ->add('role', EntityType::class, [
                'label' => new TranslatableMessage('visible-to-role-label'),
                'help' => new TranslatableMessage('visible-to-role-help'),
                'class' => CalendarRole::class,
                'choices' => $options['roles'],
                'choice_label' => 'name',
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => new TranslatableMessage('everyone-label'),
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'roles' => [],
        ]);
    }
}
