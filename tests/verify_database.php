<?php

require __DIR__ . '/../vendor/autoload.php';

use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;
use WPJarvis\Framework\Providers\DatabaseServiceProvider;

// Mock WP Environment
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'test_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

class MockWPDB { public string $prefix = 'wp_'; }
global $wpdb;
$wpdb = new MockWPDB();

// Reset registry
AppRegistry::flush();

echo "Creating App...\n";
$app = new Application(__DIR__);
AppRegistry::register('app', $app);

// Simulate Config
$app->instance('config', new \Illuminate\Config\Repository([
    'database' => [
        'default' => 'wordpress',
        'connections' => [
            'wordpress' => [] // Will be filled by provider
        ]
    ]
]));

echo "Checking Database Binding...\n";
// The provider is registered in Application constructor via registerBaseServiceProviders -> DatabaseServiceProvider
// So 'db' should be bound.

if ($app->bound('db')) {
    echo "SUCCESS: 'db' is bound.\n";
    try {
        $db = $app->make('db');
        echo "Resolved 'db' to: " . get_class($db) . "\n";

        if ($db instanceof \Illuminate\Database\DatabaseManager) {
             echo "SUCCESS: Resolved instance is DatabaseManager.\n";
        } else {
             echo "FAIL: Resolved instance is NOT DatabaseManager.\n";
             exit(1);
        }
    } catch (\Exception $e) {
        echo "FAIL: Exception resolving db: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "FAIL: 'db' is NOT bound.\n";
    exit(1);
}
