<?php

/**
 * Trindade Configuration
 *
 * Sensitive credentials go here — outside the public/ folder.
 * This file is never exposed to the web.
 */

return [
    'debug'    => false,
    'timezone' => 'Europe/Lisbon',
    'app_key'  => '', // generate with: php trindade key:generate

    // Database
    'db' => [
        'type'     => 'mysql',
        'host'     => 'localhost',
        'database' => 'my_database',
        'username'  => 'root',
        'password'  => '',
        'charset'  => 'utf8mb4',
    ],

    // JWT
    'jwt' => [
        'secret' => '', // set your secret key or use app_key above
        'expire' => 3600,
    ],

    // Mail (SMTP)
    'mail' => [
        'host'     => 'smtp.example.com',
        'port'     => 587,
        'secure'   => 'tls',
        'username'  => 'user@example.com',
        'password'  => '',
        'from'     => 'app@example.com',
        'name'     => 'My App',
    ],

    // File upload
    'upload' => [
        'max_size'   => 5242880,
        'types'      => ['image/jpeg', 'image/png', 'application/pdf'],
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'],
    ],

    // CORS
    'cors' => [
        'on'          => true,
        'origins'     => ['https://myapp.com'],
        'credentials' => true,
    ],

    // Security
    'security' => [
        'rate'   => true,
        'max'    => 60,
        'window' => 60,
    ],
];
