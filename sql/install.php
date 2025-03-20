<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload all classes

use App\Plugins\Db\Db;
use App\Plugins\Db\Connection\Mysql;
use Dotenv\Dotenv;

try {
    // Load environment variables from .env
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Get database credentials from environment variables
    $host = $_ENV['DB_HOST'];
    $dbName = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];

    // Create a MySQL connection instance
    $connection = new Mysql($host, $dbName, $username, $password);

    // Initialize the Db class with the connection
    $db = new Db($connection);

    // Read the Create Table SQL script file
    $sqlFile = __DIR__ . '/create_tables.sql';
    $sql = file_get_contents($sqlFile);

    // Execute the Create Table SQL script
    $db->executeQuery($sql);

    // Read Seed Tables SQL script file
    $sqlFile = __DIR__ . '/seed_tables.sql';
    $sql = file_get_contents($sqlFile);

    // Execute the Seed Tables SQL script
    $db->executeQuery($sql);

    echo "Database tables created successfully!";
} catch (PDOException $e) {
    // Handle any database errors
    echo "Error: " . $e->getMessage();
}
catch (Exception $e) {
    // Handle any other errors
    echo "Error: " . $e->getMessage();
}