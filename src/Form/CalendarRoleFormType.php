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
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class CalendarRoleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, ['label' => new TranslatableMessage('name-label')]);
        foreach (PermissionLabels::LABELS as $permission => $label) {
            $builder->add($permission, CheckboxType::class, [
                'label' => new TranslatableMessage($label),
                'required' => false,
                'getter' => fn(CalendarRole $role) => $role->{'can' . ucfirst($permission)}(),
                'setter' => fn(CalendarRole $role, bool $value) => $role->{'set' . ucfirst($permission)}($value),
            ]);
        }
        $builder
            ->add('users', EntityType::class, [
                'label' => new TranslatableMessage('members-label'),
                'class' => User::class,
                'choice_label' => 'username',
                'choice_translation_domain' => false,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'by_reference' => false,
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('save-label')]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarRole::class,
        ]);
    }
}
