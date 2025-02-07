<?php
/**
 * Trindade Framework
 *
 * Error Handler Class
 * 
 * Handles all error and exception handling for the framework.
 * Provides centralized error management and logging.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

class ErrorHandler {
    /**
     * Initialize error handling
     * 
     * @return void
     */
    public static function initialize(): void {
        error_reporting(E_ALL);
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Custom error handler
     * 
     * @param int    $errno   Error number
     * @param string $errstr  Error message
     * @param string $errfile File where error occurred
     * @param int    $errline Line where error occurred
     * @return void
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void {
        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
        
        require __DIR__ . '/../views/errors/500.php';
        exit(1);
    }

    /**
     * Custom exception handler
     * 
     * @param \Throwable $e Exception object
     * @return void
     */
    public static function handleException(\Throwable $e): void {
        $error = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        
        require __DIR__ . '/../views/errors/500.php';
        exit(1);
    }
} 