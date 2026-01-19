<?php
declare( strict_types=1 );

namespace BraCalculator\App\Providers;

use WPJarvis\Framework\Support\Facades\Event;
use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * Event Service Provider
 *
 * Registers application events and listeners.
 *
 * @package BraCalculator\App\Providers
 */
class EventServiceProvider extends ServiceProvider {
	/**
	 * The event listener mappings for the application.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $listen = [
		//
	];

	/**
	 * The subscriber classes to register.
	 *
	 * @var array<int, string>
	 */
	protected array $subscribe = [
		//
	];

	/**
	 * Register any events for your application.
	 *
	 * @return void
	 */
	public function register(): void {
		// Register events
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->bootEvents();
	}

	/**
	 * Boot events.
	 *
	 * @return void
	 */
	protected function bootEvents(): void {
		foreach ( $this->listen as $event => $listeners ) {
			foreach ( array_filter( $listeners ) as $listener ) {
				Event::listen( $event, $listener );
			}
		}

		foreach ( $this->subscribe as $subscriber ) {
			Event::subscribe( $subscriber );
		}
	}

	/**
	 * Get the events and handlers.
	 *
	 * @return array
	 */
	public function listens(): array {
		return $this->listen;
	}
}
