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

namespace PhpCalendar;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class UserPermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $options['data'];
        $builder
            ->add('uid', HiddenType::class)
            ->add(
                'read',
                CheckboxType::class,
                ['label' => __('read-label'), 'data' => $data['read'], 'required' => false]
            )
            ->add(
                'write',
                CheckboxType::class,
                ['label' => __('write-label'), 'data' => $data['write'], 'required' => false]
            )
            ->add(
                'modify',
                CheckboxType::class,
                ['label' => __('modify-label'), 'data' => $data['modify'], 'required' => false]
            )
            ->add(
                'admin',
                CheckboxType::class,
                ['label' => __('admin-label'), 'data' => $data['admin'], 'required' => false]
            );
    }
}
