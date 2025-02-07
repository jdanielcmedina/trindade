<?php
/**
 * Trindade Framework
 *
 * File System
 * 
 * Handles file operations and uploads.
 * Provides secure file management and storage.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

/**
 * File Class - File and upload management
 * 
 * @package Trindade
 * @property string $uploadsPath Base path for uploads
 */
class File {
    protected string $uploadsPath;
    
    public function __construct(string $uploadsPath) {
        $this->uploadsPath = rtrim($uploadsPath, '/');
        
        if (!is_dir($this->uploadsPath)) {
            mkdir($this->uploadsPath, 0777, true);
        }
    }
    
    /**
     * Uploads a file
     *
     * @param array $file $_FILES array element
     * @param string $directory Target subdirectory
     * @return array|null File info or null on failure
     */
    public function upload(array $file, string $directory = ''): ?array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $directory = trim($directory, '/');
        $targetDir = $this->uploadsPath . ($directory ? '/' . $directory : '');
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . ($extension ? '.' . $extension : '');
        $targetPath = $targetDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'name' => $file['name'],
                'path' => $targetPath,
                'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath),
                'size' => $file['size'],
                'type' => $file['type'],
                'extension' => $extension
            ];
        }
        
        return null;
    }
    
    /**
     * Downloads a file
     *
     * @param string $path File path
     * @param string|null $filename Custom filename for download
     * @throws \RuntimeException When file not found
     * @return void
     */
    public function download(string $path, string $filename = null): void {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }
        
        $filename = $filename ?: basename($path);
        $mimeType = mime_content_type($path);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');
        readfile($path);
        exit;
    }
    
    public function delete(string $path): bool {
        if (file_exists($path) && is_file($path)) {
            return unlink($path);
        }
        return false;
    }
    
    /**
     * Checks if file exists
     *
     * @param string $path File path
     * @return bool Whether file exists
     */
    public function exists(string $path): bool {
        return file_exists($path) && is_file($path);
    }
    
    /**
     * Gets file size
     *
     * @param string $path File path
     * @return int File size in bytes
     */
    public function size(string $path): int {
        return file_exists($path) ? filesize($path) : 0;
    }
    
    public function extension(string $path): string {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
    
    public function mimeType(string $path): string {
        return mime_content_type($path);
    }
    
    public function move(string $source, string $destination): bool {
        return rename($source, $destination);
    }
    
    public function copy(string $source, string $destination): bool {
        return copy($source, $destination);
    }
    
    public function read(string $path): ?string {
        return file_exists($path) ? file_get_contents($path) : null;
    }
    
    public function write(string $path, string $content): bool {
        return file_put_contents($path, $content) !== false;
    }
    
    public function append(string $path, string $content): bool {
        return file_put_contents($path, $content, FILE_APPEND) !== false;
    }
}