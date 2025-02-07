<?php
/**
 * Trindade Framework
 *
 * Database Class
 * 
 * Database wrapper for Medoo with additional functionality.
 * Provides a simple interface for database operations.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

use PDO;
use Exception;
use PDOException;
use PDOStatement;
use InvalidArgumentException;
use Medoo\Medoo;

/**
 * The Database raw object.
 */
class Raw
{
    /**
     * The array of mapping data for the raw string.
     *
     * @var array
     */
    public $map;

    /**
     * The raw string.
     *
     * @var string
     */
    public $value;
}

/**
 * @method array select(string $table, array $columns)
 * @method mixed select(string $table, string $column)
 * @method array select(string $table, array $columns, array $where)
 * @method mixed select(string $table, string $column, array $where)
 * @method array select(string $table, array $join, array $columns)
 * @method mixed select(string $table, array $join, string $column)
 * @method null select(string $table, array $columns, callable $callback)
 * @method null select(string $table, string $column, callable $callback)
 * @method null select(string $table, array $columns, array $where, callable $callback)
 * @method null select(string $table, string $column, array $where, callable $callback)
 * @method null select(string $table, array $join, array $columns, array $where, callable $callback)
 * @method null select(string $table, array $join, string $column, array $where, callable $callback)
 * @method mixed get(string $table, array|string $columns, array $where)
 * @method bool has(string $table, array $where)
 * @method mixed rand(string $table, array|string $column, array $where)
 * @method int count(string $table, array $where)
 * @method string max(string $table, string $column)
 * @method string min(string $table, string $column)
 * @method string avg(string $table, string $column)
 * @method string sum(string $table, string $column)
 * @method string max(string $table, string $column, array $where)
 * @method string min(string $table, string $column, array $where)
 * @method string avg(string $table, string $column, array $where)
 * @method string sum(string $table, string $column, array $where)
 */
class Database extends Medoo {
    /**
     * Constructor
     * 
     * @param array $config Database configuration
     * @throws PDOException If connection fails
     */
    public function __construct(array $config) {
        try {
            $options = [
                'type' => $config['type'],
                'host' => $config['host'],
                'database' => $config['database'],
                'username' => $config['username'],
                'password' => $config['password'],
                'charset' => $config['charset'] ?? 'utf8mb4',
                'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
                'prefix' => $config['prefix'] ?? '',
                'option' => array_merge([
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ], $config['options'] ?? [])
            ];
            
            parent::__construct($options);
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }
}
