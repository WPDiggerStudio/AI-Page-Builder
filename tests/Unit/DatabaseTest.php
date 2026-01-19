<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Providers\DatabaseServiceProvider;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock WP constants
        if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
        if (!defined('DB_NAME')) define('DB_NAME', 'test');
        if (!defined('DB_USER')) define('DB_USER', 'root');
        if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');

        global $wpdb;
        $wpdb = (object)['prefix' => 'wp_'];
    }

    public function test_database_service_provider_binds_db()
    {
        $app = new Application(__DIR__);
        $app->instance('config', new \Illuminate\Config\Repository([
            'database' => ['default' => 'wordpress']
        ]));

        $provider = new DatabaseServiceProvider($app);
        $provider->register();

        $this->assertTrue($app->bound('db'));
        $this->assertInstanceOf(\Illuminate\Database\DatabaseManager::class, $app->make('db'));
    }
}
