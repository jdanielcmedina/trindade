<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize error handling
Trindade\ErrorHandler::initialize();

// Initialize application
try {
    $config = require_once __DIR__ . '/../config.php';
    $app = new Trindade\Core($config);

    // Route for SPA (Single Page Application)
    $app->on('GET /', function() use ($app) {
        $app->view('app');
    });

    // Global handler for routes not found
    $app->on('.*', function() use ($app) {
        $app->view('errors/404', [
            'title' => 'Page Not Found',
            'message' => 'The page you are looking for does not exist.',
            'uri' => $_SERVER['REQUEST_URI']
        ]);
    });

} catch (Throwable $e) {
    Trindade\ErrorHandler::handleException($e);
}

