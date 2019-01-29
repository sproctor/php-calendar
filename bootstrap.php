<?php
// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Debug\Debug;

require_once "vendor/autoload.php";

// TODO: Change this for production
$isDevMode = true;
if ($isDevMode) {
    Debug::enable();
    error_reporting(-1);
    ini_set('display_errors', '1');
}

// Verify our config file exists and is valid
if (!file_exists(PHPC_CONFIG_FILE)) {
    throw new InvalidConfigException();
}
$config = include PHPC_CONFIG_FILE;
if (!isset($config["sql_host"])) {
    throw new InvalidConfigException();
}

// Create a Doctrine ORM configuration for Annotations
$db_config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode);
$db_config->addEntityNamespace('PHPC', 'PhpCalendar');

// database configuration parameters
$conn = array(
    'dbname' => $config["sql_database"],
    'user' => $config["sql_user"],
    'password' => $config["sql_passwd"],
    'host' => $config["sql_host"],
    'driver' => 'pdo_mysql',
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $db_config);
