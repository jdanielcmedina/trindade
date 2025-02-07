# Installation Guide

## System Requirements

Before installing the Trindade Framework, make sure your system meets the following requirements:

- PHP 7.4 or higher
- MySQL 5.7 or higher
- PHP Extensions:
  - PDO
  - mbstring
  - json
  - openssl

## Installation via Composer

The easiest way to install the Trindade Framework is through Composer:

```bash
composer require trindade/framework
```

## Manual Installation

1. Download the latest version of the framework
2. Extract the files to your project
3. Configure the autoloader in your `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Trindade\\": "src/"
        }
    }
}
```

## Initial Configuration

1. Create a `config.php` file in your project root:

```php
return [
    'database' => [
        'type' => 'mysql',
        'host' => 'localhost',
        'database' => 'your_database',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => ''
    ],
    
    'paths' => [
        'base' => __DIR__,
        'public' => __DIR__ . '/public',
        'storage' => __DIR__ . '/storage',
        'cache' => __DIR__ . '/storage/cache',
        'logs' => __DIR__ . '/storage/logs',
        'uploads' => __DIR__ . '/storage/uploads',
        'views' => __DIR__ . '/views'
    ],
    
    'debug' => true
];
```

2. Create the directory structure:

```
your-project/
├── config.php
├── public/
│   └── index.php
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── views/
│   ├── layouts/
│   └── errors/
└── routes/
    └── api/
```

3. Configure the `public/index.php` file:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../config.php';
$app = new Trindade\Core($config);

// Your routes here
$app->on('GET /', function() use ($app) {
    $app->json(['message' => 'Trindade Framework installed successfully!']);
});
```

4. Configure the web server:

### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Installation Verification

To verify that everything is working correctly:

1. Start the PHP server:
```bash
php -S localhost:8000 -t public
```

2. Access `http://localhost:8000` in your browser

3. You should see the success message in JSON format

## Next Steps

- Read about [Framework Components](components.md)
- Learn about the [Routing System](routing.md)
- Explore the [Plugin System](plugins.md) 