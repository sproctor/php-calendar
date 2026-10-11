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

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;

class LocaleService
{
    private ?array $mappings = null;

    public function __construct(
        private KernelInterface $kernel,
    ) {
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
                $this->mappings[$code] = Languages::getName($code, $code);
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
     * Picks the locale used to format dates and numbers. Translations come from the request's language,
     * which is only a language code, but the region decides details such as the first day of the
     * week. Uses the first of these whose language matches the request's and that names a region:
     * the user's locale setting, then the browser's Accept-Language preferences. Falls back to the
     * request's locale.
     */
    public function getFormattingLocale(Request $request, ?string $userLocale): string
    {
        $locale = $request->getLocale();
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
