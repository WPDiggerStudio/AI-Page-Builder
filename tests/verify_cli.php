<?php

// Mock environment for CLI
define('WP_CLI', true);

class WP_CLI {
    public static  = [];
    public static function add_command($name, $callable) {
        self::$commands[$name] = $callable;
        echo "WP-CLI: Registered command '$name'\n";
    }
    public static function error($msg) {
        echo "WP-CLI Error: $msg\n";
    }
}

// Mock WP functions needed by jarvis script
function home_url() { return 'http://localhost'; }
function plugin_basename($file) { return basename($file); }
function trailingslashit($str) { return rtrim($str, '/') . '/'; }
function wp_normalize_path($path) { return str_replace('\', '/', $path); }

// Include the jarvis script (it will detect WP_CLI and register command instead of running)
// We need to bypass the shebang line if possible, or just include it.
// Since it has strict types and we are mocking, we might get errors if not careful.
// Let's just include it and see if it registers.

require __DIR__ . '/../jarvis';

// Verify registration
if (isset(WP_CLI::$commands['jarvis'])) {
    echo "SUCCESS: 'jarvis' command registered with WP-CLI.\n";
} else {
    echo "FAIL: 'jarvis' command NOT registered.\n";
    exit(1);
}
