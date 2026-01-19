<?php

/**
 * --------------------------------------------------------------------------
 * Application Configuration
 * --------------------------------------------------------------------------
 *
 * This file contains core configuration settings for WP Jarvis Core.
 * It defines application name, environment, service providers,
 * and other framework settings.
 */

return [

	/*
	|--------------------------------------------------------------------------
	| Application Namespace
	|--------------------------------------------------------------------------
	|
	| This value defines the root namespace for the application's core classes.
	| It is used throughout the application to maintain consistent
	| namespacing and autoloading.
	|
	*/
	'namespace'       => 'WPJarvis\\Framework\\',

	/*
	|--------------------------------------------------------------------------
	| Application Name
	|--------------------------------------------------------------------------
	|
	| This value is the name of your application. This value is used when
	| the framework needs to place the application's name in notifications or
	| any other location as required by the application or its packages.
	|
	*/
	'name'            => env( 'APP_NAME', 'WP Jarvis Core' ),

	/*
	|--------------------------------------------------------------------------
	| Application Slug
	|--------------------------------------------------------------------------
	|
	| This value is the slug for your application. It's used for database
	| prefixes, option names, and other WordPress-specific identifiers
	| throughout the application.
	|
	*/
	'slug'            => env( 'APP_SLUG', 'wpjarvis' ),

	/*
	|--------------------------------------------------------------------------
	| Application Environment
	|--------------------------------------------------------------------------
	|
	| This value determines the "environment" your application is currently
	| running in. This may determine how you prefer to configure various
	| services the application utilizes. Set this in your ".env" file.
	|
	*/
	'env'             => env( 'APP_ENV', 'production' ),

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| When your application is in debug mode, detailed error messages with
	| stack traces will be shown on every error that occurs within your
	| application. If disabled, a simple generic error page is shown.
	|
	*/
	'debug'           => env( 'APP_DEBUG', false ),

	/*
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default timezone for your application, which
	| will be used by PHP date and date-time functions. We have gone
	| ahead and set this to a sensible default for you out of the box.
	|
	*/
	'timezone'        => env( 'APP_TIMEZONE', 'UTC' ),

	/*
	|--------------------------------------------------------------------------
	| Application Locale Configuration
	|--------------------------------------------------------------------------
	|
	| The application locale determines the default locale that will be used
	| by the translation service provider. You are free to set this value
	| to any of the locales which will be supported by the application.
	|
	*/
	'locale'          => env( 'APP_LOCALE', 'en_US' ),
	'fallback_locale' => env( 'APP_FALLBACK_LOCALE', 'en_US' ),

	/*
	|--------------------------------------------------------------------------
	| Encryption Key
	|--------------------------------------------------------------------------
	|
	| This key is used by the Illuminate encryption service and should be
	| set to a random, 32 character string, otherwise these encrypted strings
	| will not be safe. Please do this before deploying an application!
	|
	*/
	'key'             => env( 'APP_KEY' ),

	/*
	|--------------------------------------------------------------------------
	| REST API Configuration
	|--------------------------------------------------------------------------
	|
	| This section configures the REST API integration for your application.
	| The namespace is used as a prefix for all endpoints created by your
	| REST routes.
	|
	*/
	'rest'            => [
		'namespace' => env( 'REST_NAMESPACE', 'wpjarvis/v1' ),
	],

	/*
	|--------------------------------------------------------------------------
	| Autoloaded Service Providers
	|--------------------------------------------------------------------------
	|
	| The service providers listed here will be automatically loaded on the
	| request to your application. Feel free to add your own services to
	| this array to grant expanded functionality to your applications.
	|
	*/
	'providers'       => [
		// Core framework providers
	],

	/*
	|--------------------------------------------------------------------------
	| Class Aliases
	|--------------------------------------------------------------------------
	|
	| This array of class aliases will be registered when this application
	| is started. However, feel free to register as many as you wish as
	| aliases are "lazy" loaded so they don't hinder performance.
	|
	*/
	'aliases'         => [
		'Hooks'     => \WPJarvis\Framework\Support\Facades\Hooks::class,
		'Config'    => \WPJarvis\Framework\Support\Facades\Config::class,
		'Route'     => \WPJarvis\Framework\Support\Facades\Route::class,
		'Rest'      => \WPJarvis\Framework\Support\Facades\Rest::class,
		'Log'       => \Illuminate\Support\Facades\Log::class,
		'Event'     => \Illuminate\Support\Facades\Event::class,
		'Queue'     => \Illuminate\Support\Facades\Queue::class,
		'Cache'     => \Illuminate\Support\Facades\Cache::class,
		'Validator' => \Illuminate\Support\Facades\Validator::class,
		'Request'   => \Illuminate\Support\Facades\Request::class,
		'Response'  => \Illuminate\Support\Facades\Response::class,
		'View'      => \Illuminate\Support\Facades\View::class,
		'DB'        => \Illuminate\Support\Facades\DB::class,
	],


	/*
	|--------------------------------------------------------------------------
	| Performance and Caching
	|--------------------------------------------------------------------------
	|
	| Configuration options related to application performance optimization.
	| This includes caching drivers, prefixes, and other performance tuning
	| parameters.
	|
	*/
	'performance'     => [
		'cache_driver' => env( 'CACHE_DRIVER', 'wordpress' ),
		'prefix'       => env( 'CACHE_PREFIX', 'wpjarvis_' ),
	],

	/*
	|--------------------------------------------------------------------------
	| Queue Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration options for the queue system.
	|
	*/
	'queue'           => [
		'default' => env( 'QUEUE_CONNECTION', 'wordpress' ),
		'driver'  => env( 'QUEUE_DRIVER', 'wordpress' ),
	],

	/*
	|--------------------------------------------------------------------------
	| Database Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration options for the database connection.
	|
	*/
	'database'        => [
		'default'         => env( 'DB_CONNECTION', 'wordpress' ),
		'connections'     => [
			'wordpress' => [
				'driver'    => 'mysql',
				'host'      => defined( 'DB_HOST' ) ? DB_HOST : env( 'DB_HOST', 'localhost' ),
				'database'  => defined( 'DB_NAME' ) ? DB_NAME : env( 'DB_DATABASE', 'wordpress' ),
				'username'  => defined( 'DB_USER' ) ? DB_USER : env( 'DB_USERNAME', '' ),
				'password'  => defined( 'DB_PASSWORD' ) ? DB_PASSWORD : env( 'DB_PASSWORD', '' ),
				'charset'   => defined( 'DB_CHARSET' ) ? DB_CHARSET : 'utf8mb4',
				'collation' => defined( 'DB_COLLATE' ) && DB_COLLATE ? DB_COLLATE : 'utf8mb4_unicode_ci',
				'prefix'    => isset( $GLOBALS['wpdb'] ) ? $GLOBALS['wpdb']->prefix : env( 'DB_PREFIX', 'wp_' ),
			]
		],
		'migrations'      => 'wpjarvis_migrations',
		'migration_paths' => dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR . 'database/migrations',
	],

	/*
	|--------------------------------------------------------------------------
	| Logging Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration options for the logging system.
	|
	*/
	'logging'         => [
		'default'  => 'wordpress',
		'channels' => [
			'wordpress' => [
				'driver' => 'monolog',
				'level'  => env( 'LOG_LEVEL', 'debug' ),
				'days'   => env( 'LOG_DAYS', 14 ),
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Cache Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration options for the cache system.
	|
	*/
	'cache'           => [
		'default' => env( 'CACHE_DRIVER', 'wordpress' ),
		'prefix'  => env( 'CACHE_PREFIX', 'wpjarvis_' ),
	],

	/*
	|--------------------------------------------------------------------------
	| Console Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration options for the console/CLI system.
	|
	*/
	'console'         => [
		'name'    => env( 'APP_NAME', 'wpjarvis' ),
		'version' => env( 'APP_VERSION', '2.0.0' ),
	],
];
