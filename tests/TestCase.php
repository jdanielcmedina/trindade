<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Trindade\Core;

abstract class TestCase extends BaseTestCase
{
    protected ?Core $app = null;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $config = require __DIR__ . '/../config.php';
        $this->app = new Core($config);
    }
    
    protected function tearDown(): void
    {
        $this->app = null;
        parent::tearDown();
    }
    
    protected function createApplication(): Core
    {
        $config = require __DIR__ . '/../config.php';
        return new Core($config);
    }
} 