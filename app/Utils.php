<?php
/**
 * Trindade Framework
 *
 * Utilities System
 * 
 * Collection of utility functions and helpers.
 * Provides common operations and formatting tools.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

class Utils {
    /**
     * Generates a random string
     * 
     * @param int $length Length of the string
     * @param string $chars Characters to use
     * @return string Random string
     */
    public static function randomString(
        int $length = 32,
        string $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        $str = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }
        
        return $str;
    }
    
    /**
     * Generates a random token
     * 
     * @param int $length Token length
     * @return string Random token
     */
    public static function token(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Slugifies a string
     * 
     * @param string $text Text to slugify
     * @param string $separator Word separator
     * @return string Slugified text
     */
    public static function slug(string $text, string $separator = '-'): string {
        // Convert to lowercase
        $text = mb_strtolower($text);
        
        // Replace special characters
        $text = str_replace(
            ['à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ'],
            ['a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y'],
            $text
        );
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Replace spaces with separator
        $text = preg_replace('~[-\s]+~', $separator, $text);
        
        // Remove leading/trailing separator
        return trim($text, $separator);
    }
    
    /**
     * Formats a number as currency
     * 
     * @param float $number Number to format
     * @param string $currency Currency code
     * @param string $locale Locale for formatting
     * @return string Formatted currency
     */
    public static function formatCurrency(
        float $number,
        string $currency = 'EUR',
        string $locale = 'pt_PT'
    ): string {
        $fmt = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($number, $currency);
    }
    
    /**
     * Formats a date
     * 
     * @param string|int|\DateTime $date Date to format
     * @param string $format Date format
     * @param string $locale Locale for formatting
     * @return string Formatted date
     */
    public static function formatDate(
        $date,
        string $format = 'd/m/Y',
        string $locale = 'pt_PT'
    ): string {
        if (is_string($date)) {
            $date = strtotime($date);
        }
        
        if (is_int($date)) {
            $date = new \DateTime('@' . $date);
        }
        
        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL
        );
        
        $formatter->setPattern($format);
        return $formatter->format($date);
    }
    
    /**
     * Validates an email address
     * 
     * @param string $email Email to validate
     * @return bool Whether email is valid
     */
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validates a URL
     * 
     * @param string $url URL to validate
     * @return bool Whether URL is valid
     */
    public static function isValidUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validates an IP address
     * 
     * @param string $ip IP to validate
     * @param int $flags Validation flags
     * @return bool Whether IP is valid
     */
    public static function isValidIp(string $ip, int $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6): bool {
        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }
    
    /**
     * Gets client IP address
     * 
     * @return string|null Client IP or null if not found
     */
    public static function getClientIp(): ?string {
        $ip = $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? null;
            
        return $ip && self::isValidIp($ip) ? $ip : null;
    }
    
    /**
     * Truncates text to a maximum length
     * 
     * @param string $text Text to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix for truncated text
     * @return string Truncated text
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return rtrim(mb_substr($text, 0, $length)) . $suffix;
    }
    
    /**
     * Formats file size in human readable format
     * 
     * @param int $bytes Size in bytes
     * @param int $precision Number of decimal places
     * @return string Formatted size
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Gets file extension
     * 
     * @param string $filename Filename
     * @return string File extension
     */
    public static function getFileExtension(string $filename): string {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Checks if string starts with substring
     * 
     * @param string $haystack String to search in
     * @param string $needle String to search for
     * @return bool Whether string starts with substring
     */
    public static function startsWith(string $haystack, string $needle): bool {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
    
    /**
     * Checks if string ends with substring
     * 
     * @param string $haystack String to search in
     * @param string $needle String to search for
     * @return bool Whether string ends with substring
     */
    public static function endsWith(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
    
    /**
     * Checks if string contains substring
     * 
     * @param string $haystack String to search in
     * @param string $needle String to search for
     * @return bool Whether string contains substring
     */
    public static function contains(string $haystack, string $needle): bool {
        return strpos($haystack, $needle) !== false;
    }
    
    /**
     * Converts array to object
     * 
     * @param array $array Array to convert
     * @return object Converted object
     */
    public static function arrayToObject(array $array): object {
        return json_decode(json_encode($array));
    }
    
    /**
     * Converts object to array
     * 
     * @param object $object Object to convert
     * @return array Converted array
     */
    public static function objectToArray(object $object): array {
        return json_decode(json_encode($object), true);
    }
    
    /**
     * Gets value from array using dot notation
     * 
     * @param array $array Array to search in
     * @param string $key Key in dot notation
     * @param mixed $default Default value
     * @return mixed Found value or default
     */
    public static function arrayGet(array $array, string $key, $default = null) {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    /**
     * Sets value in array using dot notation
     * 
     * @param array $array Array to modify
     * @param string $key Key in dot notation
     * @param mixed $value Value to set
     * @return array Modified array
     */
    public static function arraySet(array &$array, string $key, $value): array {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
        
        return $array;
    }
    
    /**
     * Removes value from array using dot notation
     * 
     * @param array $array Array to modify
     * @param string $key Key in dot notation
     * @return void
     */
    public static function arrayForget(array &$array, string $key): void {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }
            
            $array = &$array[$key];
        }
        
        unset($array[array_shift($keys)]);
    }
}