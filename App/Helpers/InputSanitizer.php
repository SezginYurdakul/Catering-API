<?php

declare(strict_types=1);

namespace App\Helpers;

class InputSanitizer
{
    public static function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                // If the value is an array, recursively sanitize it
                $data[$key] = self::sanitize($value);
            } else {
                // If the value is not a string, do not sanitize it
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
