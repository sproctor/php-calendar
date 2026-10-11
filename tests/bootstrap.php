<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Rebuild the test database from the migrations once per run
$console = escapeshellarg(dirname(__DIR__).'/bin/console');
foreach ([
    'doctrine:database:drop --force --if-exists',
    'doctrine:database:create',
    'doctrine:migrations:migrate --no-interaction',
] as $command) {
    passthru("php $console --env=test --quiet $command", $status);
    if ($status !== 0) {
        throw new RuntimeException("Test database setup failed: $command");
    }
}
