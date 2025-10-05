<?php

declare(strict_types=1);

namespace App\Services;

use App\Plugins\Db\Db;
use PDOStatement;

/** CustomDb class extends the Db class from the Db plugin
 * This class provides custom methods for executing SELECT queries and getting the last inserted ID as an integer
 */
class CustomDb extends Db
{
    /**
     * Execute a SELECT query
     * 
     * @param string $query
     * @param array $bind
     * @return PDOStatement
     * @throws \Exception
     */
    public function executeSelectQuery(string $query, array $bind = []): bool|PDOStatement
    {
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

    /**
     * Get the last inserted ID as an integer.
     *
     * @param mixed $name
     * @return int
     * @throws \Exception
     */
    public function getLastInsertedIdAsInt($name = null): int
    {
        try {
            $id = $this->getLastInsertedId($name);

            return (int) $id;
        } catch (\PDOException $e) {
            throw new \Exception('Failed to get last inserted ID as integer: ' . $e->getMessage());
        }
    }
}
