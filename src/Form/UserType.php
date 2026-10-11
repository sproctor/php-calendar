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

use App\Entity\User;
use App\Service\LocaleService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A user account. The password is unmapped (the controller hashes it); when editing, it's optional and
 * leaving it blank keeps the current one.
 */
class UserType extends AbstractType
{
    public function __construct(private LocaleService $localeService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordConstraints = [new Length(min: 8, max: 4096)];
        if ($options['password_required']) {
            $passwordConstraints[] = new NotBlank();
        }
        $builder
            ->add('username', TextType::class, [
                'label' => new TranslatableMessage('username-label'),
                'attr' => ['autocomplete' => 'username'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $options['password_required'],
                'first_options' => [
                    'label' => new TranslatableMessage('password-label'),
                    'help' => $options['password_required'] ? null : new TranslatableMessage('keep-password-help'),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => new TranslatableMessage('confirm-password-label'),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'passwords-do-not-match-error',
                'constraints' => $passwordConstraints,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => new TranslatableMessage('timezone-label'),
                'required' => false,
                'placeholder' => new TranslatableMessage('calendar-timezone-label'),
            ])
            ->add('locale', ChoiceType::class, [
                'label' => new TranslatableMessage('default-language-label'),
                'choices' => $this->localeService->getLocaleChoices(),
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => new TranslatableMessage('browser-language-label'),
            ]);
        if ($options['include_admin']) {
            $builder->add('is_admin', CheckboxType::class, [
                'label' => new TranslatableMessage('make-admin-label'),
                'required' => false,
                'getter' => fn(User $user) => $user->isAdmin(),
                'setter' => fn(User $user, bool $value) => $user->setIsAdmin($value),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_required' => true,
            'include_admin' => false,
        ]);
    }
}
