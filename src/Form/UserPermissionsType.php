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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

class UserPermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uid', HiddenType::class)
            ->add(
                'read',
                CheckboxType::class,
                ['label' => new TranslatableMessage('read-label'), 'required' => false]
            )
            ->add(
                'write',
                CheckboxType::class,
                ['label' => new TranslatableMessage('write-label'), 'required' => false]
            )
            ->add(
                'modify',
                CheckboxType::class,
                ['label' => new TranslatableMessage('modify-label'), 'required' => false]
            )
            ->add(
                'admin',
                CheckboxType::class,
                ['label' => new TranslatableMessage('admin-label'), 'required' => false]
            );
    }
}
