<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Plugins\Http\Exceptions\ValidationException;

/**
 * Simple validator - all validation logic in one place
 */
class Validator
{
    /**
     * Validate required fields
     */
    public static function required(array $data, array $fields): void
    {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if ($errors) {
            throw new ValidationException($errors);
        }
    }
    
    /**
     * Validate positive integer
     */
    public static function positiveInt($value, string $field): void
    {
        if (!is_numeric($value) || $value <= 0) {
            throw new ValidationException([$field => ucfirst($field) . ' must be a positive integer']);
        }
    }
    
    /**
     * Validate pagination
     */
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
            throw new ValidationException($errors);
        }
    }
    
    /**
     * Validate allowed values
     */
    public static function allowedValues(array $values, array $allowed, string $field): void
    {
        $invalid = array_diff($values, $allowed);
        if ($invalid) {
            throw new ValidationException([
                $field => "Invalid $field: " . implode(', ', $invalid) . '. Allowed: ' . implode(', ', $allowed)
            ]);
        }
    }
    
    /**
     * Validate string length
     */
    public static function stringLength(string $value, string $field, int $min = 1, int $max = 255): void
    {
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            throw new ValidationException([
                $field => ucfirst($field) . " must be between $min and $max characters"
            ]);
        }
    }
    
    /**
     * Validate email format
     */
    public static function email(string $email, string $field = 'email'): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException([$field => 'Invalid email format']);
        }
    }
}