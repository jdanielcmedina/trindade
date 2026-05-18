<?php

/**
 * Trindade — Entry Point
 *
 * The ONLY file accessible from the web.
 * Point your Apache/Nginx document root to this public/ folder.
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$app = new \Trindade\Trindade(['root' => $root]);
