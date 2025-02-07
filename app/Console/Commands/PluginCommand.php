<?php
/**
 * Trindade Framework
 *
 * Plugin Command
 * 
 * Manages framework plugins.
 * Create, enable, disable and remove plugins.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Console\Commands;

use Trindade\Console\Command;

class PluginCommand extends Command
{
    protected string $name = 'plugin';
    protected string $description = 'Manage framework plugins';
    
    protected array $options = [
        '--force' => 'Force operation'
    ];

    public function execute(array $args): int
    {
        if (empty($args[0])) {
            echo "Usage: ./trindade plugin <command> [name] [--force]\n";
            echo "\nCommands:\n";
            echo "  create  Create a new plugin\n";
            echo "  enable  Enable a plugin\n";
            echo "  disable Disable a plugin\n";
            echo "  remove  Remove a plugin\n";
            return 1;
        }

        $command = $args[0];
        $name = $args[1] ?? null;
        $force = in_array('--force', $args);

        switch ($command) {
            case 'create':
                return $this->createPlugin($name, $force);
            case 'enable':
                return $this->enablePlugin($name);
            case 'disable':
                return $this->disablePlugin($name);
            case 'remove':
                return $this->removePlugin($name, $force);
            default:
                echo "Unknown command: {$command}\n";
                return 1;
        }
    }
    
    protected function createPlugin(?string $name, bool $force = false): int
    {
        if (!$name) {
            echo "Plugin name is required\n";
            return 1;
        }

        // Normaliza o nome do plugin
        $name = ucfirst($name);
        $pluginDir = __DIR__ . '/../../../plugins/' . strtolower($name);

        if (is_dir($pluginDir) && !$force) {
            echo "Plugin already exists. Use --force to overwrite.\n";
            return 1;
        }

        // Cria diretório do plugin
        if (!is_dir($pluginDir)) {
            mkdir($pluginDir, 0777, true);
        }

        // Cria arquivo principal do plugin
        $pluginFile = <<<PHP
<?php
namespace Plugins\\{$name};

use Trindade\Plugin;

class {$name}Plugin extends Plugin {
    protected string \$name = '{$name}';
    protected string \$description = 'Description of {$name} plugin';
    protected string \$version = '1.0.0';
    protected string \$author = 'Your Name';
    protected array \$dependencies = [];
    
    public function __construct(array \$config = []) {
        parent::__construct(\$config);
    }
    
    public function initialize(): void {
        // Inicialização do plugin
    }
    
    public function install(): void {
        // Instalação do plugin (criar tabelas, etc)
    }
    
    public function uninstall(): void {
        // Desinstalação do plugin (remover tabelas, etc)
    }
}
PHP;

        file_put_contents($pluginDir . '/' . $name . 'Plugin.php', $pluginFile);

        // Cria arquivo de configuração
        $configFile = <<<PHP
<?php
return [
    'enabled' => true,
    // Outras configurações do plugin
];
PHP;

        file_put_contents($pluginDir . '/config.php', $configFile);

        // Cria README.md
        $readmeFile = <<<MD
# {$name} Plugin

Descrição do plugin {$name}.

## Instalação

1. Copie o plugin para a pasta `plugins/{$name}`
2. Ative o plugin:
   ```bash
   ./trindade plugin enable {$name}
   ```

## Configuração

Edite o arquivo `plugins/{$name}/config.php`:

```php
return [
    'enabled' => true,
    // Suas configurações aqui
];
```

## Uso

Descreva como usar o plugin.

## Licença

MIT
MD;

        file_put_contents($pluginDir . '/README.md', $readmeFile);

        echo "✅ Plugin {$name} created successfully!\n";
        return 0;
    }
    
    protected function enablePlugin(?string $name): int
    {
        if (!$name) {
            echo "Plugin name is required\n";
            return 1;
        }

        $name = strtolower($name);
        $pluginDir = __DIR__ . '/../../../plugins/' . $name;
        $configFile = $pluginDir . '/config.php';

        if (!is_dir($pluginDir)) {
            echo "Plugin not found: {$name}\n";
            return 1;
        }

        if (!file_exists($configFile)) {
            echo "Plugin configuration not found\n";
            return 1;
        }

        $config = require $configFile;
        $config['enabled'] = true;

        file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");

        echo "✅ Plugin {$name} enabled successfully!\n";
        return 0;
    }
    
    protected function disablePlugin(?string $name): int
    {
        if (!$name) {
            echo "Plugin name is required\n";
            return 1;
        }

        $name = strtolower($name);
        $pluginDir = __DIR__ . '/../../../plugins/' . $name;
        $configFile = $pluginDir . '/config.php';

        if (!is_dir($pluginDir)) {
            echo "Plugin not found: {$name}\n";
            return 1;
        }

        if (!file_exists($configFile)) {
            echo "Plugin configuration not found\n";
            return 1;
        }

        $config = require $configFile;
        $config['enabled'] = false;

        file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");

        echo "✅ Plugin {$name} disabled successfully!\n";
        return 0;
    }
    
    protected function removePlugin(?string $name, bool $force = false): int
    {
        if (!$name) {
            echo "Plugin name is required\n";
            return 1;
        }

        $name = strtolower($name);
        $pluginDir = __DIR__ . '/../../../plugins/' . $name;

        if (!is_dir($pluginDir)) {
            echo "Plugin not found: {$name}\n";
            return 1;
        }

        if (!$force) {
            echo "This will remove the plugin and all its data.\n";
            echo "Are you sure? (y/n) [n]: ";
            $answer = trim(fgets(STDIN));
            if (strtolower($answer[0] ?? '') !== 'y') {
                echo "Operation cancelled\n";
                return 1;
            }
        }

        // Remove o diretório do plugin
        $this->removeDirectory($pluginDir);

        echo "✅ Plugin {$name} removed successfully!\n";
        return 0;
    }
    
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
} 