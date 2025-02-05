<?php
namespace Trindade\Plugins;

class ApiTest {
    protected $app;
    protected $table = 'posts';
    
    public function __construct($app) {
        $this->app = $app;
        $this->setupRoutes();
    }
    
    protected function setupRoutes(): void {
        $this->app->group('/api-test', function($app) {
            // Listar todos
            $app->on('GET /posts', [$this, 'list']);
            
            // Obter um
            $app->on('GET /posts/:id', [$this, 'get']);
            
            // Criar
            $app->on('POST /posts', [$this, 'create']);
            
            // Atualizar
            $app->on('PUT /posts/:id', [$this, 'update']);
            
            // Apagar
            $app->on('DELETE /posts/:id', [$this, 'delete']);
            
            // Rota de teste
            $app->on('GET /test', function() {
                return $this->json([
                    'status' => 'success',
                    'message' => 'API está funcionando!',
                    'timestamp' => time()
                ]);
            });
        });
    }
    
    // Listar todos os posts
    public function list() {
        $posts = $this->app->select($this->table, '*', [
            'ORDER' => ['created_at' => 'DESC']
        ]);
        
        return $this->app->json([
            'status' => 'success',
            'data' => $posts,
            'count' => count($posts)
        ]);
    }
    
    // Obter um post específico
    public function get($id) {
        $post = $this->app->select($this->table, '*', ['id' => $id]);
        
        if (empty($post)) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Post não encontrado'
            ], 404);
        }
        
        return $this->app->json([
            'status' => 'success',
            'data' => $post[0]
        ]);
    }
    
    // Criar um novo post
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validação básica
        if (empty($data['title']) || empty($data['content'])) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Título e conteúdo são obrigatórios',
                'received' => $data
            ], 400);
        }
        
        // Adiciona timestamp
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $id = $this->app->insert($this->table, $data);
        
        if (!$id) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Erro ao criar post'
            ], 500);
        }
        
        return $this->app->json([
            'status' => 'success',
            'message' => 'Post criado com sucesso',
            'data' => ['id' => $id]
        ], 201);
    }
    
    // Atualizar um post
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verifica se post existe
        $post = $this->app->select($this->table, '*', ['id' => $id]);
        if (empty($post)) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Post não encontrado'
            ], 404);
        }
        
        // Adiciona timestamp de atualização
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $success = $this->app->update($this->table, $data, ['id' => $id]);
        
        if (!$success) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar post'
            ], 500);
        }
        
        return $this->app->json([
            'status' => 'success',
            'message' => 'Post atualizado com sucesso'
        ]);
    }
    
    // Apagar um post
    public function delete($id) {
        // Verifica se post existe
        $post = $this->app->select($this->table, '*', ['id' => $id]);
        if (empty($post)) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Post não encontrado'
            ], 404);
        }
        
        $success = $this->app->delete($this->table, ['id' => $id]);
        
        if (!$success) {
            return $this->app->json([
                'status' => 'error',
                'message' => 'Erro ao apagar post'
            ], 500);
        }
        
        return $this->app->json([
            'status' => 'success',
            'message' => 'Post apagado com sucesso'
        ]);
    }
} 