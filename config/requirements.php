<?php

declare( strict_types=1 );

/**
 * Requirements Configuration
 *
 * Defines the minimum requirements for this plugin to function.
 *
 * @package App
 */

return [
	/*
	|--------------------------------------------------------------------------
	| Minimum PHP Version
	|--------------------------------------------------------------------------
	*/
	'min_php_version'         => '8.1',

	/*
	|--------------------------------------------------------------------------
	| Minimum WordPress Version
	|--------------------------------------------------------------------------
	*/
	'min_wp_version'          => '6.0',

	/*
	|--------------------------------------------------------------------------
	| Multisite Compatibility
	|--------------------------------------------------------------------------
	*/
	'is_multisite_compatible' => true,

	/*
	|--------------------------------------------------------------------------
	| Required PHP Extensions
	|--------------------------------------------------------------------------
	*/
	'php_extensions'          => [
		'json',
		'mbstring',
	],

	/*
	|--------------------------------------------------------------------------
	| Required Plugins
	|--------------------------------------------------------------------------
	|
	| Format:
	| 'Plugin Name' => [
	|     'plugin_slug' => 'plugin-folder/plugin-file.php',
	|     'min_plugin_version' => '1.0.0', // optional
	| ],
	|
	*/
	'required_plugins'        => [
//		'WooCommerce' => [
//			'plugin_slug'        => 'woocommerce/woocommerce.php',
//			'min_plugin_version' => '8.0.0',
//		],
	],
];
