<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\WP\Assets\Enqueue;
use WPJarvis\Framework\WP\Content\Registry;
use WPJarvis\Framework\WP\Hooks\Hooks as HooksManager;

/**
 * Core Service Provider
 *
 * Registers core framework services for WordPress integration.
 *
 * @package WPJarvis\Framework\Providers
 */
class CoreServiceProvider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void {
		// Register hooks manager
		$this->app->singleton( 'hooks', function () {
			return new HooksManager();
		} );

		// Register content registry (manages post types, taxonomies, metaboxes)
		$this->app->singleton( 'content', function () {
			return new Registry();
		} );

		// Register enqueue manager
		$this->app->singleton( 'enqueue', function () {
			return new Enqueue();
		} );

		// Bind aliases
		$this->app->alias( 'hooks', HooksManager::class );
		$this->app->alias( 'content', Registry::class );
		$this->app->alias( 'enqueue', Enqueue::class );
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Load environment file if exists
		$this->loadEnvironment();

	}

	/**
	 * Load environment file.
	 *
	 * @return void
	 */
	private function loadEnvironment(): void {
		$envFile = $this->app->basePath( '.env' );

		if ( class_exists( \Dotenv\Dotenv::class ) && file_exists( $envFile ) ) {
			$dotenv = \Dotenv\Dotenv::createImmutable( $this->app->basePath() );
			$dotenv->safeLoad();
		}
	}
}
