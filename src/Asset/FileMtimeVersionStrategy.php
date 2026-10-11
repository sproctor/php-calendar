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

namespace App\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Versions public assets by their modification time, so browsers fetch new copies after an upgrade
 */
class FileMtimeVersionStrategy implements VersionStrategyInterface
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicDir,
    ) {
    }

    public function getVersion(string $path): string
    {
        $file = $this->publicDir . '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');

        return is_file($file) ? (string) filemtime($file) : '';
    }

    public function applyVersion(string $path): string
    {
        $version = $this->getVersion($path);

        return $version === '' ? $path : $path . '?v=' . $version;
    }
}
