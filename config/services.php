<?php

declare(strict_types=1);

namespace App\Services;

require_once '../vendor/autoload.php';

use App\Plugins;
use App\Plugins\Di\Factory;
use App\Helpers\Logger;
use Bramus\Router\Router;
use App\Services\CustomDb;
use Exception;
use App\Repositories\FacilityRepository;
use App\Repositories\LocationRepository;
use App\Repositories\TagRepository;

$di = Factory::getDi();
$config = require __DIR__ . '/config.php';

// Define the log directory and file
$logDir = __DIR__ . '/../logs';
$logFile = $logDir . '/api.log';

// Check if the logs directory exists, if not, create it
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true); // Create the directory with full permissions
}

// Check if the log file exists, if not, create it
if (!file_exists($logFile)) {
    file_put_contents($logFile, ''); // Create an empty log file
}

// Add Logger to the DI container
$di->setShared('logger', function () use ($logFile) {
    return new Logger($logFile);
});

// Add Router to the DI container
$di->setShared('router', function () {
    return new Router();
});

// Add Db to the DI container
$di->setShared('db', function () use ($config) {
    // Get the logger from the DI container
    $logger = Factory::getDi()->getShared('logger');

    try {
        // Fetch database configuration
        $dbConfig = $config['db'];

        // Validate required database configuration parameters
        $requiredParams = ['host', 'database', 'username', 'password'];
        foreach ($requiredParams as $param) {
            if (empty($dbConfig[$param])) {
                $logger->error("Database configuration is missing or invalid: {$param} is required.");
                throw new Exception("Database configuration is incomplete. Missing parameter: {$param}.");
            }
        }

        // Create the connection interface
        $connectionInterface = new Plugins\Db\Connection\Mysql(
            $dbConfig['host'],
            $dbConfig['database'],
            $dbConfig['username'],
            $dbConfig['password']
        );

        // Create the CustomDb instance
        $db = new CustomDb($connectionInterface);

        return $db;
    } catch (Exception $e) {
        $logger->error('Failed to initialize the database connection: ' . $e->getMessage());
        throw $e;
    }
});

// Add FacilityService, LocationService, and TagService to the DI container


$di->setShared('locationService', function () use ($di) {
    $db = $di->getShared('db');
    $locationRepository = new LocationRepository($db);
    return new \App\Services\LocationService($locationRepository);
});

$di->setShared('tagService', function () use ($di) {
    $db = $di->getShared('db');
    $tagRepository = new TagRepository($db);
    return new \App\Services\TagService($tagRepository);
});

$di->setShared('facilityService', function () use ($di) {
    $db = $di->getShared('db');
    $facilityRepository = new FacilityRepository($db);
    $locationService = $di->getShared('locationService');
    $tagService = $di->getShared('tagService');
    return new \App\Services\FacilityService($facilityRepository, $locationService, $tagService);
});