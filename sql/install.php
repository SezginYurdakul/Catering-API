<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload all classes
require_once __DIR__ . '/../config/services.php'; // Include services.php

use App\Services\DatabaseInstaller;
use App\Plugins\Di\Factory;

try {
    // Get the Logger from the DI container
    $logger = Factory::getDi()->getShared('logger');
    $logger->info('Starting database installation process.');

    // Get the Db service from the DI container
    $db = Factory::getDi()->getShared('db');
    $logger->info('Database connection retrieved from DI container.');

    // Initialize the DatabaseInstaller
    $installer = new DatabaseInstaller($db);

    // Run the installer to create tables and seed data
    $installer->run(__DIR__ . '/create_tables.sql', __DIR__ . '/seed_tables.sql');

    echo "Database tables created and seeded successfully!";
    // Log the success message
    $logger->info('Database tables created and seeded successfully.');
} catch (PDOException $e) {
    // Handle any database errors
    echo "Error: " . $e->getMessage();
    // Log the database error
    $logger->error('Database Error: ' . $e->getMessage());
} catch (Exception $e) {
    // Handle any other errors
    echo "Error: " . $e->getMessage();
    // Log the error
    $logger->error('Error: ' . $e->getMessage());
}
