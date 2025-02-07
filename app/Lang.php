<?php
/**
 * Trindade Framework
 *
 * Language System
 * 
 * Handles internationalization and localization.
 * Provides translation and locale management.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

/**
 * Lang Class - Internationalization system
 * 
 * @package Trindade
 * @property string $path Language files path
 * @property string $locale Current locale
 * @property array $messages Messages cache
 */
class Lang {
    protected string $path;
    protected string $locale;
    protected array $messages = [];

    public function __construct(string $path, string $defaultLocale = 'pt') {
        $this->path = rtrim($path, '/');
        $this->setLocale($defaultLocale);
    }

    public function setLocale(string $locale): void {
        $this->locale = $locale;
        $this->loadMessages();
    }

    public function getLocale(): string {
        return $this->locale;
    }

    public function get(string $key, array $replace = []): string {
        $message = $this->messages;
        
        foreach (explode('.', $key) as $segment) {
            if (!isset($message[$segment])) {
                return $key;
            }
            $message = $message[$segment];
        }

        if (!is_string($message)) {
            return $key;
        }

        return $this->replaceParams($message, $replace);
    }

    protected function loadMessages(): void {
        $file = "{$this->path}/{$this->locale}.php";
        
        if (file_exists($file)) {
            $this->messages = require $file;
        } else {
            $this->messages = [];
        }
    }

    protected function replaceParams(string $message, array $replace): string {
        foreach ($replace as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }
        return $message;
    }
}