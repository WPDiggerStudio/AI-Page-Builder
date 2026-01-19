<?php
/**
 * Plugin Name: BRA Calculator
 * Plugin URI: https://example.com
 * Description: Built with WP Jarvis framework - A Laravel-style WordPress plugin.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: bra-calculator
 * Domain Path: /resources/lang
 * Requires PHP: 8.1
 * Requires at least: 6.0
 *
 * @package BraCalculator
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Plugin File Constants
|--------------------------------------------------------------------------
|
| These are the ONLY hardcoded values. Everything else comes from .env
| The bootstrap will load .env and create prefixed constants dynamically.
|
*/
define( 'WPJARVIS_PLUGIN_FILE', __FILE__ );
define( 'WPJARVIS_PLUGIN_DIR', __DIR__ );

/*
|--------------------------------------------------------------------------
| Bootstrap The Application
|--------------------------------------------------------------------------
*/

require __DIR__ . '/bootstrap/app.php';
//dd($app);