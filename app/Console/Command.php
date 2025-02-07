<?php
/**
 * Trindade Framework
 *
 * Console Command System
 * 
 * Base class for console commands.
 * Provides structure for CLI tools.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Console;

abstract class Command
{
    /**
     * Command name
     */
    protected string $name = '';
    
    /**
     * Command description
     */
    protected string $description = '';
    
    /**
     * Command arguments
     */
    protected array $arguments = [];
    
    /**
     * Command options
     */
    protected array $options = [];
    
    /**
     * Execute the command
     */
    abstract public function execute(array $args): int;
    
    /**
     * Get command name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get command description
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Get command arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
    
    /**
     * Get command options
     */
    public function getOptions(): array
    {
        return $this->options;
    }
} 