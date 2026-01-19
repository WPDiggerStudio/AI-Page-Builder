<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Providers\RouterServiceProvider;
use WPJarvis\Framework\Support\Facades\Route;

class RoutingTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock WP functions
        if (!function_exists('register_rest_route')) {
            require_once __DIR__ . '/../mocks.php';
        }

        global $mock_routes;
        $mock_routes = [];
    }

    public function test_rest_router_registers_routes()
    {
        $app = new Application(__DIR__);
        $app->instance('config', new \Illuminate\Config\Repository([
            'api' => ['namespace' => 'test/v1']
        ]));

        $provider = new RouterServiceProvider($app);
        $app->register($provider);

        // Define route via Facade (need app context)
        // Or directly via service
        $router = $app->make('router.rest');
        $router->get('/test', function() { return 'ok'; });

        $router->registerRoutes();

        global $mock_routes;
        $this->assertCount(1, $mock_routes);
        $this->assertEquals('test/v1', $mock_routes[0]['namespace']);
        $this->assertEquals('/test', $mock_routes[0]['uri']);
    }
}
