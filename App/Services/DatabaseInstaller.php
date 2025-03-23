<?php

namespace App\Services;

use App\Plugins\Db\Db;

class DatabaseInstaller {
    private Db $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function run(string $createTablesFile, string $seedTablesFile): void {
        // Execute Create Table SQL script
        if (!file_exists($createTablesFile)) {
            throw new \Exception("SQL file not found: $createTablesFile");
        }
        $createTablesSql = file_get_contents($createTablesFile);
        $this->db->executeQuery($createTablesSql);

        // Execute Seed Table SQL script
        if (!file_exists($seedTablesFile)) {
            throw new \Exception("SQL file not found: $seedTablesFile");
        }
        $seedTablesSql = file_get_contents($seedTablesFile);
        $this->db->executeQuery($seedTablesSql);
    }
}