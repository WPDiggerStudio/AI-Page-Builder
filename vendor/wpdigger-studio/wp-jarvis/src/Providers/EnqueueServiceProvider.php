<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\WP\Assets\Enqueue;

/**
 * Enqueue Service Provider
 *
 * @package WPJarvis\Framework\Providers
 */
class EnqueueServiceProvider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->app->singleton( 'enqueue', function () {
			return new Enqueue();
		} );

		$this->app->alias( 'enqueue', Enqueue::class );
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Enqueue manager will handle its own hooks
	}
}
