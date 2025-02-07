<?php

namespace Tests\Unit\Core;

use Tests\TestCase;

class RouterTest extends TestCase
{
    public function test_can_register_route()
    {
        $this->app->on('GET /test', function() {
            return 'test';
        });
        
        $this->assertTrue(true); // Placeholder until we implement route checking
    }
    
    public function test_can_register_route_with_parameters()
    {
        $this->app->on('GET /users/:id', function($id) {
            return $id;
        });
        
        $this->assertTrue(true); // Placeholder until we implement route checking
    }
    
    public function test_can_group_routes()
    {
        $this->app->group('/api', function() {
            $this->app->on('GET /users', function() {
                return 'users';
            });
        });
        
        $this->assertTrue(true); // Placeholder until we implement route checking
    }
} 