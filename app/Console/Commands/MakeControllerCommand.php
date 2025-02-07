<?php
/**
 * Trindade Framework
 *
 * Make Controller Command
 * 
 * Generates controller class files.
 * Provides scaffolding for new controllers.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Console\Commands;

use Trindade\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller';
    
    protected array $options = [
        '--resource' => 'Create a resource controller with CRUD methods',
        '--force' => 'Force creation even if controller exists'
    ];

    public function execute(array $args): int
    {
        if (empty($args[0])) {
            echo "Controller name is required\n";
            return 1;
        }

        $name = $args[0];
        $resource = in_array('--resource', $args);
        $force = in_array('--force', $args);

        // Normaliza o nome do controller
        if (!str_ends_with(strtolower($name), 'controller')) {
            $name .= 'Controller';
        }
        $name = ucfirst($name);

        $controllerPath = __DIR__ . '/../../../app/Controllers/' . $name . '.php';

        if (file_exists($controllerPath) && !$force) {
            echo "Controller already exists. Use --force to overwrite.\n";
            return 1;
        }

        // Cria diretório se não existir
        if (!is_dir(dirname($controllerPath))) {
            mkdir(dirname($controllerPath), 0777, true);
        }

        // Gera o código do controller
        $code = $this->generateController($name, $resource);
        file_put_contents($controllerPath, $code);

        echo "✅ Controller {$name} created successfully!\n";
        return 0;
    }

    protected function generateController(string $name, bool $resource = false): string
    {
        $namespace = 'App\\Controllers';
        
        if ($resource) {
            return <<<PHP
<?php
namespace {$namespace};

use Trindade\Controller;
use Trindade\Http\Request;
use Trindade\Http\Response;

class {$name} extends Controller {
    /**
     * Lista todos os registros
     */
    public function index(): void {
        // Implementar listagem
        \$this->view('{$this->getViewName($name)}/index');
    }

    /**
     * Mostra o formulário de criação
     */
    public function create(): void {
        \$this->view('{$this->getViewName($name)}/create');
    }

    /**
     * Armazena um novo registro
     */
    public function store(Request \$request): void {
        // Validar e salvar dados
        \$this->redirect('/' . \$this->getViewName($name));
    }

    /**
     * Mostra um registro específico
     */
    public function show(int \$id): void {
        // Buscar registro
        \$this->view('{$this->getViewName($name)}/show', ['id' => \$id]);
    }

    /**
     * Mostra o formulário de edição
     */
    public function edit(int \$id): void {
        // Buscar registro
        \$this->view('{$this->getViewName($name)}/edit', ['id' => \$id]);
    }

    /**
     * Atualiza um registro específico
     */
    public function update(Request \$request, int \$id): void {
        // Validar e atualizar dados
        \$this->redirect('/' . \$this->getViewName($name));
    }

    /**
     * Remove um registro específico
     */
    public function destroy(int \$id): void {
        // Remover registro
        \$this->redirect('/' . \$this->getViewName($name));
    }
}
PHP;
        }

        return <<<PHP
<?php
namespace {$namespace};

use Trindade\Controller;
use Trindade\Http\Request;
use Trindade\Http\Response;

class {$name} extends Controller {
    /**
     * Página inicial do controller
     */
    public function index(): void {
        \$this->view('{$this->getViewName($name)}/index');
    }
}
PHP;
    }

    protected function getViewName(string $controller): string
    {
        // Remove o sufixo Controller e converte para kebab-case
        $name = str_replace('Controller', '', $controller);
        return strtolower(preg_replace('/[A-Z]/', '-$0', lcfirst($name)));
    }
} 