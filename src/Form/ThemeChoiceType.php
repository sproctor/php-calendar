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

use App\Theme\Theme;
use App\Theme\ThemeRegistry;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A theme from ThemeRegistry, grouped by where it comes from. phpc.js previews the selected theme on the
 * page. With `site_default` true, it can also be left empty to use the site's default theme.
 */
class ThemeChoiceType extends AbstractType
{
    public function __construct(
        private ThemeRegistry $registry,
        private TranslatorInterface $translator,
        private Packages $packages,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $groups = [
            Theme::GROUP_DEFAULT => null,
            Theme::GROUP_BOOTSWATCH => 'theme-group-bootswatch',
            Theme::GROUP_CUSTOM => 'theme-group-custom',
        ];
        $choices = [];
        foreach ($this->registry->all() as $theme) {
            $label = $theme->darkOnly ? $this->translator->trans('dark-theme-name', ['%theme%' => $theme->name]) : $theme->name;
            $group = $groups[$theme->group];
            if ($group === null) {
                $choices[$label] = $theme->id;
            } else {
                $choices[$this->translator->trans($group)][$label] = $theme->id;
            }
        }
        $default = $this->registry->getSiteDefault();

        $resolver->setDefaults([
            'site_default' => false,
            'choices' => $choices,
            'choice_translation_domain' => false,
            'choice_attr' => fn(?string $id) => $this->previewAttributes($this->registry->get($id)),
            'required' => fn(Options $options) => !$options['site_default'],
            'placeholder' => fn(Options $options) => $options['site_default']
                ? $this->translator->trans('site-default-theme-option', ['%theme%' => $default->name])
                : null,
            'attr' => fn(Options $options) => ['data-phpc-theme-preview' => ''] + ($options['site_default']
                ? array_combine(
                    array_map(fn($key) => str_replace('data-', 'data-default-', $key), array_keys($this->previewAttributes($default))),
                    $this->previewAttributes($default),
                )
                : []),
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    private function previewAttributes(?Theme $theme): array
    {
        if ($theme === null) {
            return [];
        }

        return array_filter([
            'data-stylesheet' => $this->packages->getUrl($theme->stylesheet),
            'data-stylesheet-rtl' => $theme->rtlStylesheet !== null ? $this->packages->getUrl($theme->rtlStylesheet) : null,
            'data-dark' => $theme->darkOnly ? '1' : null,
        ]);
    }
}
