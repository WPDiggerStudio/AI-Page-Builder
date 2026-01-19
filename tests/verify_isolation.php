<?php

require __DIR__ . '/../vendor/autoload.php';

use WPJarvis\Framework\Application;

echo "Creating App 1...\n";
 = new Application(__DIR__);

echo "Creating App 2...\n";
 = new Application(__DIR__);

// Test Bindings
echo "Binding foo...\n";
->bind('foo', fn() => 'bar');
->bind('foo', fn() => 'baz');

if (->make('foo') !== 'bar') {
    echo "FAIL: App1 foo should be bar, got " . ->make('foo') . "\n";
    exit(1);
}

if (->make('foo') !== 'baz') {
    echo "FAIL: App2 foo should be baz, got " . ->make('foo') . "\n";
    exit(1);
}

// Test Singleton
echo "Testing singletons...\n";
->singleton('shared', fn() => new stdClass());
->singleton('shared', fn() => new stdClass());

 = ->make('shared');
 = ->make('shared');

if ( === ) {
    echo "FAIL: Singletons leaked across apps\n";
    exit(1);
}

// Test Instance
echo "Testing instances...\n";
 = new stdClass();
->name = 'app1';
->instance('instance', );

 = new stdClass();
->name = 'app2';
->instance('instance', );

if (->make('instance')->name !== 'app1') {
     echo "FAIL: App1 instance wrong\n";
     exit(1);
}

if (->make('instance')->name !== 'app2') {
     echo "FAIL: App2 instance wrong\n";
     exit(1);
}

echo "SUCCESS: Isolation verified.\n";
