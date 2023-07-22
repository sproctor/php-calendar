<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

class UserSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('timezone', TimezoneType::class, [
                'label' => new TranslatableMessage('timezone-label')
            ])
            ->add('locale', LocaleType::class, [
                'label' => new TranslatableMessage('locale-label')
            ])
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
}