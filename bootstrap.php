<?php
// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "vendor/autoload.php";

if (!file_exists(PHPC_CONFIG_FILE)) {
    throw new InvalidConfigException();
}
$config = include PHPC_CONFIG_FILE;

if (!isset($config["sql_host"])) {
    throw new InvalidConfigException();
}

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$db_config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode);
// or if you prefer yaml or XML
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

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
