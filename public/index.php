<?php
// Error handling configuration
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    
    require __DIR__ . '/../views/errors/500.php';
    exit(1);
}

function customExceptionHandler($e) {
    $error = [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    require __DIR__ . '/../views/errors/500.php';
    exit(1);
}

// Register error handlers
error_reporting(E_ALL);
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Try to initialize the application
try {
    require_once __DIR__ . '/../Trindade.php';
    $app = new Trindade\Trindade();

    // Main route
    $app->on('/', function() {
        $this->view('home', ['title' => 'Welcome']);
    });

    // Basic parameter capture
    $app->on('/user/:id', function($id) {
        $this->json([
            'action' => 'get user',
            'id' => $id
        ]);
    });

    // Multiple parameters
    $app->on('/posts/:year/:month/:slug', function($year, $month, $slug) {
        $this->json([
            'action' => 'get post',
            'year' => $year,
            'month' => $month,
            'slug' => $slug
        ]);
    });

    // Optional parameter with query string
    $app->on('GET /search/:query?', function($query = null) {
        $page = $this->get('page', 1);
        $limit = $this->get('limit', 10);
        
        $this->json([
            'action' => 'search',
            'query' => $query,
            'page' => $page,
            'limit' => $limit
        ]);
    });

    // Different HTTP methods for same route
    $app->on('GET /products/:id', function($id) {
        $this->json([
            'action' => 'get product',
            'id' => $id
        ]);
    });

    $app->on('POST /products/:id', function($id) {
        $data = $this->post();
        $this->json([
            'action' => 'update product',
            'id' => $id,
            'data' => $data
        ]);
    });

    $app->on('DELETE /products/:id', function($id) {
        $this->json([
            'action' => 'delete product',
            'id' => $id
        ]);
    });

    // Nested resources
    $app->on('GET /categories/:categoryId/products/:productId', function($categoryId, $productId) {
        $this->json([
            'action' => 'get product in category',
            'categoryId' => $categoryId,
            'productId' => $productId
        ]);
    });

    // API Routes
    $app->group('/api', function() use ($app) {
        
        // API v1
        $app->group('/v1', function() use ($app) {
            // Test endpoint
            $app->on('GET /test', function() {
                $this->json([
                    'version' => 'v1',
                    'message' => 'API v1 is working'
                ]);
            });

            // Users endpoint
            $app->on('GET /users', function() {
                $this->json([
                    'version' => 'v1',
                    'action' => 'list users',
                    'users' => ['user1', 'user2']
                ]);
            });

            // 404 route for v1
            $app->on('GET /:any', function() {
                $this->json([
                    'error' => [
                        'code' => 404,
                        'message' => 'Endpoint not found in API v1'
                    ]
                ], 404);
            });
        });

        // API v2
        $app->group('/v2', function() use ($app) {
            // Test endpoint
            $app->on('GET /test', function() {
                $this->json([
                    'version' => 'v2',
                    'message' => 'API v2 is working',
                    'timestamp' => time()
                ]);
            });

            // Users endpoint
            $app->on('GET /users', function() {
                $this->json([
                    'version' => 'v2',
                    'action' => 'list users',
                    'users' => [
                        ['id' => 1, 'name' => 'User 1'],
                        ['id' => 2, 'name' => 'User 2']
                    ],
                    'metadata' => [
                        'total' => 2,
                        'page' => 1
                    ]
                ]);
            });

            // 404 route for v2
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

    // General 404 route - catches ANY route not found
    $app->on('GET /:any', function() {
        // Get current URI to show in error page
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $this->view('errors/404', [
            'title' => 'Page Not Found',
            'message' => 'The page you are looking for does not exist.',
            'uri' => $uri
        ]);
    });

} catch (Throwable $e) {
    customExceptionHandler($e);
}

