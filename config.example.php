<?php
/**
 * Configuração do Framework Trindade
 * Copia este ficheiro para config.php e ajusta as configurações
 */

return [
    // Modo debug (true em desenvolvimento, false em produção)
    'debug' => true,
    
    // Caminhos da aplicação
    'paths' => [
        // Pasta das views
        'views' => __DIR__ . '/views',
        
        // Pasta de cache
        'cache' => __DIR__ . '/storage/cache',
        
        // Pasta de logs
        'logs' => __DIR__ . '/storage/logs',
        
        // Pasta de uploads
        'uploads' => __DIR__ . '/storage/uploads',
        
        // Pasta pública (css, js, imagens)
        'public' => __DIR__ . '/public',
        
        // Pasta de traduções
        'lang' => __DIR__ . '/lang'
    ],
    
    // Configuração MySQL via Medoo
    'mysql' => [
        'host' => 'localhost',
        'database' => 'minha_bd',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'port' => 3306,
        // Opções PDO adicionais
        'options' => [
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    // Configuração de Email
    'mail' => [
        // Nome do remetente
        'fromName' => 'Minha Aplicação',
        
        // Email do remetente
        'username' => 'email@dominio.com',
        
        // Password do email
        'password' => 'password_segura',
        
        // Configuração SMTP
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'auth' => true,
            'secure' => 'tls' // tls ou ssl
        ]
    ],
    
    // Configurações de Cache
    'cache' => [
        // Tempo padrão de cache em segundos (1 hora)
        'ttl' => 3600,
        
        // Prefixo para as chaves de cache
        'prefix' => 'app_'
    ],
    
    // Configurações de Sessão
    'session' => [
        // Nome da sessão
        'name' => 'TRINDADE',
        
        // Tempo de vida da sessão em segundos (2 horas)
        'lifetime' => 7200,
        
        // Caminho da cookie
        'path' => '/',
        
        // Domínio da cookie
        'domain' => '',
        
        // Usar apenas em HTTPS
        'secure' => false,
        
        // Cookie acessível apenas via HTTP
        'httponly' => true
    ],
    
    // Configurações de Cookie
    'cookie' => [
        // Tempo de vida padrão em segundos (30 dias)
        'lifetime' => 2592000,
        
        // Caminho da cookie
        'path' => '/',
        
        // Domínio da cookie
        'domain' => '',
        
        // Usar apenas em HTTPS
        'secure' => false,
        
        // Cookie acessível apenas via HTTP
        'httponly' => true,
        
        // Política SameSite (Lax, Strict, None)
        'samesite' => 'Lax'
    ],
    
    // Configurações de Upload
    'upload' => [
        // Tamanho máximo em bytes (2MB)
        'maxSize' => 2 * 1024 * 1024,
        
        // Tipos MIME permitidos
        'allowedTypes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        
        // Extensões permitidas
        'allowedExtensions' => [
            'jpg', 'jpeg', 'png', 'gif',
            'pdf', 'doc', 'docx'
        ]
    ],
    
    // Configurações de Assets
    'assets' => [
        // Usar versionamento de assets
        'useVersioning' => true,
        
        // Minificar assets em produção
        'minify' => true
    ],
    
    // Configurações de Logging
    'logging' => [
        // Nível mínimo de log
        'level' => 'debug',
        
        // Formato da data nos logs
        'dateFormat' => 'Y-m-d H:i:s',
        
        // Rotação de logs (em dias)
        'rotate' => 30
    ]
]; 