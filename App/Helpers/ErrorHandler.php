<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Plugins\Http\ApiException;
use App\Plugins\Http\Response\InternalServerError;

/**
 * Simple centralized error handler
 */
class ErrorHandler
{
    /**
     * Handle all exceptions and errors
     * @param \Throwable $exception
     */
    public static function handle(\Throwable $exception): void
    {
        // Simple logging
        self::logError($exception);
        
        // Handle API exceptions (both old and new)
        if ($exception instanceof ApiException) {
            $exception->send();
            return;
        }
        
        // Handle unknown exceptions
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        
        if ($isDev) {
            // Development: Full details for debugging
            $errorData = [
                'error' => $exception->getMessage(),
                'error_type' => 'internal_error',
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stack_trace' => $exception->getTraceAsString(),
                'environment' => 'development'
            ];
        } else {
            // Production: Minimal, secure response
            $errorData = [
                'error' => 'An internal server error occurred',
                'error_type' => 'internal_error',
                'error_code' => 'ISE_' . time(), // Unique error ID for tracking
                'support_message' => 'Please contact support if this issue persists'
            ];
        }
        
        $response = new InternalServerError($errorData);
        $response->send();
    }
    
    /**
     * Simple error logging with fallback
     * @param \Throwable $exception
     */
    private static function logError(\Throwable $exception): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        // Primary: PHP error_log
        error_log($logMessage);
        
        // Secondary: Logger service (if available)
        try {
            if (class_exists('\App\Plugins\Di\Factory')) {
                $logger = \App\Plugins\Di\Factory::getDi()->getShared('logger');
                $logger->logException($exception);
            }
        } catch (\Exception $e) {
            // Fallback: Direct file logging
            try {
                $fallbackLog = defined('BASE_PATH') 
                    ? BASE_PATH . '/logs/error_fallback.log'
                    : __DIR__ . '/../../logs/error_fallback.log';
                    
                $fallbackMessage = sprintf(
                    "[%s] FALLBACK LOG - Logger service unavailable\n%s\nOriginal error: %s\n\n",
                    date('Y-m-d H:i:s'),
                    $logMessage,
                    $e->getMessage()
                );
                
                file_put_contents($fallbackLog, $fallbackMessage, FILE_APPEND);
            } catch (\Exception $fileError) {
                // Last resort: Just continue, error_log already called
            }
        }
    }
    
    /**
     * Register global handlers
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);
        
        set_error_handler(function($severity, $message, $file, $line) {
            $exception = new \ErrorException($message, 0, $severity, $file, $line);
            self::handle($exception);
        });
    }
}