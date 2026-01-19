<?php

declare( strict_types=1 );

/**
 * Application Configuration
 *
 * All values are loaded from the.env file. Change .env, not this file.
 *
 * @package App
 */

return [
	/*
	|--------------------------------------------------------------------------
	| Plugin Identity (from .env)
	|--------------------------------------------------------------------------
	*/
	'prefix'             => env( 'PLUGIN_PREFIX', 'APP' ),
	'version'            => env( 'PLUGIN_VERSION', '1.0.0' ),
	'slug'               => env( 'PLUGIN_SLUG', 'wp-jarvis-app' ),
	'name'               => env( 'PLUGIN_NAME', 'WP Jarvis App' ),
	'textdomain'         => env( 'PLUGIN_TEXTDOMAIN', 'wp-jarvis' ),
	'namespace'          => env( 'PLUGIN_NAMESPACE', 'App' ),
	'rest_namespace'     => env( 'PLUGIN_REST_NAMESPACE', 'wpjarvis/v1' ),

	/*
	|--------------------------------------------------------------------------
	| Storage Configuration (from .env)
	|--------------------------------------------------------------------------
	*/
	'storage_folder'     => env( 'PLUGIN_STORAGE_FOLDER', env( 'PLUGIN_SLUG', 'wpjarvis' ) ),

	/*
	|--------------------------------------------------------------------------
	| Application Environment (from .env)
	|--------------------------------------------------------------------------
	*/
	'env'                => env( 'APP_ENV', 'production' ),
	'debug'              => (bool) env( 'APP_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG ),
	'url'                => env( 'APP_URL', function_exists( 'home_url' ) ? home_url() : 'http://localhost' ),
	'locale'             => env( 'APP_LOCALE', 'en' ),

	/*
	|--------------------------------------------------------------------------
	| Cleanup on Uninstall (from .env)
	|--------------------------------------------------------------------------
	*/
	'clean_on_uninstall' => (bool) env( 'CLEAN_ON_UNINSTALL', false ),

	/*
	|--------------------------------------------------------------------------
	| Autoloaded Service Providers
	|--------------------------------------------------------------------------
	|
	| These are your application/plugin-level service providers that are
	| loaded during application bootstrap.
	|
	| NOTE: Framework core providers are automatically registered by the
	| framework's Bootstrap::baseProviders() method. Do NOT list framework
	| providers here - only include your own application providers.
	|
	| Framework providers (auto-registered):
	| - FacadeServiceProvider
	| - RequirementsServiceProvider
	| - ConsoleServiceProvider
	| - CoreServiceProvider
	| - ContentServiceProvider
	| - EventServiceProvider
	| - I18nServiceProvider
	| - RouterServiceProvider
	| - ValidationServiceProvider
	| - MailServiceProvider (deferred)
	| - QueueServiceProvider (deferred)
	| - ScheduleServiceProvider (deferred)
	|
	*/
	'providers'          => [
		// Application Providers
		BraCalculator\App\Providers\AppServiceProvider::class,
		BraCalculator\App\Providers\EventServiceProvider::class,
		BraCalculator\App\Providers\NotificationServiceProvider::class,
		BraCalculator\App\Providers\RouteServiceProvider::class,
	],

	/*
	|--------------------------------------------------------------------------
	| Class Aliases
	|--------------------------------------------------------------------------
	|
	| These are your application/plugin-level facade aliases that are
	| registered during application bootstrap.
	|
	| NOTE: Framework core facades are automatically registered by the
	| framework's FacadeServiceProvider. Do NOT list framework facades
	| here - only include your own application-specific aliases.
	|
	| Framework facades (auto-registered):
	| - App
	| - Config
	| - Event
	| - Hooks
	| - Log
	| - View
	| - Cache
	| - DB
	| - Validator
	| - Route
	| - Rest
	| - Job
	|
	*/
	'aliases'            => [
		// Application-specific aliases can be added here
		// Example: 'MyHelper' => BraCalculator\App\Facades\MyHelper::class,
	],

	/*
	|--------------------------------------------------------------------------
	| Editor Integrations (from .env)
	|--------------------------------------------------------------------------
	|
	| Feature flags for editor-specific integrations.
	|
	| tinymce_button:
	| - Enables a Classic Editor / TinyMCE toolbar button to insert your
	|   framework shortcodes via a UI dialog.
	| - This does NOT “detect” whether Gutenberg is enabled. It only toggles
	|   whether we register the TinyMCE integration code.
	| - Use this when the site uses the Classic Editor, or Gutenberg with a
	|   Classic block, or when you simply want the button available.
	|
	| .env:
	|   APP_TINYMCE_BUTTON=true
	|   APP_TINYMCE_BUTTON=false
	|
	*/
	'tinymce_button'     => (bool) env( 'APP_TINYMCE_BUTTON', true ),

];
