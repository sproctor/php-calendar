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

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Verifies the unsalted MD5 password hashes of PHP-Calendar 2.0 and earlier. 2.0 hashed the password
 * after escaping it for MySQL, so this does the same. Only used to check imported passwords, which are
 * re-hashed with the current algorithm on the next successful login.
 */
class LegacyMd5PasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        throw new \LogicException('MD5 is only supported for checking legacy passwords');
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if (!preg_match('/^[0-9a-f]{32}$/', $hashedPassword) || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return hash_equals($hashedPassword, md5(self::escape($plainPassword)));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return true;
    }

    /**
     * What mysqli_real_escape_string() does to a string
     */
    private static function escape(string $value): string
    {
        return strtr($value, [
            "\\" => "\\\\",
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "'" => "\\'",
            '"' => '\\"',
            "\x1a" => "\\Z",
        ]);
    }
}
