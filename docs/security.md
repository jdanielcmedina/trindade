# Security Guide

The Trindade Framework implements various security measures to protect your application against common vulnerabilities.

## CSRF Protection

The framework includes automatic protection against CSRF (Cross-Site Request Forgery) attacks:

```php
// In forms
<form method="POST" action="/users">
    <?= $app->csrf->field() ?>
    <!-- form fields -->
</form>

// In AJAX requests
fetch('/api/users', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': '<?= $app->csrf->token() ?>'
    },
    body: formData
});
```

## Input Sanitization

```php
// Automatic sanitization
$email = $app->utils->sanitize($app->post('email'));
$name = $app->utils->sanitize($app->post('name'));

// Email validation
if (!$app->utils->isEmail($email)) {
    throw new \Exception('Invalid email');
}

// HTML sanitization
$content = $app->utils->sanitizeHtml($content, [
    'allowed_tags' => '<p><br><strong><em>',
    'allowed_attributes' => ['class', 'id']
]);
```

## SQL Injection Prevention

The framework uses PDO with prepared statements by default:

```php
// Secure by default
$users = $app->db->select('users', '*', [
    'email' => $email
]);

// Automatic prepared statement
$app->db->insert('users', [
    'name' => $name,
    'email' => $email
]);

// Complex query with prepared statement
$app->db->query(
    "SELECT * FROM users WHERE email = ? AND status = ?",
    [$email, 'active']
);
```

## XSS Prevention

```php
// In views
<div><?= $app->utils->escape($user['name']) ?></div>

// Automatic escape in arrays
foreach ($users as $user): ?>
    <div><?= $user['name'] ?></div>
<?php endforeach; ?>

// Global configuration
$app->config['auto_escape'] = true;
```

## Password Hashing

```php
// Password hash
$hash = $app->hash->make($password);

// Password verification
if ($app->hash->verify($password, $hash)) {
    // Successful login
}

// Rehash if needed
if ($app->hash->needsRehash($hash)) {
    $newHash = $app->hash->make($password);
    // Update hash in database
}
```

## Rate Limiting

```php
// Basic configuration
$app->rateLimit->configure([
    'requests' => 60,
    'period' => 60 // seconds
]);

// Rate limit middleware
$app->before(function() use ($app) {
    if (!$app->rateLimit->check()) {
        $app->json([
            'error' => 'Too many requests'
        ], 429);
        return false;
    }
});

// Rate limit by IP
$app->rateLimit->check([
    'key' => $_SERVER['REMOTE_ADDR'],
    'requests' => 100,
    'period' => 3600
]);
```

## Session Security

```php
// Secure session configuration
$app->session->configure([
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Regenerate session ID
$app->session->regenerate();

// Clear session
$app->session->destroy();
```

## File Upload Security

```php
// Upload configuration
$app->file->configure([
    'allowed_types' => ['jpg', 'png', 'pdf'],
    'max_size' => 5 * 1024 * 1024, // 5MB
    'upload_dir' => 'storage/uploads'
]);

// Secure upload
try {
    $file = $app->file->upload($_FILES['document'], [
        'validate_mime' => true,
        'sanitize_filename' => true
    ]);
} catch (\Exception $e) {
    // Handle error
}
```

## Security Headers

```php
// Headers configuration
$app->security->headers([
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'same-origin',
    'Content-Security-Policy' => "default-src 'self'"
]);
```

## Security Logging

```php
// Login attempt logging
$app->log->security('Login attempt', [
    'email' => $email,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'success' => $success
]);

// Sensitive action logging
$app->log->security('Permission change', [
    'user_id' => $userId,
    'action' => 'change_role',
    'details' => $changes
]);
```

## Best Practices

1. **Input Validation**
   - Validate all user inputs
   - Use whitelisting instead of blacklisting
   - Define clear validation rules

2. **Session Management**
   - Use appropriate timeouts
   - Regenerate IDs after login
   - Store sensitive data carefully

3. **Database**
   - Always use prepared statements
   - Limit database user privileges
   - Regular data backup

4. **Uploads**
   - Validate file types
   - Limit upload sizes
   - Store in secure location

5. **Authentication**
   - Enforce strong passwords
   - Implement account lockout
   - Use two-factor authentication

## Security Checklist

- [ ] CSRF protection active
- [ ] Input sanitization
- [ ] Prepared statements
- [ ] XSS prevention
- [ ] Security headers
- [ ] Rate limiting
- [ ] Security logging
- [ ] Secure uploads
- [ ] Secure sessions
- [ ] Hashed passwords

## Next Steps

- Explore the [Framework Components](components.md)
- Learn about the [Plugin System](plugins.md)
- See the [Best Practices](best-practices.md) 