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

    /**
     * The name Doctrine gives an unnamed index or foreign key on $table's $column: the prefix and the
     * CRC32 of the (prefixed) table and column names, as in AbstractAsset::_generateIdentifierName().
     * Migrations write these out so the schema matches what Doctrine expects from the entities.
     */
    protected function generatedName(string $prefix, string $table, string $column): string
    {
        $hash = implode('', array_map(fn(string $name) => dechex(crc32($name)), [$this->table($table), $column]));

        return strtoupper(substr($prefix . '_' . $hash, 0, 30));
    }

    public static function prefix(): string
    {
        return (string) ($_SERVER['TABLE_PREFIX'] ?? $_ENV['TABLE_PREFIX'] ?? getenv('TABLE_PREFIX') ?: '');
    }
}
