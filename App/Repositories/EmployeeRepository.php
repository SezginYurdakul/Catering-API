<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\CustomDb;
use App\Helpers\Logger;
use PDO;

class EmployeeRepository
{
    private $db;
    private ?Logger $logger;

    public function __construct(CustomDb $db, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Get all employees with pagination.
     *
     * @param int $perPage
     * @param int $offset
     * @return array
     * @throws \Exception
     */
    public function getEmployees(string $whereClause, array $bind, int $perPage, int $offset): array
    {
        try {
            $query = "
                SELECT
                    e.id,
                    e.name,
                    e.address,
                    e.phone,
                    e.email,
                    e.created_at
                FROM
                    Employees e
                LEFT JOIN Employee_Facility ef ON e.id = ef.employee_id
                LEFT JOIN Facilities f ON ef.facility_id = f.id
                LEFT JOIN Locations l ON f.location_id = l.id
                WHERE $whereClause
                GROUP BY e.id, e.name, e.address, e.phone, e.email, e.created_at
                ORDER BY e.name, e.id
                LIMIT $perPage OFFSET $offset
            ";

            $stmt = $this->db->executeSelectQuery($query, $bind);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getEmployees', $e->getMessage());
            }
            throw new \Exception("Failed to fetch employees: " . $e->getMessage());
        }
    }

    /**
     * Get an employee by ID.
     *
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getEmployeeById(int $id): ?array
    {
        try {
            $query = "
                SELECT 
                    id, name, address, phone, email, created_at
                FROM 
                    Employees
                WHERE id = :id
            ";

            $stmt = $this->db->executeSelectQuery($query, ['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getEmployeeById', $e->getMessage());
            }
            throw new \Exception("Failed to fetch employee: " . $e->getMessage());
        }
    }

    /**
     * Get facility IDs for an employee.
     *
     * @param int $employeeId
     * @return array
     * @throws \Exception
     */
    public function getFacilityIdsByEmployeeId(int $employeeId): array
    {
        try {
            $query = "
                SELECT facility_id
                FROM Employee_Facility
                WHERE employee_id = :employeeId
            ";

            $stmt = $this->db->executeSelectQuery($query, ['employeeId' => $employeeId]);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $results ?: [];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getFacilityIdsByEmployeeId', $e->getMessage());
            }
            throw new \Exception("Failed to fetch employee facilities: " . $e->getMessage());
        }
    }

    /**
     * Count total employees.
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalEmployeesCount(): int
    {
        try {
            $query = "SELECT COUNT(*) FROM Employees";
            $stmt = $this->db->executeSelectQuery($query, []);
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getTotalEmployeesCount', $e->getMessage());
            }
            throw new \Exception("Failed to count employees: " . $e->getMessage());
        }
    }

    /**
     * Count employees based on provided filters.
     *
     * @param string $whereClause
     * @param array $bind
     * @return int
     * @throws \Exception
     */
    public function getFilteredEmployeesCount(string $whereClause, array $bind): int
    {
        try {
            $query = "
                SELECT COUNT(DISTINCT e.id) AS total
                FROM Employees e
                LEFT JOIN Employee_Facility ef ON e.id = ef.employee_id
                LEFT JOIN Facilities f ON ef.facility_id = f.id
                LEFT JOIN Locations l ON f.location_id = l.id
                WHERE $whereClause
            ";

            $stmt = $this->db->executeSelectQuery($query, $bind);
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getFilteredEmployeesCount', $e->getMessage());
            }
            throw new \Exception("Failed to count filtered employees: " . $e->getMessage());
        }
    }

    /**
     * Create a new employee.
     *
     * @param array $data
     * @return int Employee ID
     * @throws \Exception
     */
    public function createEmployee(array $data): int
    {
        try {
            $query = "
                INSERT INTO Employees (name, address, phone, email)
                VALUES (:name, :address, :phone, :email)
            ";

            $this->db->executeQuery($query, $data);
            return (int) $this->db->getLastInsertedIdAsInt();
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('INSERT', 'createEmployee', $e->getMessage());
            }
            throw new \Exception("Failed to create employee: " . $e->getMessage());
        }
    }

    /**
     * Update an employee.
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateEmployee(int $id, array $data): bool
    {
        try {
            $query = "
                UPDATE Employees
                SET name = :name, address = :address, phone = :phone, email = :email
                WHERE id = :id
            ";

            $data['id'] = $id;
            $affectedRows = $this->db->executeQuery($query, $data);
            return $affectedRows > 0;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('UPDATE', 'updateEmployee', $e->getMessage());
            }
            throw new \Exception("Failed to update employee: " . $e->getMessage());
        }
    }

    /**
     * Delete an employee.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteEmployee(int $id): bool
    {
        try {
            // First, check if the employee exists
            $exists = $this->getEmployeeById($id);
            if (!$exists) {
                return false;
            }
            $query = "DELETE FROM Employees WHERE id = :id";
            $result = $this->db->executeQuery($query, ['id' => $id]);
            // Since executeQuery returns bool, check for success
            return $result === true;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('DELETE', 'deleteEmployee', $e->getMessage());
            }
            throw new \Exception("Failed to delete employee: " . $e->getMessage());
        }
    }

    /**
     * Add employee-facility relationships.
     *
     * @param int $employeeId
     * @param array $facilityIds
     * @return void
     * @throws \Exception
     */
    public function addEmployeeFacilities(int $employeeId, array $facilityIds): void
    {
        try {
            // Delete existing relationships
            $deleteQuery = "DELETE FROM Employee_Facility WHERE employee_id = :employeeId";
            $this->db->executeQuery($deleteQuery, ['employeeId' => $employeeId]);

            // Insert new relationships
            if (!empty($facilityIds)) {
                $insertQuery = "INSERT INTO Employee_Facility (employee_id, facility_id) VALUES (:employeeId, :facilityId)";
                foreach ($facilityIds as $facilityId) {
                    $this->db->executeQuery($insertQuery, [
                        'employeeId' => $employeeId,
                        'facilityId' => $facilityId
                    ]);
                }
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('INSERT', 'addEmployeeFacilities', $e->getMessage());
            }
            throw new \Exception("Failed to add employee facilities: " . $e->getMessage());
        }
    }

    /**
     * Get an employee by email.
     *
     * @param string $email
     * @return array|null
     * @throws \Exception
     */
    public function getEmployeeByEmail(string $email): ?array
    {
        try {
            $query = "SELECT id, name, address, phone, email, created_at FROM Employees WHERE email = :email";
            $stmt = $this->db->executeSelectQuery($query, [':email' => $email]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getEmployeeByEmail', $e->getMessage());
            }
            throw new \Exception("Failed to fetch employee by email: " . $e->getMessage());
        }
    }

    /**
     * Check if an employee email is unique.
     *
     * @param string $email
     * @return bool
     * @throws \Exception
     */
    public function isEmployeeEmailUnique(string $email): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM Employees WHERE email = :email";
            $stmt = $this->db->executeSelectQuery($query, [':email' => $email]);
            return (int) $stmt->fetchColumn() === 0;
        } catch (\Exception $e) {
            throw new \Exception("Failed to check if employee email '$email' is unique: " . $e->getMessage());
        }
    }
    /**
     * Check if an employee email is unique for update (excluding self).
     *
     * @param string $email
     * @param int $excludeId
     * @return bool
     * @throws \Exception
     */

    public function isEmployeeEmailUniqueForUpdate(string $email, int $excludeId): bool
    {
        $query = "SELECT COUNT(*) FROM Employees WHERE email = :email AND id != :id";
        $stmt = $this->db->executeSelectQuery($query, [':email' => $email, ':id' => $excludeId]);
        return (int) $stmt->fetchColumn() === 0;
    }
}
