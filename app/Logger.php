<?php
/**
 * Trindade Framework
 *
 * Logger System
 * 
 * Handles application logging operations.
 * Provides multiple log levels and file management.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

/**
 * Logger Class - Logging system with levels
 * 
 * @package Trindade
 * @property string $path Log files path
 * @property array $levels Available logging levels
 */
class Logger {
    /**
     * Log levels
     * 
     * @var array
     */
    protected const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600
    ];
    
    /**
     * Log file path
     * 
     * @var string
     */
    protected $path;
    
    /**
     * Current log level
     * 
     * @var int
     */
    protected $level;
    
    /**
     * Constructor
     * 
     * Initializes logger with path and minimum log level.
     * Creates log directory if it doesn't exist.
     * 
     * @param string $path Log file path
     * @param string $level Minimum log level
     * @throws \RuntimeException If log directory creation fails
     */
    public function __construct(string $path, string $level = 'debug') {
        $this->path = rtrim($path, '/');
        $this->level = self::LEVELS[strtolower($level)] ?? self::LEVELS['debug'];
        
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0777, true)) {
                throw new \RuntimeException("Failed to create log directory: {$this->path}");
            }
        }
    }
    
    /**
     * Logs a debug message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Logs an info message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }
    
    /**
     * Logs a notice message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function notice(string $message, array $context = []): void {
        $this->log('notice', $message, $context);
    }
    
    /**
     * Logs a warning message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function warning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Logs an error message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }
    
    /**
     * Logs a critical message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function critical(string $message, array $context = []): void {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Logs an alert message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function alert(string $message, array $context = []): void {
        $this->log('alert', $message, $context);
    }
    
    /**
     * Logs an emergency message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function emergency(string $message, array $context = []): void {
        $this->log('emergency', $message, $context);
    }
    
    /**
     * Logs a message with specified level
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void {
        $level = strtolower($level);
        
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }
        
        if (self::LEVELS[$level] >= $this->level) {
            $this->write($level, $message, $context);
        }
    }
    
    /**
     * Writes log entry to file
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     * @throws \RuntimeException If writing to log file fails
     */
    protected function write(string $level, string $message, array $context = []): void {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $file = "{$this->path}/{$date}.log";
        
        $entry = sprintf(
            "[%s] %s.%s: %s %s\n",
            $time,
            strtoupper($level),
            str_pad('', 9 - strlen($level)),
            $message,
            empty($context) ? '' : json_encode($context)
        );
        
        if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException("Failed to write to log file: {$file}");
        }
    }
    
    /**
     * Gets log entries for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @return array Log entries
     */
    public function getLogEntries(string $date): array {
        $file = "{$this->path}/{$date}.log";
        
        if (!file_exists($file)) {
            return [];
        }
        
        $entries = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] (.*?)\.(\s*): (.*?)( {.*})?$/', $line, $matches)) {
                $entries[] = [
                    'time' => $matches[1],
                    'level' => strtolower($matches[2]),
                    'message' => $matches[4],
                    'context' => isset($matches[5]) ? json_decode($matches[5], true) : []
                ];
            }
        }
        
        return $entries;
    }
    
    /**
     * Clears log files older than specified days
     * 
     * @param int $days Number of days to keep
     * @return int Number of files deleted
     */
    public function clearOldLogs(int $days): int {
        $deleted = 0;
        $threshold = strtotime("-{$days} days");
        
        foreach (glob("{$this->path}/*.log") as $file) {
            $date = str_replace('.log', '', basename($file));
            
            if (strtotime($date) < $threshold) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Gets current log level
     * 
     * @return string Current log level
     */
    public function getLevel(): string {
        return array_search($this->level, self::LEVELS);
    }
    
    /**
     * Sets minimum log level
     * 
     * @param string $level Log level
     * @return self
     * @throws \InvalidArgumentException If level is invalid
     */
    public function setLevel(string $level): self {
        $level = strtolower($level);
        
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }
        
        $this->level = self::LEVELS[$level];
        return $this;
    }
    
    /**
     * Gets log file path
     * 
     * @return string Log file path
     */
    public function getPath(): string {
        return $this->path;
    }
}