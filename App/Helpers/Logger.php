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
     * @return void
     */
    public function log(string $level, string $message): void
    {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] [$level] $message" . PHP_EOL;

        // Append the log message to the log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
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
