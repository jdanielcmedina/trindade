# Routing System

The Trindade Framework offers a flexible and powerful routing system that allows you to define endpoints for your application in a simple and organized way.

## Basic Routes

```php
// Simple GET route
$app->on('GET /', function() use ($app) {
    $app->json(['message' => 'Welcome!']);
});

// POST route
$app->on('POST /users', function() use ($app) {
    $data = $app->post();
    $id = $app->db->insert('users', $data);
    $app->json(['id' => $id], 201);
});

// PUT route
$app->on('PUT /users/:id', function($id) use ($app) {
    $data = $app->post();
    $app->db->update('users', $data, ['id' => $id]);
    $app->json(['success' => true]);
});

// DELETE route
$app->on('DELETE /users/:id', function($id) use ($app) {
    $app->db->delete('users', ['id' => $id]);
    $app->json(['success' => true]);
});
```

## Route Parameters

```php
// Required parameter
$app->on('GET /users/:id', function($id) use ($app) {
    $user = $app->db->get('users', '*', ['id' => $id]);
    $app->json($user);
});

// Optional parameter
$app->on('GET /posts/:year?', function($year = null) use ($app) {
    if ($year) {
        $posts = $app->db->select('posts', '*', ['YEAR(created_at)' => $year]);
    } else {
        $posts = $app->db->select('posts', '*');
    }
    $app->json($posts);
});

// Multiple parameters
$app->on('GET /blog/:year/:month/:slug', function($year, $month, $slug) use ($app) {
    $post = $app->db->get('posts', '*', [
        'AND' => [
            'YEAR(created_at)' => $year,
            'MONTH(created_at)' => $month,
            'slug' => $slug
        ]
    ]);
    $app->json($post);
});
```

## Route Groups

```php
// Basic group
$app->group('/api', function() use ($app) {
    $app->on('GET /users', function() use ($app) {
        $users = $app->db->select('users', '*');
        $app->json($users);
    });
    
    $app->on('GET /posts', function() use ($app) {
        $posts = $app->db->select('posts', '*');
        $app->json($posts);
    });
});

// Nested groups
$app->group('/api', function() use ($app) {
    $app->group('/v1', function() use ($app) {
        $app->on('GET /users', function() use ($app) {
            // API v1
        });
    });
    
    $app->group('/v2', function() use ($app) {
        $app->on('GET /users', function() use ($app) {
            // API v2
        });
    });
});

// Middleware in groups
$app->group('/admin', function() use ($app) {
    // Protected routes
}, function() use ($app) {
    // Authentication middleware
    if (!$app->session->get('admin')) {
        $app->json(['error' => 'Unauthorized'], 401);
        return false;
    }
    return true;
});
```

## Response Types

```php
// JSON Response
$app->on('GET /api/data', function() use ($app) {
    $app->json([
        'success' => true,
        'data' => ['name' => 'John']
    ]);
});

// View Response
$app->on('GET /page', function() use ($app) {
    $app->view('page', [
        'title' => 'My Page',
        'content' => 'Content...'
    ]);
});

// Text Response
$app->on('GET /text', function() use ($app) {
    $app->text('Plain text content');
});

// File Download
$app->on('GET /download', function() use ($app) {
    $app->download('/path/to/file.pdf', 'document.pdf');
});

// Redirect
$app->on('GET /redirect', function() use ($app) {
    $app->redirect('/new-page');
});
```

## Request Data

```php
// GET data
$query = $app->get('search');
$page = $app->get('page', 1); // with default value

// POST data
$data = $app->post();
$name = $app->post('name');

// Any method
$data = $app->input();
$value = $app->input('key', 'default');

// Headers
$token = $app->header('Authorization');

// Files
$file = $app->file->upload($_FILES['document']);
```

## Error Handling

```php
// 404 Error
$app->on('GET /:any', function() use ($app) {
    $app->view('errors/404', [], 404);
});

// Custom error
$app->on('GET /api/:any', function() use ($app) {
    $app->json([
        'error' => 'Endpoint not found'
    ], 404);
});

// Try/Catch in routes
$app->on('POST /data', function() use ($app) {
    try {
        // code that might throw an error
    } catch (\Exception $e) {
        $app->log->error($e->getMessage());
        $app->json(['error' => 'Internal error'], 500);
    }
});
```

## Middleware

```php
// Middleware function
function authMiddleware($app) {
    if (!$app->session->get('user_id')) {
        $app->json(['error' => 'Unauthorized'], 401);
        return false;
    }
    return true;
}

// Applying middleware
$app->on('GET /protected', function() use ($app) {
    $app->json(['data' => 'protected']);
}, 'authMiddleware');

// Multiple middlewares
$app->on('POST /admin/users', function() use ($app) {
    // ...
}, ['authMiddleware', 'adminMiddleware']);
```

## Next Steps

- Explore the [Plugin System](plugins.md)
- Check the [Security Guide](security.md)
- See the [Framework Components](components.md) 