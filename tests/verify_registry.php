<?php

require __DIR__ . '/../vendor/autoload.php';

use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;

// Reset registry
AppRegistry::flush();

echo "Creating App A...\n";
 = new Application(__DIR__);
AppRegistry::register('plugin-a', );

echo "Creating App B...\n";
 = new Application(__DIR__);
AppRegistry::register('plugin-b', );

// Verify Registry retrieval
if (AppRegistry::get('plugin-a') !== ) {
    echo "FAIL: AppRegistry::get('plugin-a') returned wrong instance.\n";
    exit(1);
}

if (AppRegistry::get('plugin-b') !== ) {
    echo "FAIL: AppRegistry::get('plugin-b') returned wrong instance.\n";
    exit(1);
}

// Verify Helper (simulating wpj_app behavior)
// Note: We can't easily test wpj_app helper directly if it relies on 'Application::getInstance()' fallback or global defines without defining them.
// But we can test explicit key passing.

 = wpj_app(null, [], 'plugin-a');
if ( !== ) {
    echo "FAIL: wpj_app(..., 'plugin-a') returned wrong instance.\n";
    exit(1);
}

 = wpj_app(null, [], 'plugin-b');
if ( !== ) {
    echo "FAIL: wpj_app(..., 'plugin-b') returned wrong instance.\n";
    exit(1);
}

echo "SUCCESS: Registry verification passed.\n";
