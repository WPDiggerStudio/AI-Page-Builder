<?php

require __DIR__ . '/../vendor/autoload.php';

use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;
use WPJarvis\Framework\Support\Facades\Route;

// Reset registry
AppRegistry::flush();

echo "Creating App A...\n";
 = new Application(__DIR__);
->instance('router', (object)['name' => 'Router A']);
AppRegistry::register('plugin-a', );

echo "Creating App B...\n";
 = new Application(__DIR__);
->instance('router', (object)['name' => 'Router B']);
AppRegistry::register('plugin-b', );

// Test Context Switching via Constant (Simulation)
// Note: We can't redefine constants at runtime easily, so we rely on explicit app resolution logic if possible
// Or we test ScopedFacade::getFacadeRoot() logic via a subclass or mock if needed.
// However, since we implemented 'WPJARVIS_CURRENT_APP_KEY' check in ScopedFacade, we can try to define it if not defined.

if (!defined('WPJARVIS_CURRENT_APP_KEY')) {
    define('WPJARVIS_CURRENT_APP_KEY', 'plugin-a');
}

echo "Testing Facade Resolution for Plugin A...\n";
// Route::getFacadeRoot() should resolve to App A's router because of the constant
try {
     = Route::getFacadeRoot();
    if (->name !== 'Router A') {
        echo "FAIL: Route facade resolved wrong router: " . ->name . "\n";
        exit(1);
    }
} catch (\Exception ) {
    echo "FAIL: Exception resolving facade: " . ->getMessage() . "\n";
    exit(1);
}

echo "SUCCESS: Facade scoping verified.\n";
