<?php
namespace Trindade\Console\Commands;

use Trindade\Console\Command;

class MakeModelCommand extends Command {
    protected string $name = 'make:model';
    protected string $description = 'Create a new model';
    
    protected array $options = [
        '--migration' => 'Create a migration for the model',
        '--force' => 'Force creation even if model exists'
    ];

    public function execute(array $args): int {
        if (empty($args[0])) {
            echo "Model name is required\n";
            return 1;
        }

        $name = ucfirst($args[0]);
        $migration = in_array('--migration', $args);
        $force = in_array('--force', $args);

        $modelPath = __DIR__ . '/../../../app/Models/' . $name . '.php';

        if (file_exists($modelPath) && !$force) {
            echo "Model already exists. Use --force to overwrite.\n";
            return 1;
        }

        // Cria diretório se não existir
        if (!is_dir(dirname($modelPath))) {
            mkdir(dirname($modelPath), 0777, true);
        }

        // Gera o código do modelo
        $code = $this->generateModel($name);
        file_put_contents($modelPath, $code);

        echo "✅ Model {$name} created successfully!\n";

        // Cria migration se solicitado
        if ($migration) {
            $this->createMigration($name);
        }

        return 0;
    }

    protected function generateModel(string $name): string {
        $tableName = $this->getTableName($name);
        
        return <<<PHP
<?php
namespace App\Models;

use Trindade\Model;

class {$name} extends Model {
    /**
     * Nome da tabela associada ao modelo
     */
    protected string \$table = '{$tableName}';
    
    /**
     * Chave primária da tabela
     */
    protected string \$primaryKey = 'id';
    
    /**
     * Indica se o modelo deve gerenciar timestamps
     */
    protected bool \$timestamps = true;
    
    /**
     * Atributos que podem ser preenchidos em massa
     */
    protected array \$fillable = [
        // Lista de campos preenchíveis
    ];
    
    /**
     * Atributos que devem ser convertidos para tipos nativos
     */
    protected array \$casts = [
        'id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Regras de validação para os atributos
     */
    protected array \$rules = [
        // 'campo' => 'required|min:3|max:255'
    ];
}
PHP;
    }

    protected function createMigration(string $modelName): void {
        $timestamp = date('Y_m_d_His');
        $tableName = $this->getTableName($modelName);
        $migrationName = "create_{$tableName}_table";
        $className = str_replace([' ', '-', '_'], '', ucwords($migrationName, ' -_'));
        
        $migrationPath = __DIR__ . '/../../../database/migrations/' . $timestamp . '_' . $migrationName . '.php';
        
        // Cria diretório se não existir
        if (!is_dir(dirname($migrationPath))) {
            mkdir(dirname($migrationPath), 0777, true);
        }
        
        $code = <<<PHP
<?php
use Trindade\Database\Migration;
use Trindade\Database\Schema;
use Trindade\Database\Table;

class {$className} extends Migration {
    /**
     * Executa a migração
     */
    public function up(): void {
        Schema::create('{$tableName}', function(Table \$table) {
            \$table->id();
            // Adicione suas colunas aqui
            \$table->timestamps();
        });
    }
    
    /**
     * Reverte a migração
     */
    public function down(): void {
        Schema::dropIfExists('{$tableName}');
    }
}
PHP;

        file_put_contents($migrationPath, $code);
        echo "✅ Migration created successfully: {$migrationName}\n";
    }

    protected function getTableName(string $modelName): string {
        // Converte o nome do modelo para snake_case e pluraliza
        $tableName = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($modelName)));
        return $tableName . 's';
    }
} 