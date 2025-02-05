<?php
return [
    'database' => [
        'type' => 'mysql',
        'host' => 'localhost',
        'dbName' => 'trindade',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    'mail' => [
        'username' => 'teu_email@gmail.com',
        'password' => 'tua_password',
        'fromName' => 'Trindade Framework',
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'secure' => 'tls',
            'auth' => true
        ]
    ],
    
    'paths' => [
        'base' => __DIR__,
        'public' => __DIR__ . '/public',
        'storage' => __DIR__ . '/storage',
        'cache' => __DIR__ . '/storage/cache',
        'logs' => __DIR__ . '/storage/logs',
        'uploads' => __DIR__ . '/storage/uploads',
        'views' => __DIR__ . '/views',
    ],
    
    'session' => [
        'name' => 'trindade_session',
        'lifetime' => 7200,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httpOnly' => true
    ],
    
    'cache' => [
        'driver' => 'file',
        'defaultTtl' => 3600,
    ],
    
    'debug' => true,
    'timezone' => 'Europe/Lisbon',
    'locale' => 'pt',
    'key' => 'chave_secreta_aqui'
]; 