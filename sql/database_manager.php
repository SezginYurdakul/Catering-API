<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Services\CustomDb;
use App\Plugins\Db\Connection\Mysql;
use PDO;

/**
 * Database Management Tool
 * Provides organized commands for database operations
 */

if ($argc < 2) {
    showHelp();
    exit(1);
}

$command = strtolower($argv[1]);

try {
    // Create database connection using the same method as services.php
    $config = require __DIR__ . '/../config/config.php';
    $dbConfig = $config['db'];
    
    switch ($command) {
        case 'create-db':
            echo "🏗️  Creating database...\n";
            // Connect to information_schema to create database
            $connectionInterface = new Mysql(
                $dbConfig['host'],
                'information_schema',
                $dbConfig['username'],
                $dbConfig['password']
            );
            $db = new CustomDb($connectionInterface);
            
            // Execute CREATE DATABASE directly
            $pdo = $db->getConnection();
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS catering_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "✅ Database created successfully!\n";
            } catch (Exception $e) {
                throw new Exception("Failed to create database: " . $e->getMessage());
            }
            break;
            
        case 'create-tables':
            echo "📋 Creating tables...\n";
            $connectionInterface = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $db = new CustomDb($connectionInterface);
            executeSQLFile($db, '02_create_tables.sql');
            echo "✅ Tables created successfully!\n";
            break;
            
        case 'seed-data':
            echo "🌱 Seeding sample data...\n";
            $connectionInterface = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $db = new CustomDb($connectionInterface);
            executeSQLFile($db, '03_seed_tables.sql');
            echo "✅ Sample data loaded successfully!\n";
            break;
            
        case 'clear-data':
            echo "🗑️  Clearing all data...\n";
            $connectionInterface = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $db = new CustomDb($connectionInterface);
            executeSQLFile($db, '04_clear_data.sql');
            echo "✅ All data cleared successfully!\n";
            break;
            
        case 'drop-tables':
            echo "💥 Dropping all tables...\n";
            if (!confirmAction("This will delete ALL tables and data. Are you sure?")) {
                echo "❌ Operation cancelled.\n";
                exit(0);
            }
            $connectionInterface = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $db = new CustomDb($connectionInterface);
            executeSQLFile($db, '05_drop_tables.sql');
            echo "✅ All tables dropped successfully!\n";
            break;
            
        case 'setup':
            echo "🚀 Setting up complete database (create-db + create-tables + seed-data)...\n";
            
            // Create database via information_schema connection
            $infoConnection = new Mysql(
                $dbConfig['host'],
                'information_schema',
                $dbConfig['username'],
                $dbConfig['password']
            );
            $infoDb = new CustomDb($infoConnection);
            $pdo = $infoDb->getConnection();
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS catering_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo "  ✅ Database created\n";
            } catch (Exception $e) {
                throw new Exception("Failed to create database: " . $e->getMessage());
            }
            
            // Now create new connection to the created database
            $cateringConnection = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $cateringDb = new CustomDb($cateringConnection);
            
            executeSQLFile($cateringDb, '02_create_tables.sql');
            echo "  ✅ Tables created\n";
            executeSQLFile($cateringDb, '03_seed_tables.sql');
            echo "  ✅ Sample data loaded\n";
            echo "🎉 Complete setup finished!\n";
            break;
            
        case 'reset':
            echo "🔄 Resetting database (clear-data + seed-data)...\n";
            
            // Execute clear data
            $clearConnection = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $clearDb = new CustomDb($clearConnection);
            executeSQLFile($clearDb, '04_clear_data.sql');
            echo "  ✅ Data cleared\n";
            
            // Use fresh connection for seed data
            $seedConnection = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $seedDb = new CustomDb($seedConnection);
            executeSQLFile($seedDb, '03_seed_tables.sql');
            echo "  ✅ Sample data loaded\n";
            
            echo "🎉 Database reset completed!\n";
            break;
            
        case 'status':
            $connectionInterface = new Mysql(
                $dbConfig['host'],
                $dbConfig['database'],
                $dbConfig['username'],
                $dbConfig['password']
            );
            $db = new CustomDb($connectionInterface);
            showDatabaseStatus($db);
            break;
            
        default:
            echo "❌ Unknown command: $command\n\n";
            showHelp();
            exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Execute SQL file
 */
function executeSQLFile(CustomDb $db, string $filename): void {
    $filepath = __DIR__ . '/' . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception("SQL file not found: $filename");
    }
    
    $sql = file_get_contents($filepath);
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $filename");
    }
    
    executeSQLScript($db, $sql);
}

/**
 * Execute SQL script with multiple statements
 */
function executeSQLScript(CustomDb $db, string $sql): void {
    // Get PDO connection for direct execution
    $pdo = $db->getConnection();
    
    // Enable buffered queries to avoid "unbuffered queries" error
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    // Remove comments and clean the SQL
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    $sql = trim($sql);
    
    if (empty($sql)) {
        return;
    }
    
    // For complex multi-line statements, use MySQL's exec directly
    // Split by semicolon but handle multi-line statements properly
    $statements = preg_split('/;\s*(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            
            // Show a concise debug message
            $firstLine = explode("\n", $statement)[0];
            echo "  DEBUG: Executed: " . substr($firstLine, 0, 60) . "...\n";
            
        } catch (Exception $e) {
            echo "  ERROR: Failed statement: " . substr($statement, 0, 100) . "...\n";
            echo "  ERROR: " . $e->getMessage() . "\n";

            // Treat a few known non-fatal errors as warnings and continue seeding.
            // This helps the setup flow complete when seed files are not fully idempotent
            // (for example, duplicate rows or minor pre-existence issues).
            $msg = $e->getMessage();
            $isNonFatal = false;

            // Common non-fatal MySQL messages we can ignore during seeding
            if (strpos($msg, 'already exists') !== false) {
                $isNonFatal = true;
            }
            if (strpos($msg, "doesn't exist") !== false) {
                $isNonFatal = true;
            }
            if (strpos($msg, 'Duplicate entry') !== false) {
                // Duplicate primary/unique key when inserting seed rows - skip
                $isNonFatal = true;
            }

            if ($isNonFatal) {
                echo "  ⚠️ Non-fatal error during seed; continuing.\n";
                continue;
            }

            throw new Exception("Failed to execute: " . substr($statement, 0, 100) . "... Error: " . $e->getMessage());
        }
    }
}

/**
 * Show database status
 */
function showDatabaseStatus(CustomDb $db): void {
    echo "📊 Database Status:\n";
    echo "┌─────────────────────┬─────────┐\n";
    echo "│ Table               │ Count   │\n";
    echo "├─────────────────────┼─────────┤\n";
    
    $tables = ['Facilities', 'Locations', 'Tags', 'Facility_Tags', 'Employees', 'Employee_Facility'];
    
    foreach ($tables as $table) {
        $result = $db->executeSelectQuery("SELECT COUNT(*) as count FROM $table");
        $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
        echo sprintf("│ %-19s │ %7s │\n", $table, $count);
    }
    
    echo "└─────────────────────┴─────────┘\n\n";
    
    // Show recent facilities
    echo "📋 Recent Facilities:\n";
    $result = $db->executeSelectQuery(
        "SELECT f.name, l.city 
         FROM Facilities f 
         JOIN Locations l ON f.location_id = l.id 
         ORDER BY f.id DESC 
         LIMIT 5"
    );
    
    $facilities = $result->fetchAll(PDO::FETCH_ASSOC);
    if (empty($facilities)) {
        echo "  No facilities found.\n";
    } else {
        foreach ($facilities as $facility) {
            echo "  • {$facility['name']} ({$facility['city']})\n";
        }
    }
}

/**
 * Confirm user action
 */
function confirmAction(string $message): bool {
    echo $message . " (y/N): ";
    $input = trim(fgets(STDIN));
    return strtolower($input) === 'y' || strtolower($input) === 'yes';
}

/**
 * Show help information
 */
function showHelp(): void {
    echo "Database Management Tool\n";
    echo "========================\n\n";
    echo "Usage: php database_manager.php <command>\n\n";
    echo "Available commands:\n";
    echo "  create-db      Create the database\n";
    echo "  create-tables  Create all tables\n";
    echo "  seed-data      Load sample data into tables\n";
    echo "  clear-data     Clear all data from tables (keep structure)\n";
    echo "  drop-tables    Drop all tables (DELETE EVERYTHING!)\n";
    echo "  setup          Complete setup (create-db + create-tables + seed-data)\n";
    echo "  reset          Reset data (clear-data + seed-data)\n";
    echo "  status         Show database status and table counts\n\n";
    echo "Examples:\n";
    echo "  php database_manager.php setup     # Fresh install\n";
    echo "  php database_manager.php reset     # Reset with fresh data\n";
    echo "  php database_manager.php status    # Check current state\n";
}