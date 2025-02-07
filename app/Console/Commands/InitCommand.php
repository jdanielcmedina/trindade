<?php
/**
 * Trindade Framework
 *
 * Init Command
 * 
 * Initializes framework configuration.
 * Sets up database, paths and security settings.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Console\Commands;

use Trindade\Console\Command;
use PDO;

class InitCommand extends Command
{
    protected string $name = 'init';
    protected string $description = 'Initialize framework configuration and components';
    
    protected array $options = [
        '--force' => 'Overwrite existing configuration'
    ];

    protected ?PDO $db = null;
    
    public function execute(array $args): int
    {
        echo "\nTrindade Framework Initialization\n";
        echo "==============================\n\n";

        // 1. Database Configuration
        if (!$this->configureDatabaseConnection()) {
            return 1;
        }

        // 2. Create base configuration file
        if (!$this->createBaseConfiguration()) {
            return 1;
        }

        // 3. Create required directories
        $this->createDirectoryStructure();

        // 4. Ask about Admin Panel
        if ($this->confirm("\nDo you want to install the Admin Panel?")) {
            $this->installAdminPanel();
        }

        // 5. Ask about Blog Plugin
        if ($this->confirm("\nDo you want to install the Blog Plugin?")) {
            $this->installBlogPlugin();
        }

        // 6. Mark as initialized
        file_put_contents(__DIR__ . '/../../../.initialized', date('Y-m-d H:i:s'));

        echo "\n✅ Framework initialized successfully!\n";
        echo "You can now start your application.\n\n";

        return 0;
    }
    
    protected function configureDatabaseConnection(): bool
    {
        echo "Database Configuration\n";
        echo "---------------------\n";

        do {
            $config = $this->getDatabaseConfig();
            
            echo "\nTesting connection...\n";
            
            try {
                $this->db = new PDO(
                    "{$config['type']}:host={$config['host']};charset={$config['charset']}",
                    $config['username'],
                    $config['password']
                );
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Tenta criar a base de dados se não existir
                $this->db->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}`");
                $this->db->exec("USE `{$config['database']}`");
                
                echo "✅ Database connection successful!\n";
                $this->dbConfig = $config;
                return true;
                
            } catch (\PDOException $e) {
                echo "❌ Connection failed: " . $e->getMessage() . "\n";
                if (!$this->confirm("Do you want to try again?")) {
                    return false;
                }
            }
        } while (true);
    }
    
    protected function getDatabaseConfig(): array
    {
        return [
            'type' => $this->prompt('Database type (mysql)', 'mysql'),
            'host' => $this->prompt('Database host', 'localhost'),
            'database' => $this->prompt('Database name'),
            'username' => $this->prompt('Database username'),
            'password' => $this->prompt('Database password', '', true),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => ''
        ];
    }
    
    protected function createBaseConfiguration(): bool
    {
        $config = [
            'database' => $this->dbConfig,
            'mail' => [
                'host' => $this->prompt('SMTP host', 'smtp.mailtrap.io'),
                'port' => $this->prompt('SMTP port', '587'),
                'username' => $this->prompt('SMTP username'),
                'password' => $this->prompt('SMTP password', '', true),
                'from' => [
                    'address' => $this->prompt('Default sender email'),
                    'name' => $this->prompt('Default sender name', 'Trindade')
                ]
            ],
            'key' => bin2hex(random_bytes(32))
        ];

        $template = file_get_contents(__DIR__ . '/stubs/config.stub');
        $configContent = $this->generateConfig($config);
        
        file_put_contents(__DIR__ . '/../../../config.php', $configContent);
        return true;
    }
    
    protected function createDirectoryStructure(): void
    {
        $dirs = [
            'storage/cache',
            'storage/logs',
            'storage/uploads',
            'app/Controllers',
            'app/Models',
            'views',
            'plugins'
        ];
        
        foreach ($dirs as $dir) {
            $path = __DIR__ . '/../../../' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
                file_put_contents($path . '/.gitkeep', '');
            }
        }
    }
    
    protected function installAdminPanel(): void
    {
        echo "\nInstalling Admin Panel\n";
        echo "--------------------\n";

        // Criar tabela de utilizadores
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        try {
            $this->db->exec($sql);
            
            // Criar utilizador admin
            $name = $this->prompt('Admin name');
            $email = $this->prompt('Admin email');
            $password = password_hash(
                $this->prompt('Admin password', '', true),
                PASSWORD_DEFAULT
            );
            
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$name, $email, $password]);
            
            echo "✅ Admin panel installed successfully!\n";
            
        } catch (\PDOException $e) {
            echo "❌ Error installing admin panel: " . $e->getMessage() . "\n";
        }
    }
    
    protected function installBlogPlugin(): void
    {
        echo "\nInstalling Blog Plugin\n";
        echo "-------------------\n";

        try {
            // Criar tabelas do blog
            $this->db->exec($this->getBlogTables());
            echo "✅ Blog plugin installed successfully!\n";
            
        } catch (\PDOException $e) {
            echo "❌ Error installing blog plugin: " . $e->getMessage() . "\n";
        }
    }
    
    protected function getBlogTables(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category_id INT,
    author_id INT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id INT,
    tag_id INT,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }
    
    protected function prompt(string $question, string $default = '', bool $hidden = false): string
    {
        $defaultText = $default ? " [{$default}]" : '';
        echo "{$question}{$defaultText}: ";
        
        if ($hidden) {
            system('stty -echo');
        }
        
        $answer = trim(fgets(STDIN));
        
        if ($hidden) {
            system('stty echo');
            echo "\n";
        }
        
        return $answer ?: $default;
    }
    
    protected function confirm(string $question): bool
    {
        $answer = $this->prompt($question . ' (y/n)', 'n');
        return strtolower($answer[0] ?? '') === 'y';
    }
    
    protected function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    protected function generateConfig(array $data): string
    {
        $template = file_get_contents(__DIR__ . '/stubs/config.stub');
        
        return strtr($template, [
            '{{DB_TYPE}}' => $data['database']['type'],
            '{{DB_HOST}}' => $data['database']['host'],
            '{{DB_NAME}}' => $data['database']['database'],
            '{{DB_USER}}' => $data['database']['username'],
            '{{DB_PASS}}' => $data['database']['password'],
            '{{MAIL_HOST}}' => $data['mail']['host'],
            '{{MAIL_PORT}}' => $data['mail']['port'],
            '{{MAIL_USER}}' => $data['mail']['username'],
            '{{MAIL_PASS}}' => $data['mail']['password'],
            '{{MAIL_FROM}}' => $data['mail']['from']['address'],
            '{{MAIL_NAME}}' => $data['mail']['from']['name'],
            '{{APP_KEY}}' => $data['key']
        ]);
    }
} 