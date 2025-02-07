# Trindade Framework

Modern, lightweight and flexible PHP framework for fast and secure web development.

## 📚 Complete Documentation

- [Installation Guide](docs/installation.md)
- [Framework Components](docs/components.md)
- [Routing System](docs/routing.md)
- [Plugin System](docs/plugins.md)
- [Security Guide](docs/security.md)
- [Contributing Guide](docs/contributing.md)

## 🚀 Quick Installation

```bash
composer require trindade/framework
```

## 🛠️ Dependencies

The framework uses third-party libraries to provide robust functionality:

- **Medoo**: Query builder and database system
  - Simple and secure interface for database operations
  - Support for multiple database types (MySQL, PostgreSQL, SQLite)
  - Protection against SQL injection
  - Documentation: [https://medoo.in](https://medoo.in)

- **PHPMailer**: Email sending system
  - Full support for HTML emails
  - SMTP authentication
  - Attachments and mass mailing
  - Documentation: [https://github.com/PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)

## ⚡ Basic Example

```php
require_once __DIR__ . '/vendor/autoload.php';

$config = require_once 'config.php';
$app = new Trindade\Core($config);

// Database example using Medoo
$app->on('GET /users', function() use ($app) {
    $users = $app->db->select('users', '*');
    $app->json($users);
});

// Email example using PHPMailer
$app->on('POST /contact', function() use ($app) {
    $app->mail->send(
        'user@email.com',
        'Contact Form',
        'New message from contact form',
        ['html' => true]
    );
});
```

## 🎯 Main Features

### Powerful Routing
```php
// Basic route
$app->on('GET /users', function() use ($app) {
    $users = $app->db->select('users', '*');
    $app->json($users);
});

// Route groups
$app->group('/api', function() use ($app) {
    $app->on('GET /users', function() {
        // ...
    });
});

// Dynamic parameters
$app->on('GET /users/:id', function($id) use ($app) {
    // ...
});
```

### Database Query Builder
```php
// Simple select
$users = $app->db->select('users', '*');

// Join with conditions
$posts = $app->db->select('posts', [
    '[>]users' => ['user_id' => 'id']
], [
    'posts.id',
    'posts.title',
    'users.name'
]);

// Insert with ID return
$id = $app->db->insert('users', [
    'name' => 'John',
    'email' => 'john@email.com'
]);
```

### Cache System
```php
// Set cache
$app->cache->set('key', $value, 3600);

// Get from cache
$value = $app->cache->get('key', $default);
```

### Session Management
```php
$app->session->set('user_id', 123);
$userId = $app->session->get('user_id');
```

### Plugin System
```php
// Plugin configuration
'plugins' => [
    'blog' => [
        'class' => \Trindade\Plugins\Blog\BlogPlugin::class,
        'config' => [
            // plugin settings
        ]
    ]
]

// Using the plugin
$app->blog->createPost([
    'title' => 'My Post',
    'content' => 'Content...'
]);
```

### Utilities
```php
// Slugify
$slug = $app->utils->slug('Post Title');

// Date formatting
$date = $app->utils->formatDate('2024-01-01');

// Secure hash
$hash = $app->hash->make($password);
```

## 🛠️ Main Components

- **Core**: Framework core
- **Database**: Database system with Query Builder
- **Session**: Session management
- **Cache**: Cache system
- **Mail**: Email system
- **File**: File management
- **Utils**: Utility functions
- **Logger**: Logging system
- **Assets**: Asset management
- **Lang**: Internationalization
- **Hash**: Hash and encryption utilities
- **Cookie**: Cookie management

## 🔌 Available Plugins

- **Blog**: Complete blog system
- **Auth**: Authentication and authorization
- **Admin**: Administrative panel
- **API**: RESTful API generator
- **Forms**: Form builder
- **SEO**: Search engine optimization

## 🔒 Security

- CSRF Protection
- Input Sanitization
- Prepared Statements
- XSS Prevention
- Rate Limiting
- Password Hashing

## 📦 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- PHP Extensions:
  - PDO
  - mbstring
  - json
  - openssl

## 🤝 Contributing

1. Fork the project
2. Create your feature branch (`git checkout -b feature/MyFeature`)
3. Commit your changes (`

## 📝 Author

**Jorge Daniel Medina**
- GitHub: [@jdanielcmedina](https://github.com/jdanielcmedina)

## 📄 License

This framework is open-source software licensed under the MIT license.