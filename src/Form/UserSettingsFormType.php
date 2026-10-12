<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class UserSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('email-label', domain: 'account'),
                'help' => $options['mail_enabled']
                    ? new TranslatableMessage('email-confirm-help', domain: 'account')
                    : new TranslatableMessage('email-help', domain: 'account'),
                'required' => false,
                'constraints' => [new Email(), new Length(max: 180)],
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => new TranslatableMessage('timezone-label'),
                'required' => false,
                'placeholder' => new TranslatableMessage('calendar-timezone-label'),
            ])
            ->add(
                'locale',
                ChoiceType::class,
                [
                    'label' => new TranslatableMessage('default-language-label'),
                    'choices' => $options['locales'],
                    'choice_translation_domain' => false,
                    'required' => false,
                    'placeholder' => new TranslatableMessage('browser-language-label'),
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => new TranslatableMessage('save-label')]
            );
    }

    public function getBlockPrefix(): string
    {
        return 'user_settings';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'locales' => [],
            'mail_enabled' => false,
        ]);
    }
}