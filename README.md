# 🚀 Trindade Framework

> A decade of PHP development experience, now open to the community.

## The Story

Trindade Framework is the result of 10 years of personal development and real-world application. What started as my personal toolkit for building web applications has evolved into a robust, minimalist framework that I've used across numerous projects.

As a developer who values clean, efficient code over complex dependencies, I've always preferred writing solutions that are straightforward and maintainable. This framework reflects that philosophy - it's lightweight, functional, and gets the job done without unnecessary complexity.

After using Trindade privately in my projects for a decade, I've decided to share it with the community. Whether you're a fan of minimalist code like me or just looking for a straightforward PHP framework, you're welcome to use, learn from, and contribute to Trindade.

## Why Trindade?

- **Minimalist by Design**: No external dependencies, no bloat
- **Battle-Tested**: Used in production for over 10 years
- **Clean Code**: Focused on readability and maintainability
- **Practical Approach**: Built from real-world experience
- **Modern PHP**: Leverages PHP 8.0+ features
- **Open to Growth**: Ready for community contributions

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer (optional)

## 🛠️ Installation

1. **Via Composer**
```bash
composer require jdanielcmedina/trindade
```

2. **Manual**
- Clone the repository
```bash
git clone https://github.com/jdanielcmedina/trindade.git
```
- Or download from [https://github.com/jdanielcmedina/trindade/releases](https://github.com/jdanielcmedina/trindade/releases)

## ⚙️ Configuration

Create a `config.php` file in your project root:

```php
return [
    'debug' => true,
    
    // Application paths
    'paths' => [
        'views' => __DIR__ . '/views',
        'cache' => __DIR__ . '/storage/cache',
        'logs' => __DIR__ . '/storage/logs',
        'uploads' => __DIR__ . '/storage/uploads',
        'public' => __DIR__ . '/public',
        'lang' => __DIR__ . '/lang'
    ],
    
    // MySQL Configuration
    'mysql' => [
        'host' => 'localhost',
        'database' => 'my_db',
        'username' => 'root',
        'password' => ''
    ],
    
    // Email Configuration
    'mail' => [
        'fromName' => 'My App',
        'username' => 'email@domain.com',
        'password' => 'password',
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'auth' => true,
            'secure' => 'tls'
        ]
    ]
];
```

## 📁 Folder Structure

```
my-project/
├── config.php
├── index.php
├── Trindade.php
├── public/
│   ├── css/
│   ├── js/
│   └── img/
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── views/
│   ├── layouts/
│   └── errors/
└── lang/
    ├── en.php
    └── pt.php
```

## 🚦 Routes

### Basic Routes
```php
// Simple route
$app->on('GET /', function() {
    $this->view('home', ['title' => 'Welcome']);
});

// Route with parameter
$app->on('GET /user/:id', function($id) {
    $this->json([
        'action' => 'get user',
        'id' => $id
    ]);
});

// Multiple parameters
$app->on('GET /posts/:year/:month/:slug', function($year, $month, $slug) {
    $this->json([
        'action' => 'get post',
        'year' => $year,
        'month' => $month,
        'slug' => $slug
    ]);
});

// Optional parameter with query string
$app->on('GET /search/:query?', function($query = null) {
    $page = $this->get('page', 1);    // from query string ?page=1
    $limit = $this->get('limit', 10); // from query string ?limit=10
    
    $this->json([
        'action' => 'search',
        'query' => $query,
        'page' => $page,
        'limit' => $limit
    ]);
});
```

### HTTP Methods & RESTful Routes
```php
// GET - Fetch a product
$app->on('GET /products/:id', function($id) {
    $this->json([
        'action' => 'get product',
        'id' => $id
    ]);
});

// POST - Update a product
$app->on('POST /products/:id', function($id) {
    $data = $this->post(); // get POST data
    $this->json([
        'action' => 'update product',
        'id' => $id,
        'data' => $data
    ]);
});

// DELETE - Remove a product
$app->on('DELETE /products/:id', function($id) {
    $this->json([
        'action' => 'delete product',
        'id' => $id
    ]);
});
```

### Nested Resources
```php
$app->on('GET /categories/:categoryId/products/:productId', function($categoryId, $productId) {
    $this->json([
        'action' => 'get product in category',
        'categoryId' => $categoryId,
        'productId' => $productId
    ]);
});
```

### API Versioning with Groups
```php
$app->group('/api', function() use ($app) {
    // API v1 group
    $app->group('/v1', function() use ($app) {
        $app->on('GET /test', function() {
            $this->json([
                'version' => 'v1',
                'message' => 'API v1 is working'
            ]);
        });

        // v1 404 handler
        $app->on('GET /:any', function() {
            $this->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Endpoint not found in API v1'
                ]
            ], 404);
        });
    });

    // API v2 group
    $app->group('/v2', function() use ($app) {
        $app->on('GET /test', function() {
            $this->json([
                'version' => 'v2',
                'message' => 'API v2 is working',
                'timestamp' => time()
            ]);
        });

        // v2 404 handler
        $app->on('GET /:any', function() {
            $this->json([
                'status' => 'error',
                'version' => 'v2',
                'errors' => [
                    [
                        'status' => 404,
                        'title' => 'Not Found',
                        'detail' => 'Endpoint not found in API v2',
                        'timestamp' => time()
                    ]
                ]
            ], 404);
        });
    });
});
```

### Route Parameters
- `:param` - Required parameter
- `:param?` - Optional parameter
- `:any` - Wildcard (matches everything, useful for 404 handlers)

### HTTP Methods Supported
- GET
- POST
- PUT
- DELETE
- PATCH
- OPTIONS
- HEAD
- ANY (matches any method)

### Response Types
```php
// JSON Response
$this->json($data, $statusCode = 200);

// View Response
$this->view('template', $data);

// Text Response
$this->text('Hello World', $statusCode = 200);
```

### Request Data
```php
// GET data
$query = $this->get('search');
$page = $this->get('page', 1); // with default

// POST data
$data = $this->post();
$name = $this->post('name');

// Any request data (GET + POST)
$data = $this->input();
$value = $this->input('key', 'default');
```

## 🎨 Views

```php
// In index.php
$app->on('/', function() {
    return $this->view('home', [
        'title' => 'Welcome',
        'user' => ['name' => 'John']
    ]);
});

// In views/home.php
<h1><?= $title ?></h1>
<p>Hello <?= $user['name'] ?></p>
```

## 💾 Database

```php
// Select
$users = $this->db->select('users', '*', ['active' => 1]);

// Insert
$id = $this->db->insert('users', [
    'name' => 'John',
    'email' => 'john@email.com'
]);

// Update
$this->db->update('users', 
    ['active' => 0], 
    ['id' => 1]
);

// Delete
$this->db->delete('users', ['id' => 1]);
```

## 📧 Email

```php
$this->mail->to('user@email.com')
    ->subject('Welcome!')
    ->body('<h1>Hello!</h1>', true)
    ->attach('/path/to/file.pdf')
    ->send();
```

## 📁 Files

```php
// Upload
$file = $this->file->upload($_FILES['document']);

// Download
$this->file->download('/path/to/file.pdf', 'document.pdf');

// Operations
$this->file->move($source, $dest);
$this->file->copy($source, $dest);
$this->file->delete($path);
```

## 🔐 Hash & Encryption

```php
// Password hash
$hash = $this->hash->make('password123');
$valid = $this->hash->verify('password123', $hash);

// Encryption
$encrypted = $this->hash->encrypt('secret data', 'key');
$decrypted = $this->hash->decrypt($encrypted, 'key');

// Others
$md5 = $this->hash->md5('text');
$uuid = $this->hash->uuid();
```

## 🛠️ Utilities

```php
// Text
$slug = $this->utils->slug('Hello World'); // hello-world
$excerpt = $this->utils->excerpt($longText, 50);
$safe = $this->utils->sanitize('<script>');

// Formatting
$number = $this->utils->formatNumber(1234.56); // 1,234.56
$money = $this->utils->formatMoney(1234.56); // $ 1,234.56
$date = $this->utils->formatDate('2024-03-14'); // 03/14/2024

// Validation
$this->utils->isEmail('email@test.com');
$this->utils->isUrl('https://site.com');
$this->utils->isCp('1234-567');
```

## 📝 Logs

```php
$this->log->emergency('System down!');
$this->log->error('DB Error', ['table' => 'users']);
$this->log->info('New record', ['id' => 123]);
```

## 🎨 Assets

```php
// In views
<link href="<?= $this->assets->css('app.css') ?>" rel="stylesheet">
<script src="<?= $this->assets->js('app.js') ?>"></script>
<img src="<?= $this->assets->img('logo.png') ?>">
```

## 🌍 Internationalization

```php
// In lang/en.php
return [
    'messages' => [
        'welcome' => 'Welcome :name!'
    ]
];

// In code
echo $this->lang->get('messages.welcome', ['name' => 'John']);
$this->lang->setLocale('pt');
```

## 🔄 Cache

```php
// Store
$this->cache->set('users', $users, 3600);

// Retrieve
$users = $this->cache->get('users', []);

// Remove
$this->cache->remove('users');
```

## 🚨 Error Handling

```php
try {
    // code that might fail
} catch (\Exception $e) {
    $this->log->error($e->getMessage());
    return $this->json(['error' => 'An error occurred'], 500);
}
```

## 📚 Examples

### Basic Example
```php
<?php
require_once 'Trindade.php';

$app = new Trindade\Trindade();

$app->on('GET /', function() {
    return $this->view('home', ['title' => 'Welcome']);
});

$app->run();
```

### REST API
```php
$app->group('/api', function($app) {
    $app->on('GET /users', function() {
        return $this->json($this->db->select('users'));
    });
    
    $app->on('POST /users', function() {
        $data = $this->post();
        $id = $this->db->insert('users', $data);
        return $this->json(['id' => $id], 201);
    });
});
```

## 🤝 Contributing

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details. # trindade
