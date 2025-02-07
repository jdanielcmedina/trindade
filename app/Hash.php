<?php
/**
 * Trindade Framework
 *
 * Hash System
 * 
 * Handles cryptographic operations.
 * Provides secure hashing and verification tools.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

class Hash {
    /**
     * Default hashing algorithm
     * 
     * @var string
     */
    protected const DEFAULT_ALGO = PASSWORD_BCRYPT;
    
    /**
     * Default hashing options
     * 
     * @var array
     */
    protected const DEFAULT_OPTIONS = [
        'cost' => 12
    ];
    
    /**
     * Creates a password hash
     * 
     * Uses bcrypt by default with secure cost factor.
     * Automatically generates and manages salt.
     * 
     * @param string $password Password to hash
     * @param array $options Hashing options
     * @return string Hashed password
     */
    public function make(string $password, array $options = []): string {
        return password_hash(
            $password,
            self::DEFAULT_ALGO,
            array_merge(self::DEFAULT_OPTIONS, $options)
        );
    }
    
    /**
     * Verifies a password against a hash
     * 
     * @param string $password Password to verify
     * @param string $hash Hash to check against
     * @return bool Whether password matches hash
     */
    public function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Checks if hash needs rehashing
     * 
     * Determines if the hash was created with different options
     * or an older algorithm and needs to be updated.
     * 
     * @param string $hash Hash to check
     * @param array $options Hashing options to check against
     * @return bool Whether hash needs rehashing
     */
    public function needsRehash(string $hash, array $options = []): bool {
        return password_needs_rehash(
            $hash,
            self::DEFAULT_ALGO,
            array_merge(self::DEFAULT_OPTIONS, $options)
        );
    }
    
    /**
     * Creates a secure random string
     * 
     * @param int $length Length of string
     * @return string Random string
     */
    public function random(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Creates a hash of data using specified algorithm
     * 
     * @param string $data Data to hash
     * @param string $algo Hashing algorithm
     * @param bool $binary Whether to output raw binary data
     * @return string Hashed data
     */
    public function hash(string $data, string $algo = 'sha256', bool $binary = false): string {
        return hash($algo, $data, $binary);
    }
    
    /**
     * Creates an HMAC hash of data
     * 
     * @param string $data Data to hash
     * @param string $key Secret key
     * @param string $algo Hashing algorithm
     * @param bool $binary Whether to output raw binary data
     * @return string HMAC hash
     */
    public function hmac(string $data, string $key, string $algo = 'sha256', bool $binary = false): string {
        return hash_hmac($algo, $data, $key, $binary);
    }
    
    /**
     * Generates a secure random bytes string
     * 
     * @param int $length Number of bytes
     * @return string Random bytes
     * @throws \Exception If secure source of randomness is not available
     */
    public function bytes(int $length): string {
        return random_bytes($length);
    }
    
    /**
     * Gets information about a password hash
     * 
     * @param string $hash Hash to get info about
     * @return array Hash information
     */
    public function info(string $hash): array {
        return password_get_info($hash);
    }
    
    /**
     * Lists available hashing algorithms
     * 
     * @param bool $asString Whether to return as string
     * @return array|string Available algorithms
     */
    public function algorithms(bool $asString = false): array|string {
        $algos = hash_algos();
        return $asString ? implode(', ', $algos) : $algos;
    }
    
    /**
     * Generates a secure salt
     * 
     * @param int $length Salt length
     * @return string Generated salt
     */
    public function salt(int $length = 32): string {
        return base64_encode($this->bytes($length));
    }
    
    /**
     * Compares two strings in constant time
     * 
     * Prevents timing attacks when comparing hashes.
     * 
     * @param string $known_string The string of known length to compare against
     * @param string $user_string The user-supplied string
     * @return bool Whether strings are equal
     */
    public function equals(string $known_string, string $user_string): bool {
        return hash_equals($known_string, $user_string);
    }
}