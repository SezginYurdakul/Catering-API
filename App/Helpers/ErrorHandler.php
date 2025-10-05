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
     * Handle all exceptions
     * @param \Exception $exception
     */
    public static function handle(\Exception $exception): void
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
     * Simple error logging
     * @param \Exception $exception
     */
    private static function logError(\Exception $exception): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        error_log($logMessage);
        
        // Also log to API log file if logger service exists
        try {
            if (class_exists('\App\Plugins\Di\Factory')) {
                $logger = \App\Plugins\Di\Factory::getDi()->getShared('logger');
                $logger->log('ERROR', $exception->getMessage(), [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]);
            }
        } catch (\Exception $e) {
            // Logger not available, continue
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