<?php
/**
 * Trindade Framework
 *
 * Asset Management System
 * 
 * Handles asset versioning and management.
 * Provides tools for CSS, JS and image management.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

/**
 * Assets Class - Asset management (CSS/JS/Images)
 * 
 * @package Trindade
 * @property string $publicPath Public assets path
 * @property string $manifestFile Manifest file path
 * @property array|null $manifest Manifest cache
 * @property bool $useVersioning Versioning flag
 */
class Assets {
    protected string $publicPath;
    protected string $manifestFile;
    protected ?array $manifest = null;
    protected bool $useVersioning;

    public function __construct(string $publicPath, bool $useVersioning = true) {
        $this->publicPath = rtrim($publicPath, '/');
        $this->manifestFile = $this->publicPath . '/manifest.json';
        $this->useVersioning = $useVersioning;
    }

    public function css(string $path): string {
        return $this->asset($path, 'css');
    }

    public function js(string $path): string {
        return $this->asset($path, 'js');
    }

    public function img(string $path): string {
        return $this->asset($path, 'img');
    }

    protected function asset(string $path, string $type): string {
        $path = ltrim($path, '/');
        
        if (!$this->useVersioning) {
            return "/{$path}";
        }

        // Carregar manifest
        if ($this->manifest === null) {
            $this->loadManifest();
        }

        // Verificar se existe no manifest
        if (isset($this->manifest[$path])) {
            return $this->manifest[$path];
        }

        // Se não existe no manifest, criar versão
        $fullPath = "{$this->publicPath}/{$path}";
        if (file_exists($fullPath)) {
            $version = substr(md5_file($fullPath), 0, 8);
            $versionedPath = preg_replace('/\.(js|css|jpg|jpeg|png|gif|svg)$/', ".{$version}.$1", $path);
            
            // Copiar arquivo com versão
            copy($fullPath, "{$this->publicPath}/{$versionedPath}");
            
            // Atualizar manifest
            $this->manifest[$path] = "/{$versionedPath}";
            $this->saveManifest();
            
            return "/{$versionedPath}";
        }

        return "/{$path}";
    }

    protected function loadManifest(): void {
        if (file_exists($this->manifestFile)) {
            $this->manifest = json_decode(file_get_contents($this->manifestFile), true) ?? [];
        } else {
            $this->manifest = [];
        }
    }

    protected function saveManifest(): void {
        file_put_contents($this->manifestFile, json_encode($this->manifest, JSON_PRETTY_PRINT));
    }
}

