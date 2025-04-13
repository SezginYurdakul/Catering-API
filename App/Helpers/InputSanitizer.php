<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Plugins\Di\Factory;

class InputSanitizer
{
    // General sanitization for all input data
    public static function sanitize(array $data): array
    {
        $logger = Factory::getDi()->getShared('logger');
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove unwanted special characters for general text fields
                $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                $value = preg_replace('/[^\w\s\-.,]/u', '', $value); // Allow letters, numbers, spaces, dashes, dots, and commas
                $data[$key] = $value;
            } elseif (is_int($value)) {
                $data[$key] = self::sanitizeId($value);
            } elseif (is_float($value)) {
                $data[$key] = self::sanitizeFloat($value);
            } elseif (is_bool($value)) {
                $data[$key] = self::sanitizeBool($value);
            } elseif (is_array($value)) {
                $data[$key] = self::sanitize($value); // Recursive sanitization
            } else {
                $logger->error("Unsupported data type for key '{$key}': " . gettype($value));
                $data[$key] = null; // Set to null for unsupported types
            }
        }
        return $data;
    }

    // Float validation and sanitization
    public static function sanitizeFloat($value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

    // Boolean validation and sanitization
    public static function sanitizeBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
    // ID valadation and sanitization
    public static function sanitizeId($value): ?int
    {
        if (is_numeric($value) && (int)$value > 0) {
            return (int)$value;
        }
        return null;
    }

    // Email validation and sanitization
    public static function sanitizeEmail(string $value): ?string
    {
        $value = trim($value);
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }
        return null;
    }

    // Phone number validation and sanitization
    // +31 6 12345678
    public static function sanitizePhone(string $value): ?string
    {
        $value = preg_replace('/[^\d+]/', '', $value);
        if (preg_match('/^\+?\d{7,15}$/', $value)) {
            return $value;
        }
        return null;
    }

    // Address validation and sanitization, free text 
    // but should not contain special characters
    // Example: "123 Anemone Lane, Amsterdam, 1012 AB"
    public static function sanitizeAddress(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
