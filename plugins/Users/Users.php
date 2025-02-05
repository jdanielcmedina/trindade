<?php
namespace Trindade\Plugins;

class Users {
    protected $app;
    protected $table = 'users';
    
    public function __construct($app) {
        $this->app = $app;
        $this->setupRoutes();
    }
    
    protected function setupRoutes(): void {
        // Rotas de API
        $this->app->group('/api/users', function($app) {
            $app->on('GET /', [$this, 'list']);
            $app->on('GET /:id', [$this, 'get']);
            $app->on('POST /', [$this, 'create']);
            $app->on('PUT /:id', [$this, 'update']);
            $app->on('DELETE /:id', [$this, 'delete']);
        });
        
        // Rotas de Admin
        $this->app->group('/admin/users', function($app) {
            $app->on('GET /', [$this, 'adminList']);
            $app->on('GET /new', [$this, 'adminNew']);
            $app->on('GET /edit/:id', [$this, 'adminEdit']);
        });
    }
    
    // API Methods
    public function list() {
        $users = $this->app->select($this->table, '*', [
            'ORDER' => ['name' => 'ASC']
        ]);
        return $this->app->json($users);
    }
    
    public function get($id) {
        $user = $this->app->select($this->table, '*', ['id' => $id]);
        if (empty($user)) {
            return $this->app->json(['error' => 'User not found'], 404);
        }
        return $this->app->json($user[0]);
    }
    
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação básica
        if (empty($data['email']) || empty($data['password'])) {
            return $this->app->json(['error' => 'Email and password are required'], 400);
        }
        
        // Hash da password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $id = $this->app->insert($this->table, $data);
        if (!$id) {
            return $this->app->json(['error' => 'Failed to create user'], 500);
        }
        
        return $this->app->json(['id' => $id], 201);
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Se tiver password, faz hash
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $success = $this->app->update($this->table, $data, ['id' => $id]);
        if (!$success) {
            return $this->app->json(['error' => 'Failed to update user'], 500);
        }
        
        return $this->app->json(['success' => true]);
    }
    
    public function delete($id) {
        $success = $this->app->delete($this->table, ['id' => $id]);
        if (!$success) {
            return $this->app->json(['error' => 'Failed to delete user'], 500);
        }
        
        return $this->app->json(['success' => true]);
    }
    
    // Admin Views
    public function adminList() {
        $users = $this->app->select($this->table);
        return $this->app->view('plugins/users/list', ['users' => $users]);
    }
    
    public function adminNew() {
        return $this->app->view('plugins/users/form', ['user' => null]);
    }
    
    public function adminEdit($id) {
        $user = $this->app->select($this->table, '*', ['id' => $id]);
        if (empty($user)) {
            return $this->app->redirect('/admin/users');
        }
        return $this->app->view('plugins/users/form', ['user' => $user[0]]);
    }
    
    // Auth Methods
    public function login($email, $password) {
        $user = $this->app->select($this->table, '*', ['email' => $email]);
        if (empty($user) || !password_verify($password, $user[0]['password'])) {
            return false;
        }
        
        $this->app->session('user_id', $user[0]['id']);
        return true;
    }
    
    public function logout() {
        $this->app->session('user_id', null);
    }
    
    public function getCurrentUser() {
        $userId = $this->app->session('user_id');
        if (!$userId) return null;
        
        $user = $this->app->select($this->table, '*', ['id' => $userId]);
        return $user[0] ?? null;
    }
} 