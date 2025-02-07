# Plugin System

The Trindade Framework has a powerful plugin system that allows you to extend the framework's functionality in a modular way.

## Basic Plugin Structure

```php
<?php
namespace Trindade\Plugins\MyPlugin;

class MyPlugin {
    protected $app;
    protected $config;
    
    public function __construct($app, array $config = []) {
        $this->app = $app;
        $this->config = array_merge([
            // default settings
        ], $config);
    }
    
    public function initialize() {
        // Plugin initialization
        $this->registerRoutes();
        $this->createTables();
    }
    
    protected function registerRoutes() {
        $this->app->group('/my-plugin', function() {
            // Define plugin routes
        });
    }
    
    protected function createTables() {
        // Create necessary tables
    }
}
```

## Registering a Plugin

In your `config.php` file:

```php
return [
    'plugins' => [
        'myPlugin' => [
            'class' => \Trindade\Plugins\MyPlugin\MyPlugin::class,
            'config' => [
                // specific settings
            ]
        ]
    ]
];
```

## Plugin Types

### 1. Feature Plugin

```php
<?php
namespace Trindade\Plugins\Auth;

class AuthPlugin {
    public function initialize() {
        $this->app->auth = $this;
    }
    
    public function login($email, $password) {
        // Login logic
    }
    
    public function register($data) {
        // Registration logic
    }
    
    public function logout() {
        // Logout logic
    }
}
```

### 2. Middleware Plugin

```php
<?php
namespace Trindade\Plugins\RateLimit;

class RateLimitPlugin {
    public function initialize() {
        $this->app->before(function($app) {
            return $this->checkRateLimit($app);
        });
    }
    
    protected function checkRateLimit($app) {
        // Rate limiting logic
    }
}
```

### 3. Integration Plugin

```php
<?php
namespace Trindade\Plugins\Payment;

class PaymentPlugin {
    public function initialize() {
        $this->app->payment = $this;
    }
    
    public function processPayment($amount, $method) {
        switch ($method) {
            case 'stripe':
                return $this->stripePayment($amount);
            case 'paypal':
                return $this->paypalPayment($amount);
        }
    }
}
```

## Complete Example: Blog Plugin

```php
<?php
namespace Trindade\Plugins\Blog;

class BlogPlugin {
    protected $app;
    protected $config;
    
    public function __construct($app, array $config = []) {
        $this->app = $app;
        $this->config = array_merge([
            'postsTable' => 'blog_posts',
            'categoriesTable' => 'blog_categories',
            'tagsTable' => 'blog_tags',
            'perPage' => 10
        ], $config);
    }
    
    public function initialize() {
        $this->app->blog = $this;
        $this->createTables();
        $this->registerRoutes();
    }
    
    protected function createTables() {
        // Create posts table
        $this->app->db->query("
            CREATE TABLE IF NOT EXISTS {$this->config['postsTable']} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                content TEXT NOT NULL,
                author_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Other tables...
    }
    
    protected function registerRoutes() {
        $this->app->group('/blog', function() {
            $this->app->on('GET /', function() {
                $posts = $this->getPosts();
                $this->app->view('plugins/Blog/views/index', ['posts' => $posts]);
            });
            
            $this->app->on('GET /:slug', function($slug) {
                $post = $this->getPost($slug);
                $this->app->view('plugins/Blog/views/post', ['post' => $post]);
            });
        });
    }
    
    public function getPosts(array $conditions = [], int $page = 1) {
        // Logic to fetch posts
    }
    
    public function getPost(string $slug) {
        // Logic to fetch a post
    }
    
    public function createPost(array $data) {
        // Logic to create post
    }
}
```

## Best Practices

1. **File Structure**
```
plugins/
    MyPlugin/
        MyPlugin.php
        views/
        assets/
        migrations/
```

2. **Configuration**
- Use sensible defaults
- Allow configuration overrides
- Document available options

3. **Initialization**
- Check dependencies
- Create necessary tables
- Register routes and hooks
- Initialize services

4. **Security**
- Validate inputs
- Use prepared statements
- Implement access control
- Sanitize outputs

5. **Performance**
- Use cache when possible
- Optimize queries
- Load resources on demand

## Testing Plugins

```php
<?php
namespace Tests\Plugins;

class BlogPluginTest extends TestCase {
    protected $app;
    protected $plugin;
    
    public function setUp() {
        $this->app = new \Trindade\Core([/* config */]);
        $this->plugin = new \Trindade\Plugins\Blog\BlogPlugin($this->app);
    }
    
    public function testCreatePost() {
        $data = [
            'title' => 'Test Post',
            'content' => 'Content...'
        ];
        
        $id = $this->plugin->createPost($data);
        $this->assertNotNull($id);
        
        $post = $this->plugin->getPost($id);
        $this->assertEquals($data['title'], $post['title']);
    }
}
```

## Next Steps

- See examples in [Available Plugins](plugins-list.md)
- Learn about [Security](security.md)
- Explore the [Framework Components](components.md) 