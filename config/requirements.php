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

/*
 * Stops with an explanation when a PHP extension the app can't run without is missing, instead of
 * failing later with an error about a missing class. Included by public/index.php and bin/console
 * before anything else.
 */

$phpcMissingExtensions = array_values(array_filter(
    ['intl', 'pdo_mysql'],
    fn(string $extension) => !extension_loaded($extension),
));

if ($phpcMissingExtensions !== []) {
    $phpcList = implode(', ', $phpcMissingExtensions);
    $phpcHelp = "PHP-Calendar needs these PHP extensions, which aren't enabled: $phpcList.\n\n"
        . "On shared hosting, enable them in your control panel (in cPanel: \"Select PHP Version\" or\n"
        . "\"PHP Extensions\"), or ask your host. On Debian or Ubuntu, install the packages, e.g.\n"
        . 'sudo apt install ' . implode(' ', array_map(
            fn(string $extension) => 'php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-' . ($extension === 'pdo_mysql' ? 'mysql' : $extension),
            $phpcMissingExtensions,
        )) . "\nand restart the web server or PHP-FPM. On Windows, enable them in php.ini.\n";

    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        fwrite(STDERR, $phpcHelp);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>PHP-Calendar requirements</title></head>'
        . '<body><h1>PHP-Calendar can\'t start</h1><pre>' . htmlspecialchars($phpcHelp) . '</pre></body></html>';
    exit(1);
}
