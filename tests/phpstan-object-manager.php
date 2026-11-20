<?php

/*
 * For PHPStan's Doctrine extension, which reads the entity mapping (to know, for example, that Doctrine
 * sets the IDs). Building the entity manager doesn't connect to the database.
 */

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$kernel = new Kernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
