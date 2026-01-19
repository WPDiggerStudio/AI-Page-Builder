<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * Event Service Provider
 *
 * Registers event listeners and subscribers.
 *
 * @package WPJarvis\Framework\Providers
 */
class EventServiceProvider extends ServiceProvider {
	/**
	 * The event listener mappings.
	 *
	 * @var array<string, array<class-string>>
	 */
	protected array $listen = [];

	/**
	 * The subscriber classes.
	 *
	 * @var array<class-string>
	 */
	protected array $subscribe = [];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function register(): void {
		// Check if events are cached
		$cachedPath = $this->app->getCachedEventsPath();
		if ( file_exists( $cachedPath ) ) {
			$cached          = require $cachedPath;
			$this->listen    = array_merge( $this->listen, $cached['listen'] ?? [] );
			$this->subscribe = array_merge( $this->subscribe, $cached['subscribe'] ?? [] );

			return;
		}

		// Fall back to loading from config
		$this->listen = array_merge(
			$this->listen,
			$this->app->make( 'config' )->get( 'events.listen', [] )
		);

		$this->subscribe = array_merge(
			$this->subscribe,
			$this->app->make( 'config' )->get( 'events.subscribe', [] )
		);
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void {
		$events = $this->app->make( 'events' );

		// Register listeners
		foreach ( $this->listen as $event => $listeners ) {
			foreach ( $listeners as $listener ) {
				$events->listen( $event, $listener );
			}
		}

		// Register subscribers
		foreach ( $this->subscribe as $subscriber ) {
			$events->subscribe( $subscriber );
		}
	}
}
