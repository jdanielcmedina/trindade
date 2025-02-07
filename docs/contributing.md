# Guia de Contribuição

Obrigado por considerar contribuir para o Trindade Framework! Este documento fornece as diretrizes para contribuir com o projeto.

## Código de Conduta

Este projeto e todos os participantes estão sob o [Código de Conduta](CODE_OF_CONDUCT.md). Ao participar, espera-se que você mantenha este código.

## Como Posso Contribuir?

### Reportando Bugs

1. Verifique se o bug já não foi reportado na seção de [Issues](https://github.com/trindade/framework/issues)
2. Se não encontrar uma issue aberta, [crie uma nova](https://github.com/trindade/framework/issues/new)
3. Inclua um título e descrição clara
4. Forneça o máximo de informações relevantes possível:
   - Versão do PHP
   - Versão do Framework
   - Código que reproduz o problema
   - Logs de erro
   - Sistema operacional

### Sugerindo Melhorias

1. Primeiro, leia a documentação para ter certeza que a funcionalidade já não existe
2. Verifique se a melhoria já não foi sugerida nas [Issues](https://github.com/trindade/framework/issues)
3. Crie uma nova issue descrevendo sua sugestão
4. Inclua casos de uso e benefícios da nova funcionalidade

### Pull Requests

1. Fork o repositório
2. Crie um novo branch para sua feature:
   ```bash
   git checkout -b feature/MinhaFeature
   ```
3. Faça suas alterações
4. Execute os testes:
   ```bash
   composer test
   ```
5. Commit suas mudanças:
   ```bash
   git commit -m 'Adiciona nova feature'
   ```
6. Push para o branch:
   ```bash
   git push origin feature/MinhaFeature
   ```
7. Abra um Pull Request

## Estilo de Código

### PHP

- PSR-1: Basic Coding Standard
- PSR-2: Coding Style Guide
- PSR-4: Autoloading Standard
- PSR-12: Extended Coding Style

```php
<?php
namespace Trindade\Component;

class MinhaClasse
{
    private $propriedade;
    
    public function __construct()
    {
        $this->propriedade = true;
    }
    
    public function meuMetodo(): bool
    {
        if ($this->propriedade) {
            return true;
        }
        
        return false;
    }
}
```

### Documentação

- Use DocBlocks para classes e métodos
- Mantenha a documentação atualizada
- Escreva exemplos claros

```php
/**
 * Classe responsável por gerenciar usuários
 *
 * @package Trindade\Auth
 */
class UserManager
{
    /**
     * Cria um novo usuário
     *
     * @param array $data Dados do usuário
     * @return int ID do usuário criado
     * @throws \Exception Se os dados forem inválidos
     */
    public function create(array $data): int
    {
        // implementação
    }
}
```

## Testes

### Escrevendo Testes

```php
namespace Tests\Components;

use PHPUnit\Framework\TestCase;
use Trindade\Components\Database;

class DatabaseTest extends TestCase
{
    protected $db;
    
    protected function setUp(): void
    {
        $this->db = new Database([
            'driver' => 'sqlite',
            'database' => ':memory:'
        ]);
    }
    
    public function testSelect()
    {
        $result = $this->db->select('users', '*');
        $this->assertIsArray($result);
    }
}
```

### Executando Testes

```bash
# Todos os testes
composer test

# Teste específico
./vendor/bin/phpunit tests/Components/DatabaseTest.php

# Com coverage
composer test-coverage
```

## Git Workflow

1. **Branches**
   - `main`: branch principal, sempre estável
   - `develop`: branch de desenvolvimento
   - `feature/*`: novas funcionalidades
   - `bugfix/*`: correções de bugs
   - `release/*`: preparação para release

2. **Commits**
   - Use mensagens claras e descritivas
   - Use o presente do indicativo
   - Primeira linha com no máximo 50 caracteres
   - Corpo do commit com 72 caracteres por linha

3. **Merge Requests**
   - Mantenha MRs pequenos e focados
   - Descreva claramente as mudanças
   - Inclua testes relevantes
   - Atualize a documentação

## Releases

1. **Versionamento**
   - Seguimos [Semantic Versioning](https://semver.org/)
   - MAJOR.MINOR.PATCH
   - Ex: 1.0.0, 1.1.0, 1.1.1

2. **Changelog**
   - Mantenha o CHANGELOG.md atualizado
   - Use seções: Added, Changed, Deprecated, Removed, Fixed, Security

3. **Tags**
   ```bash
   git tag -a v1.0.0 -m "Versão 1.0.0"
   git push origin v1.0.0
   ```

## Documentação

1. **README.md**
   - Visão geral do projeto
   - Instruções de instalação
   - Exemplos básicos
   - Links úteis

2. **Documentação Técnica**
   - Mantenha a pasta `docs/` atualizada
   - Use Markdown para documentação
   - Inclua exemplos práticos
   - Documente breaking changes

3. **DocBlocks**
   - Documente todas as classes
   - Documente métodos públicos
   - Inclua tipos de parâmetros e retorno
   - Documente exceções

## Segurança

- **Reportando Vulnerabilidades**
  - NÃO abra issues públicas para vulnerabilidades
  - Envie um email para security@trindade.dev
  - Inclua o máximo de detalhes possível
  - Aguarde confirmação antes de divulgar

## Próximos Passos

- Leia o [Guia de Segurança](security.md)
- Explore os [Componentes do Framework](components.md)
- Veja as [Melhores Práticas](best-practices.md) 