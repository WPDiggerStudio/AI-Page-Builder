<?php
declare( strict_types=1 );

namespace BraCalculator\App\Providers;

use BraCalculator\App\Events\NotificationCreated;
use BraCalculator\App\Listeners\SendNotificationEmail;
use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\Event;

/**
 * Notification Service Provider
 *
 * Registers notification-related services and event listeners.
 *
 * @package BraCalculator\App\Providers
 */
class NotificationServiceProvider extends ServiceProvider {
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(): void {
		// Register notification services
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register event listeners
		$this->registerEventListeners();

		// Publish migrations
		$this->publishes( [
			$this->app->basePath( 'database/migrations' ) => wpj_database_path( 'migrations' ),
		], 'wp-jarvis-migrations' );
	}

	/**
	 * Register event listeners.
	 *
	 * @return void
	 */
	protected function registerEventListeners(): void {
		Event::listen(
			NotificationCreated::class,
			SendNotificationEmail::class
		);
	}
}
