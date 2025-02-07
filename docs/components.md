# Framework Components

The Trindade Framework consists of several modular components that work together to provide a robust and flexible development experience.

## Core

The framework's core that manages all other components.

```php
$app = new Trindade\Core($config);
```

### Main Features
- Dependency management
- Plugin loading
- Routing
- Error handling

## Database

Database system with PDO-based Query Builder.

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

// Insert
$id = $app->db->insert('users', [
    'name' => 'John',
    'email' => 'john@email.com'
]);

// Update
$app->db->update('users', 
    ['active' => 1], 
    ['id' => 1]
);

// Delete
$app->db->delete('users', ['id' => 1]);

// Transactions
$app->db->action(function($db) {
    $db->insert('users', ['name' => 'John']);
    $db->insert('profiles', ['user_id' => $db->id()]);
});
```

## Session

Simplified session management.

```php
// Set value
$app->session->set('user_id', 123);

// Get value
$userId = $app->session->get('user_id');

// Remove value
$app->session->remove('user_id');

// Check if exists
if ($app->session->has('user_id')) {
    // ...
}

// Clear session
$app->session->clear();
```

## Cache

Flexible cache system.

```php
// Set cache
$app->cache->set('key', $value, 3600); // 1 hour

// Get from cache
$value = $app->cache->get('key', $default);

// Remove from cache
$app->cache->remove('key');

// Clear all cache
$app->cache->clear();
```

## Mail

Email sending system.

```php
$app->mail->to('user@email.com')
    ->subject('Welcome!')
    ->body('Hello, welcome!', true) // true for HTML
    ->attach('/path/to/file.pdf')
    ->send();
```

## File

File and upload management.

```php
// File upload
$file = $app->file->upload($_FILES['document']);

// Download
$app->file->download('/path/to/file.pdf', 'document.pdf');

// File operations
$app->file->move($source, $dest);
$app->file->copy($source, $dest);
$app->file->delete($path);
```

## Utils

Various utility functions.

```php
// Slugify
$slug = $app->utils->slug('Post Title');

// Date formatting
$date = $app->utils->formatDate('2024-01-01');

// Sanitization
$text = $app->utils->sanitize($input);

// Validation
if ($app->utils->isEmail('email@test.com')) {
    // ...
}
```

## Logger

Logging system.

```php
$app->log->emergency('System down!');
$app->log->error('DB Error', ['table' => 'users']);
$app->log->info('New record', ['id' => 123]);
```

## Assets

Asset management with versioning.

```php
// In views
<link href="<?= $app->assets->css('app.css') ?>" rel="stylesheet">
<script src="<?= $app->assets->js('app.js') ?>"></script>
<img src="<?= $app->assets->img('logo.png') ?>">
```

## Lang

Internationalization system.

```php
// In lang/en.php
return [
    'messages' => [
        'welcome' => 'Welcome :name!'
    ]
];

// In code
echo $app->lang->get('messages.welcome', ['name' => 'John']);
$app->lang->setLocale('en');
```

## Hash

Hash and encryption utilities.

```php
// Password hash
$hash = $app->hash->make('password123');
$valid = $app->hash->verify('password123', $hash);

// Encryption
$encrypted = $app->hash->encrypt('secret data', 'key');
$decrypted = $app->hash->decrypt($encrypted, 'key');
```

## Cookie

Cookie management.

```php
// Set cookie
$app->cookie->set('name', 'value', 3600); // 1 hour

// Get value
$value = $app->cookie->get('name');

// Remove cookie
$app->cookie->remove('name');
```

## Next Steps

- Learn about the [Routing System](routing.md)
- Explore the [Plugin System](plugins.md)
- Check the [Security Guide](security.md) 