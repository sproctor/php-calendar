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
namespace App\Doctrine;

use Doctrine\Migrations\AbstractMigration;

/**
 * Base for migrations, which name tables with table() so they get TABLE_PREFIX like the entities do
 * (see TablePrefixListener)
 */
abstract class PrefixedMigration extends AbstractMigration
{
    protected function table(string $name): string
    {
        return self::prefix() . $name;
    }

    public static function prefix(): string
    {
        return (string) ($_SERVER['TABLE_PREFIX'] ?? $_ENV['TABLE_PREFIX'] ?? getenv('TABLE_PREFIX') ?: '');
    }
}
