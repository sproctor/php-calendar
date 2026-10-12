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

namespace App\Service;

use App\Entity\Calendar;
use App\Entity\User;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;

class LocaleService
{
    /** The language a visitor picked from the menu, remembered for pages whose URL doesn't name one */
    public const COOKIE = 'phpc_locale';

    private ?array $mappings = null;

    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    /** Languages written right to left */
    private const RTL_LANGUAGES = ['ar', 'ckb', 'dv', 'fa', 'he', 'ps', 'sd', 'ug', 'ur', 'yi'];

    public static function isRightToLeft(string $locale): bool
    {
        return in_array(\Locale::getPrimaryLanguage($locale), self::RTL_LANGUAGES, true);
    }

    /**
     * The languages that have translations, as language code => name in that language
     */
    public function getLocaleMappings(): array
    {
        if ($this->mappings === null) {
            $this->mappings = [];
            $finder = new Finder();

            foreach ($finder->name('*.yaml')->in($this->kernel->getProjectDir() . '/translations')->files() as $file) {
                preg_match('/[^.]\.(.+)/', $file->getFilenameWithoutExtension(), $matches);
                $code = $matches[1];
                // Codes are languages (e.g. "de"), or a language and region where the variants differ (e.g. "zh_TW")
                $this->mappings[$code] = Locales::getName($code, $code);
            }
            ksort($this->mappings);
        }

        return $this->mappings;
    }

    /**
     * Choices for a user's locale setting: each translated language, plus its regional variants,
     * which decide date formats and the first day of the week. Returned as name => locale code.
     */
    public function getLocaleChoices(): array
    {
        $choices = [];
        foreach (array_keys($this->getLocaleMappings()) as $language) {
            $choices[\Locale::getDisplayName($language)] = $language;
            foreach (Locales::getLocales() as $locale) {
                if (preg_match('/^' . preg_quote($language, '/') . '_[A-Z]{2}$/', $locale)) {
                    $choices[\Locale::getDisplayName($locale)] = $locale;
                }
            }
        }
        ksort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }

    /**
     * Picks the language for a page whose URL doesn't name one: the logged-in user's setting, then the
     * language this browser picked from the menu (the COOKIE), then $calendar's default language, then
     * the browser's Accept-Language preferences.
     */
    public function pickLanguage(Request $request, ?User $user, ?Calendar $calendar = null): string
    {
        $languages = array_keys($this->getLocaleMappings());
        foreach ([$user?->getLocale(), $request->cookies->get(self::COOKIE), $calendar?->getLocale()] as $choice) {
            if (is_string($choice) && $choice !== '') {
                $translation = $this->translationFor($choice);
                if ($translation !== null) {
                    return $translation;
                }
            }
        }

        // getPreferredLanguage() falls back to the first language when the browser sends no
        // Accept-Language header or none of its languages match, so make that English
        $languages = array_values(array_unique(['en', ...$languages]));

        return $request->getPreferredLanguage($languages) ?? 'en';
    }

    /**
     * The translation to use for $locale: the one for exactly that locale (e.g. "zh_TW"), else the one
     * for its language (e.g. "fr" for "fr_CA"), else null
     */
    public function translationFor(string $locale): ?string
    {
        $locale = \Locale::canonicalize($locale);
        $mappings = $this->getLocaleMappings();
        if (isset($mappings[$locale])) {
            return $locale;
        }
        $language = \Locale::getPrimaryLanguage($locale);
        $region = \Locale::getRegion($locale);
        if ($region !== '' && isset($mappings["{$language}_$region"])) {
            return "{$language}_$region";
        }

        return isset($mappings[$language]) ? $language : null;
    }

    public function isLanguage(string $language): bool
    {
        return array_key_exists($language, $this->getLocaleMappings());
    }

    /**
     * Picks the locale used to format dates and numbers. Translations come from the request's language,
     * which is only a language code, but the region decides details such as the first day of the
     * week. Uses the first of these whose language matches the request's and that names a region:
     * the user's locale setting, then the browser's Accept-Language preferences. Falls back to the
     * request's locale.
     */
    public function getFormattingLocale(Request $request, ?string $userLocale): string
    {
        $locale = $request->getLocale();
        // Translations for a region, e.g. "pt_BR", already say how to format
        if (\Locale::getRegion($locale) !== '') {
            return $locale;
        }
        $language = \Locale::getPrimaryLanguage($locale);

        $candidates = $request->getLanguages();
        if ($userLocale !== null && $userLocale !== '') {
            array_unshift($candidates, $userLocale);
        }
        foreach ($candidates as $candidate) {
            $candidate = \Locale::canonicalize($candidate);
            if (\Locale::getPrimaryLanguage($candidate) === $language && \Locale::getRegion($candidate) !== '') {
                return $candidate;
            }
        }

        return $locale;
    }
}
