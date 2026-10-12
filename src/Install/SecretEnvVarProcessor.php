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
namespace App\Install;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * %env(app_secret:APP_SECRET)%: APP_SECRET, or when it's empty, a random secret kept in var/secret.
 * A new install has no APP_SECRET until the web installer writes one, but signing (logins, links)
 * needs a secret from the first page; a fixed fallback would let anyone forge them.
 */
class SecretEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/secret')]
        private string $file,
    ) {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $value = (string) $getEnv($name);
        if ($value !== '') {
            return $value;
        }

        $saved = is_file($this->file) ? trim((string) file_get_contents($this->file)) : '';
        if ($saved !== '') {
            return $saved;
        }
        $secret = bin2hex(random_bytes(32));
        $temporary = $this->file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($temporary, $secret) === false) {
            throw new \RuntimeException("APP_SECRET is empty and {$this->file} can't be written");
        }
        chmod($temporary, 0600);
        // Hard-linking fails if another request saved one first; then use theirs
        if (@link($temporary, $this->file)) {
            @unlink($temporary);
        } elseif (is_file($this->file)) {
            @unlink($temporary);

            return trim((string) file_get_contents($this->file));
        } else {
            // No hard links on this filesystem
            rename($temporary, $this->file);
        }

        return $secret;
    }

    public static function getProvidedTypes(): array
    {
        return ['app_secret' => 'string'];
    }
}
