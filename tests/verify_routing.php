<?php

require __DIR__ . '/../vendor/autoload.php';

use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;
use WPJarvis\Framework\Providers\RouterServiceProvider;
use WPJarvis\Framework\Support\Facades\Route;

// Reset registry
AppRegistry::flush();

echo "Creating App...\n";
 = new Application(__DIR__ . '/..'); // Point to root so it finds routes/api.php
AppRegistry::register('app', );

// Simulate config
->instance('config', new \Illuminate\Config\Repository([
    'api' => ['namespace' => 'test/v1']
]));

echo "Registering RouterServiceProvider...\n";
 = new RouterServiceProvider();
->register();
->boot(); // Registers 'rest_api_init' hook

// Mock WordPress functions
if (!function_exists('register_rest_route')) {
    function register_rest_route(, , ) {
        echo "WP: Registering route: [$namespace] $uri\n";
        global ;
        [] = compact('namespace', 'uri', 'args');
    }
}
if (!function_exists('add_action')) {
    function add_action(, ) {
        echo "WP: Added action: $hook\n";
        global ;
        [][] = ;
    }
}

// Manually trigger the 'rest_api_init' hook callback
echo "Triggering loadApiRoutes via hook callback...\n";
global ;
if (isset(['rest_api_init'])) {
    foreach (['rest_api_init'] as ) {
        call_user_func();
    }
} else {
    echo "FAIL: rest_api_init hook was not registered.\n";
    exit(1);
}

// Check if routes were registered in the mock
global ;
if (empty()) {
    // If api.php is empty or missing, this might happen. Let's force add one via Facade to test.
    echo "Adding route via Facade...\n";
    if (!defined('WPJARVIS_CURRENT_APP_KEY')) define('WPJARVIS_CURRENT_APP_KEY', 'app');

    Route::get('/facade-test', function() { return 'ok'; });

    // Manually call registerRoutes on the router since the hook callback only loads the file
    // In the real flow: hook -> loadApiRoutes -> require api.php (which does Route::get) -> registerRoutes()
    // Since we just called Route::get manually, we need to trigger registration.
    ->make('router')->registerRoutes();
}

if (!empty()) {
    echo "SUCCESS: Routes registered.\n";
    print_r();
} else {
    echo "FAIL: No routes registered.\n";
    exit(1);
}
