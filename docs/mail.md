# Mail System

The Trindade Framework provides a robust email system using PHPMailer. It supports SMTP configuration, HTML emails, attachments, templates, and more.

## Configuration

In your `config.php`:

```php
'mail' => [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your_email@gmail.com',
    'password' => 'your_password',
    'from' => [
        'address' => 'your_email@gmail.com',
        'name' => 'Sender Name'
    ],
    'debug' => false // Set to true for SMTP debugging
]
```

## Basic Usage

### Sending Simple Emails

```php
// Single recipient
$app->mail->send(
    'recipient@email.com',
    'Email Subject',
    'Email content in HTML'
);

// Multiple recipients
$app->mail->send(
    [
        'john@email.com' => 'John Doe',
        'jane@email.com' => 'Jane Doe'
    ],
    'Email Subject',
    'Email content in HTML'
);
```

### Using CC and BCC

```php
$app->mail->send(
    'recipient@email.com',
    'Email Subject',
    'Email content',
    [
        'cc' => [
            'cc1@email.com',
            'cc2@email.com' => 'CC Name'
        ],
        'bcc' => [
            'bcc@email.com'
        ]
    ]
);
```

### Adding Attachments

```php
$app->mail->send(
    'recipient@email.com',
    'Email Subject',
    'Email content',
    [
        'attachments' => [
            // Simple attachment
            '/path/to/file.pdf',
            
            // Attachment with custom name
            [
                'path' => '/path/to/file.pdf',
                'name' => 'document.pdf'
            ],
            
            // Attachment with all options
            [
                'path' => '/path/to/file.pdf',
                'name' => 'document.pdf',
                'encoding' => 'base64',
                'type' => 'application/pdf'
            ]
        ]
    ]
);
```

## Using Templates

Create an email template file (e.g., `views/emails/welcome.php`):

```php
<h1>Welcome <?= $name ?>!</h1>
<p>Thank you for joining us.</p>
<p>Your account details:</p>
<ul>
    <li>Email: <?= $email ?></li>
    <li>Username: <?= $username ?></li>
</ul>
```

Send email using the template:

```php
$app->mail->sendTemplate(
    'user@email.com',
    'views/emails/welcome.php',
    [
        'name' => 'John Doe',
        'email' => 'john@email.com',
        'username' => 'johndoe'
    ],
    [
        'subject' => 'Welcome to Our Platform'
    ]
);
```

## Error Handling

The mail system includes built-in error handling and logging:

```php
// Add a logger when initializing
$app->mail = new Mail($config, function($message) use ($app) {
    $app->log->info($message);
});

// Send email with error handling
if (!$app->mail->send('user@email.com', 'Subject', 'Content')) {
    // Handle error
    $app->log->error('Failed to send email');
}
```

## Advanced Usage

### Getting PHPMailer Instance

For advanced configurations, you can access the PHPMailer instance directly:

```php
$mailer = $app->mail->getMailer();
$mailer->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];
```

### Modifying Configuration

```php
$app->mail->setConfig('debug', true);
$app->mail->setConfig('encryption', 'ssl');
```

## Gmail Configuration

To use Gmail SMTP, you'll need to:

1. Enable 2-Step Verification in your Google Account
2. Generate an App Password:
   - Go to Google Account Security
   - Select "App Passwords"
   - Generate a new password for your app
3. Use that password in your configuration:

```php
'mail' => [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your_gmail@gmail.com',
    'password' => 'your_app_password',
    'from' => [
        'address' => 'your_gmail@gmail.com',
        'name' => 'Your Name'
    ]
]
```

## Security Considerations

1. **Never store email credentials in version control**
   - Use environment variables
   - Keep sensitive data in a separate config file

2. **Use TLS/SSL Encryption**
   - Always use encryption when connecting to SMTP servers
   - Verify SSL certificates in production

3. **Validate Email Addresses**
   - Always validate email addresses before sending
   - Use proper email headers

4. **Rate Limiting**
   - Implement rate limiting for email sending
   - Monitor for abuse

## Troubleshooting

Common issues and solutions:

1. **Connection Failed**
   - Verify SMTP credentials
   - Check firewall settings
   - Ensure proper encryption settings

2. **Authentication Failed**
   - Double-check username/password
   - For Gmail, ensure using App Password
   - Verify account security settings

3. **SSL/TLS Issues**
   - Update SSL certificates
   - Verify SSL/TLS configuration
   - Check server requirements

4. **Emails in Spam**
   - Configure SPF records
   - Set up DKIM
   - Use consistent "From" addresses

## Next Steps

- Learn about [Framework Components](components.md)
- Check the [Security Guide](security.md)
- Explore [Best Practices](best-practices.md) 