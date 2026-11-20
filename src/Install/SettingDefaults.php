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

use Symfony\Component\Dotenv\Dotenv;

/**
 * Symfony loads .env.local.php instead of .env, not on top of it, and upgrades keep the .env.local.php
 * the installer wrote. A site installed before a setting existed would have no value for it, and every
 * request would fail, so settings missing from .env.local.php get their default from .env.
 */
final class SettingDefaults
{
    public static function apply(string $projectDir): void
    {
        // Without .env.local.php, Symfony has already loaded .env
        if (!is_file("$projectDir/.env.local.php") || !is_file("$projectDir/.env")) {
            return;
        }
        $defaults = (new Dotenv())->parse((string) file_get_contents("$projectDir/.env"), "$projectDir/.env");
        foreach ($defaults as $name => $value) {
            if (!isset($_SERVER[$name]) && !isset($_ENV[$name]) && getenv($name) === false) {
                $_SERVER[$name] = $_ENV[$name] = $value;
            }
        }
    }
}
