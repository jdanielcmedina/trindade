<?php

/**
 * Trindade — Entry Point
 *
 * This is the ONLY file accessible from the web.
 * Everything else (config, routes, views, src) lives outside public/.
 *
 * Point your Apache/Nginx document root to this public/ folder.
 */

require '../vendor/autoload.php';

$app = new \Trindade\Trindade();
