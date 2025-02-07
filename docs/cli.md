# CLI - Command Line Interface

O Trindade Framework inclui uma poderosa interface de linha de comando (CLI) para ajudar na gestão e desenvolvimento do projeto.

## Comandos Disponíveis

### Inicialização do Framework

```bash
./trindade init [--force]
```

O comando `init` guia-te através do processo de configuração inicial do framework:

1. **Configuração da Base de Dados**
   - Tipo de base de dados (MySQL)
   - Host
   - Nome da base de dados
   - Utilizador
   - Password
   - Testa a conexão automaticamente
   - Cria a base de dados se não existir

2. **Configuração de Email**
   - Host SMTP
   - Porta SMTP
   - Utilizador
   - Password
   - Email padrão do remetente
   - Nome padrão do remetente

3. **Instalação do Painel Admin**
   - Pergunta se deseja instalar
   - Cria tabela de utilizadores
   - Configura utilizador admin inicial
   - Define permissões básicas

4. **Instalação do Plugin Blog**
   - Pergunta se deseja instalar
   - Cria todas as tabelas necessárias:
     - Categorias
     - Posts
     - Tags
     - Comentários

Opções:
- `--force`: Força a reconfiguração mesmo se já existir

### Criação de Controllers

```bash
./trindade make:controller NomeController [--resource]
```

Cria um novo controller na pasta `app/Controllers`.

Opções:
- `--resource`: Cria um controller com métodos CRUD (index, create, store, show, edit, update, destroy)

### Gestão de Plugins

```bash
./trindade plugin <comando> [nome]
```

Comandos disponíveis:
- `create`: Cria um novo plugin
- `enable`: Ativa um plugin
- `disable`: Desativa um plugin
- `remove`: Remove um plugin

## Exemplos de Uso

### Inicializar o Framework

```bash
./trindade init
```

Exemplo de output:
```
Trindade Framework Initialization
==============================

Database Configuration
---------------------
Database type (mysql): mysql
Database host [localhost]: 
Database name: meu_projeto
Database username: root
Database password: 

Testing connection...
✅ Database connection successful!

Mail Configuration
-----------------
SMTP host [smtp.mailtrap.io]: 
SMTP port [587]: 
SMTP username: meu_usuario
SMTP password: 
Default sender email: no-reply@meusite.com
Default sender name [Trindade]: Meu Site

Do you want to install the Admin Panel? (y/n) [n]: y

Installing Admin Panel
--------------------
Admin name: Admin
Admin email: admin@meusite.com
Admin password: 
✅ Admin panel installed successfully!

Do you want to install the Blog Plugin? (y/n) [n]: y

Installing Blog Plugin
-------------------
✅ Blog plugin installed successfully!

✅ Framework initialized successfully!
You can now start your application.
```

### Criar um Controller

```bash
./trindade make:controller BlogController --resource
```

Cria um novo controller com métodos CRUD:
```php
<?php

namespace App\Controllers;

class BlogController
{
    public function index()
    {
        // GET /blog
        // List all posts
    }
    
    public function create()
    {
        // GET /blog/create
        // Show create form
    }
    
    // ... outros métodos CRUD
}
```

### Criar um Plugin

```bash
./trindade plugin create newsletter
```

Cria a estrutura base de um novo plugin:
```
plugins/newsletter/
├── NewsletterPlugin.php
├── config.php
└── README.md
```

## Boas Práticas

1. **Sempre inicialize primeiro**
   - Execute `./trindade init` antes de qualquer outro comando
   - Certifique-se que a base de dados está configurada corretamente

2. **Use nomes descritivos**
   - Controllers: `UserController`, `ProductController`
   - Plugins: `newsletter`, `payment`, `analytics`

3. **Mantenha plugins organizados**
   - Um plugin por funcionalidade
   - Documente bem no README.md do plugin
   - Mantenha as dependências mínimas

4. **Backup antes de alterações**
   - Faça backup da base de dados antes de instalar plugins
   - Use controle de versão (git) para rastrear alterações

## Resolução de Problemas

### Erro de Conexão com Base de Dados
```
❌ Connection failed: SQLSTATE[HY000] [1045] Access denied
```
- Verifique as credenciais
- Confirme se o servidor MySQL está a correr
- Verifique as permissões do utilizador

### Erro ao Criar Controller
```
Error: Controller already exists
```
- Escolha um nome diferente
- Use `--force` se quiser sobrescrever

### Erro ao Instalar Plugin
```
❌ Error installing plugin: Table already exists
```
- Verifique se o plugin já foi instalado
- Faça backup e use `--force` para reinstalar 