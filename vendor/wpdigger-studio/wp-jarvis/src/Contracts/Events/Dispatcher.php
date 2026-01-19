<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Events;

/**
 * Dispatcher Interface
 *
 * Defines the contract for event dispatching.
 */
interface Dispatcher {
	/**
	 * Register an event listener.
	 *
	 * @param string|array $events
	 * @param mixed $listener
	 */
	public function listen( string|array $events, mixed $listener ): void;

	/**
	 * Determine if a given event has listeners.
	 */
	public function hasListeners( string $eventName ): bool;

	/**
	 * Register an event subscriber.
	 */
	public function subscribe( object|string $subscriber ): void;

	/**
	 * Dispatch an event and call the listeners.
	 *
	 * @param string|object $event
	 * @param mixed $payload
	 * @param bool $halt
	 *
	 * @return array|null
	 */
	public function dispatch( string|object $event, mixed $payload = [], bool $halt = false ): ?array;

	/**
	 * Remove a set of listeners from the dispatcher.
	 */
	public function forget( string $event ): void;
}
