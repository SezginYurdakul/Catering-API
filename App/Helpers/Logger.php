<?php

declare(strict_types=1);

namespace App\Helpers;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Log a message to the log file
     * 
     * @param string $level
     * @param string $message
     * @param array $context Additional context data
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $logData = [
            'timestamp' => $date,
            'level' => $level,
            'message' => $message,
            'request_id' => $this->getRequestId(),
            'user_ip' => $this->getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'context' => $this->sanitizeContext($context)
        ];

        $logMessage = json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        // Append the log message to the log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Generate unique request ID for tracking
     * @return string
     */
    private function getRequestId(): string
    {
        static $requestId = null;
        if ($requestId === null) {
            $requestId = uniqid('req_', true);
        }
        return $requestId;
    }
    
    /**
     * Get user IP address
     * @return string
     */
    private function getUserIP(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        return 'Unknown';
    }
    
    /**
     * Log error with exception details
     * @param \Exception $exception
     * @param array $context
     * @return void
     */
    public function logException(\Exception $exception, array $context = []): void
    {
        $context = array_merge($context, [
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $this->log('ERROR', $exception->getMessage(), $context);
    }
    
    /**
     * Log database errors
     * @param string $operation
     * @param string $query
     * @param string $error
     * @return void
     */
    public function logDatabaseError(string $operation, string $query, string $error): void
    {
        $context = [
            'operation' => $operation,
            'query' => $query,
            'database_error' => $error
        ];
        
        $this->log('ERROR', 'Database operation failed', $context);
    }
    
    /**
     * Log validation errors
     * @param array $validationErrors
     * @param string $endpoint
     * @return void
     */
    public function logValidationError(array $validationErrors, string $endpoint): void
    {
        $requestData = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];

        $context = [
            'validation_errors' => $validationErrors,
            'endpoint' => $endpoint,
            'request_data' => $this->sanitizeContext($requestData)
        ];

        $this->log('WARNING', 'Validation failed', $context);
    }

    /**
     * Sanitize context data to remove sensitive information (GDPR compliance)
     * @param array $context
     * @return array
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'old_password',
            'new_password',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'secret',
            'secret_key',
            'private_key',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'social_security',
            'pin',
            'authorization'
        ];

        return $this->recursiveSanitize($context, $sensitiveKeys);
    }

    /**
     * Recursively sanitize nested arrays
     * @param mixed $data
     * @param array $sensitiveKeys
     * @return mixed
     */
    private function recursiveSanitize($data, array $sensitiveKeys)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            // Check if key contains sensitive information (case-insensitive)
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->recursiveSanitize($value, $sensitiveKeys);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }


    /**
     * Log a DEBUG message to the log file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log an INFO message to the log file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log a WARNING message to the log file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log an ERROR message to the log file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log a CRITICAL message to the log file
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }
}
