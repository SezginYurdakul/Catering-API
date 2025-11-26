<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Plugins\Http\ApiException;
use App\Plugins\Http\Response\InternalServerError;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\NotFound;
use App\Domain\Exceptions\DomainException;
use App\Domain\Exceptions\DuplicateResourceException;
use App\Domain\Exceptions\ResourceNotFoundException;
use App\Domain\Exceptions\ResourceInUseException;
use App\Domain\Exceptions\InvalidOperationException;
use App\Domain\Exceptions\BusinessRuleViolationException;
use App\Domain\Exceptions\DatabaseException;

/**
 * Centralized error handler for all exceptions
 */
class ErrorHandler
{
    /**
     * Handle all exceptions and errors
     * @param \Throwable $exception
     */
    public static function handle(\Throwable $exception): void
    {
        // Log all errors
        self::logError($exception);

        // 1. Handle API exceptions (HTTP response wrappers)
        if ($exception instanceof ApiException) {
            $exception->send();
            exit;
        }

        // 2. Handle Domain exceptions (business logic errors)
        if ($exception instanceof DomainException) {
            $response = self::handleDomainException($exception);
            $response->send();
            exit;
        }

        // 3. Handle unknown exceptions
        $response = self::handleUnknownException($exception);
        $response->send();
        exit;
    }

    /**
     * Handle domain exceptions and map to appropriate HTTP responses
     * @param DomainException $exception
     * @return mixed
     */
    private static function handleDomainException(DomainException $exception)
    {
        $errorCode = $exception->getErrorCode();
        $context = $exception->getContext();
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';

        // Map domain exceptions to HTTP responses
        switch ($errorCode) {
            case 'DUPLICATE_RESOURCE':
            case 'RESOURCE_IN_USE':
            case 'INVALID_OPERATION':
            case 'BUSINESS_RULE_VIOLATION': {
                // 400 Bad Request - Client errors (validation/business rules)
                $response = [
                    'error' => $exception->getMessage(),
                    'error_type' => 'business_rule_violation',
                    'error_code' => $errorCode
                ];
                if ($isDev) {
                    $response['details'] = $context;
                }
                return new BadRequest($response);
            }

            case 'RESOURCE_NOT_FOUND': {
                // 404 Not Found
                $response = [
                    'error' => $exception->getMessage(),
                    'error_type' => 'resource_not_found',
                    'error_code' => $errorCode
                ];
                if ($isDev) {
                    $response['details'] = $context;
                }
                return new NotFound($response);
            }

            case 'DATABASE_ERROR':
            case 'EXTERNAL_SERVICE_ERROR': {
                // 500 Internal Server Error
                $errorData = [
                    'error' => 'An internal error occurred',
                    'error_type' => 'internal_error',
                    'error_code' => $errorCode,
                    'request_id' => self::generateRequestId()
                ];

                // In development, show more details
                if ($isDev) {
                    $errorData['debug'] = [
                        'message' => $exception->getMessage(),
                        'context' => $context
                    ];
                }

                return new InternalServerError($errorData);
            }

            default:
                // Unknown domain exception - treat as internal error
                return new InternalServerError([
                    'error' => 'An internal error occurred',
                    'error_type' => 'internal_error',
                    'error_code' => $errorCode,
                    'request_id' => self::generateRequestId()
                ]);
        }
    }

    /**
     * Handle unknown exceptions
     * @param \Throwable $exception
     * @return InternalServerError
     */
    private static function handleUnknownException(\Throwable $exception): InternalServerError
    {
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
                'request_id' => self::generateRequestId(),
                'support_message' => 'Please contact support if this issue persists'
            ];
        }

        return new InternalServerError($errorData);
    }

    /**
     * Log error with full details
     * @param \Throwable $exception
     */
    private static function logError(\Throwable $exception): void
    {
        // Determine severity based on exception type
        $severity = self::determineLogSeverity($exception);
        
        $logMessage = sprintf(
            "[%s] [%s] %s: %s in %s:%d",
            date('Y-m-d H:i:s'),
            $severity,
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
                
                // Use appropriate log method based on severity
                match($severity) {
                    'CRITICAL' => $logger->critical($exception->getMessage(), [
                        'exception' => get_class($exception),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString()
                    ]),
                    'ERROR' => $logger->error($exception->getMessage(), [
                        'exception' => get_class($exception),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine()
                    ]),
                    'WARNING' => $logger->warning($exception->getMessage(), [
                        'exception' => get_class($exception),
                        'context' => $exception instanceof DomainException ? $exception->getContext() : []
                    ]),
                    default => $logger->info($exception->getMessage())
                };
            }
        } catch (\Exception $e) {
            // Fallback: Direct file logging
            self::fallbackFileLog($logMessage, $e);
        }

        // Debug log (can be removed in production)
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            self::writeDebugLog($exception);
        }
    }

    /**
     * Determine log severity based on exception type
     * @param \Throwable $exception
     * @return string
     */
    private static function determineLogSeverity(\Throwable $exception): string
    {
        // Critical: Database and external service errors
        if ($exception instanceof DatabaseException) {
            return 'CRITICAL';
        }

        // Error: System errors and unknown exceptions
        if ($exception instanceof \PDOException || 
            $exception instanceof \ErrorException ||
            !($exception instanceof DomainException)) {
            return 'ERROR';
        }

        // Warning: Business rule violations
        if ($exception instanceof DuplicateResourceException ||
            $exception instanceof ResourceInUseException ||
            $exception instanceof BusinessRuleViolationException ||
            $exception instanceof InvalidOperationException) {
            return 'WARNING';
        }

        // Info: Not found (common, not critical)
        if ($exception instanceof ResourceNotFoundException) {
            return 'INFO';
        }

        return 'ERROR';
    }

    /**
     * Fallback file logging when logger service unavailable
     * @param string $logMessage
     * @param \Exception $loggerException
     */
    private static function fallbackFileLog(string $logMessage, \Exception $loggerException): void
    {
        try {
            $fallbackLog = defined('BASE_PATH')
                ? BASE_PATH . '/logs/error_fallback.log'
                : __DIR__ . '/../../logs/error_fallback.log';

            $fallbackMessage = sprintf(
                "[%s] FALLBACK LOG - Logger service unavailable\n%s\nLogger error: %s\n\n",
                date('Y-m-d H:i:s'),
                $logMessage,
                $loggerException->getMessage()
            );

            file_put_contents($fallbackLog, $fallbackMessage, FILE_APPEND);
        } catch (\Exception $fileError) {
            // Last resort: Just continue, error_log already called
        }
    }

    /**
     * Write detailed debug log (development only)
     * @param \Throwable $exception
     */
    private static function writeDebugLog(\Throwable $exception): void
    {
        try {
            $debugLog = defined('BASE_PATH')
                ? BASE_PATH . '/logs/debug.log'
                : __DIR__ . '/../../logs/debug.log';

            $debugMessage = sprintf(
                "[%s] ErrorHandler called\n" .
                "Type: %s\n" .
                "Message: %s\n" .
                "File: %s\n" .
                "Line: %d\n" .
                "Stack Trace:\n%s\n" .
                "---\n",
                date('Y-m-d H:i:s'),
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            );

            file_put_contents($debugLog, $debugMessage, FILE_APPEND);
        } catch (\Exception $e) {
            // Ignore debug log errors
        }
    }

    /**
     * Generate unique request ID for tracking
     * @return string
     */
    private static function generateRequestId(): string
    {
        return 'REQ_' . bin2hex(random_bytes(8)) . '_' . time();
    }

    /**
     * Register global handlers
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);

        set_error_handler(function ($severity, $message, $file, $line) {
            // Convert PHP errors to exceptions
            $exception = new \ErrorException($message, 0, $severity, $file, $line);
            self::handle($exception);
        });

        // Shutdown handler for fatal errors
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $exception = new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                );
                self::handle($exception);
            }
        });
    }
}