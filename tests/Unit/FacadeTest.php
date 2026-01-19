<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;
use WPJarvis\Framework\Support\Facades\Route;

class FacadeTest extends TestCase
{
    protected function setUp(): void
    {
        AppRegistry::flush();
    }

    public function test_facades_resolve_context_aware_app()
    {
        $appA = new Application(__DIR__);
        $appA->instance('router.rest', (object)['name' => 'Router A']);
        // Bind 'router' alias which RouterServiceProvider normally does
        $appA->bind('router', function($app) { return $app['router.rest']; });
        AppRegistry::register('plugin-a', $appA);

        $appB = new Application(__DIR__);
        $appB->instance('router.rest', (object)['name' => 'Router B']);
        $appB->bind('router', function($app) { return $app['router.rest']; });
        AppRegistry::register('plugin-b', $appB);

        // Define constant for test context
        if (!defined('WPJARVIS_CURRENT_APP_KEY')) {
            define('WPJARVIS_CURRENT_APP_KEY', 'plugin-a');
        }

        // Should resolve Plugin A
        $router = Route::getFacadeRoot();
        $this->assertEquals('Router A', $router->name);
    }
}
