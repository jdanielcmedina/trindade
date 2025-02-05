# Plugin ApiTest

Plugin de exemplo para testar API REST com respostas JSON.

## Instalação

1. Cria a tabela necessária:
```sql
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
);
```

2. Carrega o plugin no teu `index.php`:
```php
$app = new Trindade\Trindade();
$apiTest = $app->plugin('ApiTest');
```

## Rotas Disponíveis

### Testar API
```http
GET /api-test/test

Resposta:
{
    "status": "success",
    "message": "API está funcionando!",
    "timestamp": 1234567890
}
```

### Listar Posts
```http
GET /api-test/posts

Resposta:
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "title": "Primeiro Post",
            "content": "Conteúdo do post...",
            "created_at": "2024-01-20 10:00:00"
        }
    ],
    "count": 1
}
```

### Obter Post
```http
GET /api-test/posts/1

Resposta:
{
    "status": "success",
    "data": {
        "id": 1,
        "title": "Primeiro Post",
        "content": "Conteúdo do post...",
        "created_at": "2024-01-20 10:00:00"
    }
}
```

### Criar Post
```http
POST /api-test/posts
Content-Type: application/json

{
    "title": "Novo Post",
    "content": "Conteúdo do novo post..."
}

Resposta:
{
    "status": "success",
    "message": "Post criado com sucesso",
    "data": {
        "id": 2
    }
}
```

### Atualizar Post
```http
PUT /api-test/posts/1
Content-Type: application/json

{
    "title": "Título Atualizado",
    "content": "Conteúdo atualizado..."
}

Resposta:
{
    "status": "success",
    "message": "Post atualizado com sucesso"
}
```

### Apagar Post
```http
DELETE /api-test/posts/1

Resposta:
{
    "status": "success",
    "message": "Post apagado com sucesso"
}
```

## Testar com cURL

### Testar API
```bash
curl http://localhost/api-test/test
```

### Listar Posts
```bash
curl http://localhost/api-test/posts
```

### Obter Post
```bash
curl http://localhost/api-test/posts/1
```

### Criar Post
```bash
curl -X POST http://localhost/api-test/posts \
  -H "Content-Type: application/json" \
  -d '{"title":"Novo Post","content":"Conteúdo do post..."}'
```

### Atualizar Post
```bash
curl -X PUT http://localhost/api-test/posts/1 \
  -H "Content-Type: application/json" \
  -d '{"title":"Título Atualizado","content":"Conteúdo atualizado..."}'
```

### Apagar Post
```bash
curl -X DELETE http://localhost/api-test/posts/1
```

## Códigos de Status

- 200: Sucesso
- 201: Criado com sucesso
- 400: Erro de validação
- 404: Não encontrado
- 500: Erro interno 