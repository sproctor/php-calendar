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

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Which version of PHP-Calendar is running: the release in the VERSION file that bin/build-release
 * writes, or for a git checkout, its branch and commit (e.g. "main@8e6a918")
 */
class AppVersion
{
    private ?string $version = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function get(): string
    {
        return $this->version ??= $this->release() ?? $this->checkout() ?? 'unknown';
    }

    private function release(): ?string
    {
        $file = "$this->projectDir/VERSION";
        $version = is_file($file) ? trim((string) file_get_contents($file)) : '';

        return $version !== '' ? $version : null;
    }

    /**
     * Reads .git directly, since web hosts often can't run git
     */
    private function checkout(): ?string
    {
        $git = "$this->projectDir/.git";
        if (is_file($git) && preg_match('/^gitdir: (.+)$/m', (string) file_get_contents($git), $m)) {
            // A worktree: .git names its own git directory, and branches live in the main one
            $git = str_starts_with($m[1], '/') ? $m[1] : "$this->projectDir/$m[1]";
        }
        $head = @file_get_contents("$git/HEAD");
        if ($head === false) {
            return null;
        }
        $head = trim($head);
        if (!str_starts_with($head, 'ref: ')) {
            return substr($head, 0, 7);
        }

        $ref = substr($head, 5);
        $common = is_file("$git/commondir") ? "$git/" . trim((string) file_get_contents("$git/commondir")) : $git;
        $hash = @file_get_contents("$git/$ref") ?: @file_get_contents("$common/$ref") ?: null;
        if ($hash === null && is_file("$common/packed-refs")
            && preg_match('/^([0-9a-f]{40}) ' . preg_quote($ref, '/') . '$/m', (string) file_get_contents("$common/packed-refs"), $m)) {
            $hash = $m[1];
        }
        $branch = preg_replace('%^refs/heads/%', '', $ref);

        return $hash === null ? $branch : $branch . '@' . substr(trim($hash), 0, 7);
    }
}
