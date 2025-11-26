<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Di\Factory;

class Validator
{
    private static function getLogger(): ?Logger
    {
        try {
            return Factory::getDi()->getShared('logger');
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function logValidationError(array $errors, string $context): void
    {
        $logger = self::getLogger();
        if ($logger) {
            $logger->logValidationError($errors, $context);
        }
    }

    public static function required(array $data, array $fields): void
    {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if ($errors) {
            self::logValidationError($errors, 'required_fields_validation');
            throw new ValidationException($errors);
        }
    }
    
    public static function positiveInt($value, string $field): void
    {
        if (!is_numeric($value) || $value <= 0) {
            $errors = [$field => ucfirst($field) . ' must be a positive integer'];
            self::logValidationError($errors, 'positive_int_validation');
            throw new ValidationException($errors);
        }
    }
    
    public static function pagination(array $params): void
    {
        $errors = [];
        
        if (isset($params['page']) && (!is_numeric($params['page']) || $params['page'] < 1)) {
            $errors['page'] = 'Page must be a positive integer';
        }
        
        if (isset($params['per_page']) && (!is_numeric($params['per_page']) || $params['per_page'] < 1 || $params['per_page'] > 100)) {
            $errors['per_page'] = 'Per page must be between 1 and 100';
        }
        
        if ($errors) {
            self::logValidationError($errors, 'pagination_validation');
            throw new ValidationException($errors);
        }
    }
    
    public static function allowedValues(array $values, array $allowed, string $field): void
    {
        $invalid = array_diff($values, $allowed);
        if ($invalid) {
            $errors = [
                $field => "Invalid $field: " . implode(', ', $invalid) . '. Allowed: ' . implode(', ', $allowed)
            ];
            self::logValidationError($errors, 'allowed_values_validation');
            throw new ValidationException($errors);
        }
    }
    
    public static function stringLength(string $value, string $field, int $min = 1, int $max = 255): void
    {
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            $errors = [
                $field => ucfirst($field) . " must be between $min and $max characters"
            ];
            self::logValidationError($errors, 'string_length_validation');
            throw new ValidationException($errors);
        }
    }
    
    public static function email(string $email, string $field = 'email'): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors = [$field => 'Invalid email format'];
            self::logValidationError($errors, 'email_validation');
            throw new ValidationException($errors);
        }
    }

    
    /**
     * Validate pagination and return errors array
     */
    public static function validatePagination(array $params): array
    {
        $errors = [];
        
        if (isset($params['page'])) {
            $page = InputSanitizer::sanitizeId($params['page']);
            if ($page === null || $page < 1) {
                $errors['page'] = 'Page must be a positive integer';
            }
        }
        
        if (isset($params['per_page'])) {
            $perPage = InputSanitizer::sanitizeId($params['per_page']);
            if ($perPage === null || $perPage < 1 || $perPage > 100) {
                $errors['per_page'] = 'Per page must be between 1 and 100';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate required fields and return errors array
     */
    public static function validateRequired(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }
    
    /**
     * Validate positive integer and return error or null
     */
    public static function validatePositiveInt($value, string $field): ?string
    {
        $sanitized = InputSanitizer::sanitizeId($value);
        if ($sanitized === null || $sanitized < 1) {
            return ucfirst($field) . ' must be a positive integer';
        }
        return null;
    }
    
    /**
     * Validate allowed values and return error or null
     */
    public static function validateAllowedValues(array $values, array $allowed, string $field): ?string
    {
        $invalid = array_diff($values, $allowed);
        if (!empty($invalid)) {
            return "Invalid $field: " . implode(', ', $invalid) . '. Allowed: ' . implode(', ', $allowed);
        }
        return null;
    }
    
    /**
     * Validate email and return error or null
     */
    public static function validateEmail(string $email, string $field = 'email'): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
        return null;
    }
    
    /**
     * Validate string length and return error or null
     */
    public static function validateStringLength(string $value, string $field, int $min = 1, int $max = 255): ?string
    {
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            return ucfirst($field) . " must be between $min and $max characters";
        }
        return null;
    }
    
    /**
     * Validate operator and return error or null
     */
    public static function validateOperator(string $operator): ?string
    {
        if (!in_array($operator, ['AND', 'OR'], true)) {
            return "Invalid operator. Only 'AND' or 'OR' are allowed.";
        }
        return null;
    }
}