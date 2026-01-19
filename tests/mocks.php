<?php

// Mocks for Unit Tests

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $uri, $args) {
        global $mock_routes;
        $mock_routes[] = compact('namespace', 'uri', 'args');
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback) {
        global $mock_actions;
        $mock_actions[$hook][] = $callback;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/test/';
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path($path) {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($str) {
        return rtrim($str, '/') . '/';
    }
}
