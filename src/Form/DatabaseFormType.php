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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * The MySQL or MariaDB connection asked for by the installer, and the prefix for the calendar's tables
 */
class DatabaseFormType extends AbstractType
{
    /** Not 2.0's default, phpc_, so a 2.0 installation can share the database */
    public const DEFAULT_PREFIX = 'phpc3_';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('host', TextType::class, [
                'label' => new TranslatableMessage('database-host-label'),
                'help' => new TranslatableMessage('database-host-help'),
                'data' => 'localhost',
                'constraints' => [new NotBlank()],
            ])
            ->add('port', IntegerType::class, [
                'label' => new TranslatableMessage('database-port-label'),
                'data' => 3306,
                'constraints' => [new NotBlank(), new Range(min: 1, max: 65535)],
            ])
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('database-name-label'),
                'constraints' => [new NotBlank()],
            ])
            ->add('user', TextType::class, [
                'label' => new TranslatableMessage('database-user-label'),
                'constraints' => [new NotBlank()],
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add('password', PasswordType::class, [
                'label' => new TranslatableMessage('database-password-label'),
                'required' => false,
                'always_empty' => false,
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add('prefix', TextType::class, [
                'label' => new TranslatableMessage('table-prefix-label'),
                'help' => new TranslatableMessage('new-table-prefix-help'),
                'data' => self::DEFAULT_PREFIX,
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Regex('/^[a-z0-9_]{0,20}$/', message: 'table-prefix-error')],
            ])
            ->add('save', SubmitType::class, ['label' => new TranslatableMessage('connect-database-button')]);
    }
}
