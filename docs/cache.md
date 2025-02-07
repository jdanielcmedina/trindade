# Cache System

The Trindade Framework provides a flexible caching system with support for multiple drivers. Currently supported drivers are:
- File System (default)
- Redis
- Memcached

## Basic Usage

```php
// Set a value in cache
$app->cache->set('user:123', $userData, 3600); // expires in 1 hour

// Get a value from cache
$userData = $app->cache->get('user:123', $defaultValue);

// Check if key exists
if ($app->cache->has('user:123')) {
    // ...
}

// Remove a value
$app->cache->remove('user:123');

// Clear all cache
$app->cache->clear();
```

## Multiple Operations

```php
// Set multiple values
$app->cache->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2'
], 3600);

// Get multiple values
$values = $app->cache->getMultiple(['key1', 'key2'], $default);

// Remove multiple values
$app->cache->removeMultiple(['key1', 'key2']);
```

## Configuration

### File Driver (Default)
```php
'cache' => [
    'driver' => 'file',
    'path' => __DIR__ . '/storage/cache',
    'prefix' => 'trindade:'
]
```

### Redis Driver
```php
'cache' => [
    'driver' => 'redis',
    'prefix' => 'trindade:',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0
    ]
]
```

### Memcached Driver
```php
'cache' => [
    'driver' => 'memcached',
    'prefix' => 'trindade:',
    'memcached' => [
        'servers' => [
            [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 100
            ]
        ],
        'options' => [
            // Additional Memcached options
        ]
    ]
]
```

## Driver Features

### File Driver
- Simple file-based caching
- No external dependencies
- Good for development and small sites
- Stores cache in files under storage/cache directory

### Redis Driver
- High performance
- Support for complex data structures
- Excellent for distributed caching
- Requires Redis server
- Features:
  - Atomic operations
  - Key expiration
  - Pub/Sub capabilities
  - Data persistence

### Memcached Driver
- High performance
- Good for simple objects
- Multi-server support
- Requires Memcached server
- Features:
  - Automatic load balancing
  - Binary protocol support
  - Data compression
  - Distributed caching

## Best Practices

1. **Key Naming**
```php
// Use namespaced keys
$app->cache->set('users:profile:123', $data);
$app->cache->set('posts:recent', $posts);
$app->cache->set('settings:site', $settings);
```

2. **TTL (Time To Live)**
```php
// Short-lived cache (5 minutes)
$app->cache->set('stats:visitors', $stats, 300);

// Medium-lived cache (1 hour)
$app->cache->set('posts:popular', $posts, 3600);

// Long-lived cache (1 day)
$app->cache->set('settings:global', $settings, 86400);
```

3. **Error Handling**
```php
try {
    $data = $app->cache->get('key');
    if ($data === null) {
        // Cache miss - fetch and store data
        $data = fetchExpensiveData();
        $app->cache->set('key', $data, 3600);
    }
} catch (\Exception $e) {
    // Handle cache errors gracefully
    $data = fetchExpensiveData();
}
```

4. **Cache Tags** (Implementation Example)
```php
// Set cache with tags
$app->cache->set('post:' . $id, $post, 3600);
$app->cache->set('tags:post:' . $id, ['blog', 'featured'], 3600);

// Clear cache by tag
$taggedPosts = $app->cache->get('tags:post:*');
foreach ($taggedPosts as $key => $tags) {
    if (in_array('blog', $tags)) {
        $postId = str_replace('tags:post:', '', $key);
        $app->cache->remove('post:' . $postId);
        $app->cache->remove($key);
    }
}
```

## Requirements

### Redis Driver
- PHP Redis extension (`phpredis`)
- Redis server

Installation:
```bash
# Install Redis server
sudo apt-get install redis-server

# Install PHP Redis extension
sudo pecl install redis
```

### Memcached Driver
- PHP Memcached extension
- Memcached server

Installation:
```bash
# Install Memcached server
sudo apt-get install memcached

# Install PHP Memcached extension
sudo apt-get install php-memcached
```

## Security Considerations

1. **Data Sensitivity**
   - Don't cache sensitive information
   - Use encryption for sensitive data if necessary
   - Be careful with user-specific data

2. **Cache Poisoning**
   - Validate data before caching
   - Sanitize cache keys
   - Use prefixes to prevent collisions

3. **Access Control**
   - Secure Redis/Memcached servers
   - Use authentication when available
   - Restrict network access

## Performance Tips

1. **Choose the Right Driver**
   - File: Development/Small sites
   - Redis: Complex data/High traffic
   - Memcached: Simple data/Distributed

2. **Optimize Key Length**
   - Keep keys short but descriptive
   - Use consistent naming conventions
   - Consider key compression for large datasets

3. **Batch Operations**
   - Use getMultiple/setMultiple for bulk operations
   - Group related data
   - Consider cache warming for critical data

## Next Steps

- Explore [Framework Components](components.md)
- Learn about [Security](security.md)
- Check [Best Practices](best-practices.md) 