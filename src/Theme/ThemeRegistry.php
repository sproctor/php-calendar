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

use App\Entity\Calendar;
use App\Repository\SettingRepository;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The themes to choose from: Bootstrap itself, the Bootswatch themes copied by bin/copy-assets, and
 * the admin's own in public/themes/ (see the README there). A calendar's theme, else the site's default
 * theme (Admin → Appearance), else Bootstrap, styles a page.
 */
class ThemeRegistry
{
    public const DEFAULT = 'bootstrap';

    /** @var array<string, Theme>|null */
    private ?array $themes = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicDir,
        private SettingRepository $settings,
        #[Autowire(service: 'cache.app')]
        private CacheInterface $cache,
    ) {
    }

    /**
     * @return array<string, Theme> by ID, in the order to list them
     */
    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }
        $themes = [self::DEFAULT => $this->theme(self::DEFAULT, 'Bootstrap', Theme::GROUP_DEFAULT, 'static/lib/bootstrap/bootstrap.min.css')];

        foreach (glob("$this->publicDir/static/lib/bootswatch/*/bootstrap.min.css") as $file) {
            $name = basename(dirname($file));
            $themes["bootswatch:$name"] = $this->theme("bootswatch:$name", ucfirst($name), Theme::GROUP_BOOTSWATCH,
                "static/lib/bootswatch/$name/bootstrap.min.css");
        }

        // One stylesheet, or a folder with bootstrap.min.css; an optional JSON file names it
        $custom = [];
        foreach (glob("$this->publicDir/themes/*.css") as $file) {
            $custom[basename($file, '.css')] = ['themes/' . basename($file), substr($file, 0, -4) . '.json'];
        }
        foreach (glob("$this->publicDir/themes/*/bootstrap.min.css") as $file) {
            $custom[basename(dirname($file))] = ['themes/' . basename(dirname($file)) . '/bootstrap.min.css', dirname($file) . '/theme.json'];
        }
        ksort($custom, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($custom as $name => [$stylesheet, $json]) {
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
                continue;
            }
            $meta = is_file($json) ? json_decode((string) file_get_contents($json), true) : null;
            $label = is_array($meta) && is_string($meta['name'] ?? null) && trim($meta['name']) !== '' ? trim($meta['name']) : $name;
            $themes["custom:$name"] = $this->theme("custom:$name", $label, Theme::GROUP_CUSTOM, $stylesheet);
        }

        return $this->themes = $themes;
    }

    public function get(?string $id): ?Theme
    {
        return $id === null ? null : ($this->all()[$id] ?? null);
    }

    public function getSiteDefault(): Theme
    {
        try {
            $id = $this->settings->getValue(SettingRepository::DEFAULT_THEME);
        } catch (DBALException) {
            // Before the installer or an upgrade has created the table
            $id = null;
        }

        return $this->get($id) ?? $this->all()[self::DEFAULT];
    }

    /**
     * The theme for a page about $calendar, or any other page when null. A theme whose files were
     * removed falls back to the next choice.
     */
    public function forCalendar(?Calendar $calendar): Theme
    {
        return $this->get($calendar?->getTheme()) ?? $this->getSiteDefault();
    }

    private function theme(string $id, string $name, string $group, string $stylesheet): Theme
    {
        $rtl = preg_replace('/\.min\.css$/', '.rtl.min.css', $stylesheet);
        $hasRtl = $rtl !== $stylesheet && is_file("$this->publicDir/$rtl");

        return new Theme($id, $name, $group, $stylesheet, $hasRtl ? $rtl : null, $this->isDark("$this->publicDir/$stylesheet"));
    }

    /**
     * Whether a stylesheet's normal (light mode) background is dark. Remembered until the file changes.
     */
    private function isDark(string $file): bool
    {
        $key = 'theme_dark_' . hash('xxh128', $file . "\0" . @filemtime($file));

        return $this->cache->get($key, function () use ($file): bool {
            $css = (string) @file_get_contents($file);
            if (!preg_match('/:root(?:,\[data-bs-theme=light\])?\{[^}]*?--bs-body-bg:\s*#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/', $css, $m)) {
                return false;
            }
            $hex = strlen($m[1]) === 3 ? preg_replace('/(.)/', '$1$1', $m[1]) : $m[1];
            [$r, $g, $b] = array_map(fn($c) => hexdec($c) / 255, str_split($hex, 2));
            // Relative luminance (WCAG); white is 1, black is 0
            $linear = fn($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

            return 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b) < 0.2;
        });
    }
}
