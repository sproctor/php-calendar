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
namespace App\Theme;

/**
 * A Bootstrap stylesheet the site can look like; see ThemeRegistry
 */
final readonly class Theme
{
    public const GROUP_DEFAULT = 'default';
    public const GROUP_BOOTSWATCH = 'bootswatch';
    public const GROUP_CUSTOM = 'custom';

    /**
     * @param string $stylesheet Path under public/, for asset()
     * @param ?string $rtlStylesheet The right-to-left version, if there is one
     * @param bool $darkOnly Designed with a dark background, so always shown in dark mode
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $group,
        public string $stylesheet,
        public ?string $rtlStylesheet,
        public bool $darkOnly,
    ) {
    }

    public function stylesheetFor(bool $rightToLeft): string
    {
        return $rightToLeft && $this->rtlStylesheet !== null ? $this->rtlStylesheet : $this->stylesheet;
    }
}
