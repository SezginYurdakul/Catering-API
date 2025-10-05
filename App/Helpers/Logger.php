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
            'context' => $context
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
        $context = [
            'validation_errors' => $validationErrors,
            'endpoint' => $endpoint,
            'request_data' => $_POST ?: file_get_contents('php://input')
        ];
        
        $this->log('WARNING', 'Validation failed', $context);
    }

    /**
     * Log an INFO message to the log file
     * 
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    /**
     * Log a WARNING message to the log file
     * 
     * @param string $message
     * @return void
     */
    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }
}
