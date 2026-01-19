<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Events;

use WPJarvis\Framework\Contracts\Events\Dispatcher as DispatcherContract;
use WPJarvis\Framework\Support\Facades\Config;

/**
 * Dispatcher - Event dispatcher.
 */
class Dispatcher implements DispatcherContract {
	/**
	 * Registered listeners.
	 *
	 * @var array<string, array<callable|string|array>>
	 */
	private array $listeners = [];

	/**
	 * Hook prefix.
	 */
	private string $hookPrefix;

	/**
	 * Create a new Dispatcher instance.
	 */
	public function __construct() {
		$pluginSlug       = Config::get( 'app.slug', 'wp-jarvis' );
		$this->hookPrefix = $pluginSlug . '_event_';
	}

	/**
	 * Register an event listener.
	 */
	public function listen( string|array $events, mixed $listener ): void {
		foreach ( (array) $events as $event ) {
			$this->listeners[ $event ][] = $listener;
		}
	}

	/**
	 * Determine if an event has listeners.
	 */
	public function hasListeners( string $eventName ): bool {
		return ! empty( $this->listeners[ $eventName ] ) ||
		       has_action( $this->getHookName( $eventName ) );
	}

	/**
	 * Register an event subscriber.
	 */
	public function subscribe( object|string $subscriber ): void {
		if ( is_string( $subscriber ) ) {
			$subscriber = new $subscriber();
		}

		// Safety check
		if ( method_exists( $subscriber, 'subscribe' ) ) {
			$subscriber->subscribe( $this );
		}
	}

	/**
	 * Dispatch an event.
	 */
	public function dispatch( string|object $event, mixed $payload = [], bool $halt = false ): ?array {
		$eventName = is_object( $event ) ? get_class( $event ) : $event;
		$responses = [];

		// Normalize payload to array
		if ( is_object( $event ) ) {
			$payload = [ $event ];
		} elseif ( ! is_array( $payload ) ) {
			$payload = [ $payload ];
		}

		// 1. Run Internal Listeners (Jarvis)
		if ( isset( $this->listeners[ $eventName ] ) ) {
			foreach ( $this->listeners[ $eventName ] as $listener ) {
				$response = $this->callListener( $listener, $payload );

				if ( $halt && $response !== null ) {
					return [ $response ];
				}

				$responses[] = $response;
			}
		}

		// 2. Broadcast to WordPress (External Plugins)
		// This allows 3rd party themes/plugins to hook in, but doesn't re-run our internal listeners.
		do_action( $this->getHookName( $eventName ), ...$payload );

		return $responses;
	}

	/**
	 * Remove listeners for an event.
	 */
	public function forget( string $event ): void {
		unset( $this->listeners[ $event ] );
	}

	/**
	 * Call a listener.
	 */
	protected function callListener( mixed $listener, array $payload ): mixed {
		if ( is_callable( $listener ) ) {
			return call_user_func_array( $listener, $payload );
		}

		if ( is_string( $listener ) ) {
			return $this->callClassListener( $listener, $payload );
		}

		if ( is_array( $listener ) && count( $listener ) === 2 ) {
			[ $class, $method ] = $listener;
			// Instantiate if class name provided
			if ( is_string( $class ) ) {
				$class = new $class();
			}

			return call_user_func_array( [ $class, $method ], $payload );
		}

		return null;
	}

	/**
	 * Call a class-based listener.
	 */
	protected function callClassListener( string $listener, array $payload ): mixed {
		if ( str_contains( $listener, '@' ) ) {
			[ $class, $method ] = explode( '@', $listener, 2 );
		} else {
			$class  = $listener;
			$method = 'handle';
		}

		// Optimization: Simple instantiation is faster than Reflection if no constructor args needed
		$instance = new $class();

		return call_user_func_array( [ $instance, $method ], $payload );
	}

	/**
	 * Get the WordPress hook name for an event.
	 */
	protected function getHookName( string $event ): string {
		// Normalized hook name: wp-jarvis_event_app_events_usercreated
		$hookName = strtolower( str_replace( '\\', '_', $event ) );

		return $this->hookPrefix . $hookName;
	}

	/**
	 * Fire an event (alias for dispatch).
	 */
	public function fire( string|object $event, mixed $payload = [], bool $halt = false ): ?array {
		return $this->dispatch( $event, $payload, $halt );
	}

	/**
	 * Get all registered listeners.
	 */
	public function getListeners( string $eventName ): array {
		return $this->listeners[ $eventName ] ?? [];
	}
}