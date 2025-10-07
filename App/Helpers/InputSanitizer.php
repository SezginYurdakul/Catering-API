<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Plugins\Di\Factory;

class InputSanitizer
{
    // General sanitization for all input data
    public static function sanitize(array $data): array
    {
        $logger = null;
        
        // Safely get logger if available
        try {
            if (class_exists('\App\Plugins\Di\Factory')) {
                $di = Factory::getDi();
                if ($di && $di->has('logger')) {
                    $logger = $di->getShared('logger');
                }
            }
        } catch (\Exception $e) {
            // Logger not available, continue without logging
        }
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Sanitize by encoding HTML entities (prevents XSS)
                // Preserves apostrophes, parentheses, and international characters
                $value = trim($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
                if ($logger) {
                    $logger->error("Unsupported data type for key '{$key}': " . gettype($value));
                }
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

    // URL sanitization
    public static function sanitizeUrl(string $value): ?string
    {
        $value = trim($value);
        $value = filter_var($value, FILTER_SANITIZE_URL);
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        return null;
    }

    // Text sanitization for rich content (allows more characters)
    public static function sanitizeText(string $value): string
    {
        $value = trim($value);
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    // Date sanitization and validation
    public static function sanitizeDate(string $value, string $format = 'Y-m-d'): ?string
    {
        $date = \DateTime::createFromFormat($format, trim($value));
        if ($date && $date->format($format) === trim($value)) {
            return $date->format($format);
        }
        return null;
    }

    // JSON sanitization
    public static function sanitizeJson(string $value): ?array
    {
        $decoded = json_decode(trim($value), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Use lighter sanitization for JSON to preserve structure
            return self::sanitizeJsonArray($decoded);
        }
        return null;
    }

    /**
     * Lighter sanitization for JSON arrays (only encodes HTML, doesn't strip characters)
     * @param array $data
     * @return array
     */
    private static function sanitizeJsonArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Only encode HTML entities, preserve all other characters
                $data[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $data[$key] = self::sanitizeJsonArray($value);
            }
            // Other types (int, float, bool) pass through unchanged
        }
        return $data;
    }
}
