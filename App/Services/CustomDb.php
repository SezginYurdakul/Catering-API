<?php

namespace App\Services;

use App\Plugins\Db\Db;
use PDO;
use PDOStatement;

class CustomDb extends Db {
    /**
     * Execute a SELECT query
     * @param string $query
     * @param array $bind
     * @return PDOStatement
     * @throws \Exception
     */
    public function executeSelectQuery(string $query, array $bind = []): bool|PDOStatement {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($bind);
    
            // Check if the query is a SELECT query
            if (stripos(trim($query), 'SELECT') === 0) {
                return $stmt; // Return PDOStatement for SELECT queries
            }
    
            // For non-SELECT queries, return true/false
            return true;
        } catch (\PDOException $e) {
            throw new \Exception('Failed to execute query: ' . $e->getMessage());
        }
    }
}