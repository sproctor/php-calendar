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

use App\Theme\Theme;
use App\Theme\ThemeRegistry;

/**
 * How an embedded calendar looks, from the query parameters embed.js passes on, so the frame can match
 * the page around it. Anything that doesn't validate is ignored: the values end up in a style element.
 */
final readonly class EmbedOptions
{
    public const MODES = ['light', 'dark', 'auto'];

    private const HEX = '/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i';
    // A font-family list: names, optionally quoted, separated by commas
    private const FONT = '/^\s*(?:"[\w -]+"|\'[\w -]+\'|[\w -]+)(?:\s*,\s*(?:"[\w -]+"|\'[\w -]+\'|[\w -]+))*\s*$/';

    /**
     * @param ?string $background "transparent" or a #rrggbb color
     * @param ?string $accent A #rrggbb color
     */
    public function __construct(
        public bool $header = true,
        public ?string $background = null,
        public ?string $accent = null,
        public ?string $font = null,
        public ?string $mode = null,
        public ?Theme $theme = null,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromQuery(array $query, ThemeRegistry $themes): self
    {
        $string = fn(string $name) => is_string($query[$name] ?? null) ? trim($query[$name]) : '';

        $background = $string('bg');
        $font = $string('font');
        $mode = $string('mode');

        return new self(
            header: $string('header') !== '0',
            background: $background === 'transparent' ? 'transparent' : self::color($background),
            accent: self::color($string('accent')),
            font: strlen($font) <= 200 && preg_match(self::FONT, $font) ? $font : null,
            mode: in_array($mode, self::MODES, true) ? $mode : null,
            theme: $themes->get($string('theme') ?: null),
        );
    }

    /**
     * CSS overriding the theme with the chosen background, accent and font, or '' when there are none.
     * Built only from validated values, so it's safe to output unescaped in a style element.
     */
    public function css(): string
    {
        // html[data-bs-theme] outranks the theme's own :root and [data-bs-theme=dark] rules
        $root = [];
        $rules = [];
        if ($this->background === 'transparent') {
            $root[] = '--bs-body-bg: transparent';
            $rules[] = 'body { background: transparent; }';
        } elseif ($this->background !== null) {
            $root[] = "--bs-body-bg: $this->background";
            $root[] = '--bs-body-bg-rgb: ' . self::rgb($this->background);
        }
        if ($this->accent !== null) {
            $rgb = self::rgb($this->accent);
            $text = self::isLight($this->accent) ? '#000' : '#fff';
            $root[] = "--bs-primary: $this->accent";
            $root[] = "--bs-primary-rgb: $rgb";
            $root[] = "--bs-link-color: $this->accent";
            $root[] = "--bs-link-color-rgb: $rgb";
            $root[] = "--bs-link-hover-color: $this->accent";
            $root[] = "--bs-link-hover-color-rgb: $rgb";
            $hover = "color-mix(in srgb, $this->accent 85%, #000)";
            $rules[] = ".btn-primary { --bs-btn-color: $text; --bs-btn-bg: $this->accent; --bs-btn-border-color: $this->accent;"
                . " --bs-btn-hover-color: $text; --bs-btn-hover-bg: $hover; --bs-btn-hover-border-color: $hover;"
                . " --bs-btn-active-color: $text; --bs-btn-active-bg: $hover; --bs-btn-active-border-color: $hover;"
                . " --bs-btn-disabled-color: $text; --bs-btn-disabled-bg: $this->accent; --bs-btn-disabled-border-color: $this->accent; }";
            $rules[] = ".bg-primary.text-white { color: $text !important; }";
        }
        if ($this->font !== null) {
            $root[] = "--bs-body-font-family: $this->font";
        }
        if ($root) {
            array_unshift($rules, 'html[data-bs-theme] { ' . implode('; ', $root) . '; }');
        }

        return implode("\n", $rules);
    }

    /**
     * "#rgb" or "rrggbb" as "#rrggbb", or null when it isn't a hex color
     */
    private static function color(string $value): ?string
    {
        if (!preg_match(self::HEX, $value, $match)) {
            return null;
        }
        $hex = strtolower($match[1]);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return "#$hex";
    }

    /**
     * "#rrggbb" as "r, g, b", for Bootstrap's *-rgb variables
     */
    public static function rgb(string $color): string
    {
        return implode(', ', array_map('hexdec', str_split(substr($color, 1), 2)));
    }

    /**
     * Whether dark text reads better than white on $color (WCAG relative luminance)
     */
    private static function isLight(string $color): bool
    {
        [$r, $g, $b] = array_map(function (string $part) {
            $c = hexdec($part) / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($color, 1), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b > 0.179;
    }
}
