<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Events;

/**
 * Subscriber - Base class for event subscribers.
 *
 * Event subscribers can listen to multiple events with a single class.
 * They use the subscribe() method to register all their handlers.
 *
 * @package WPJarvis\Framework\Events
 */
abstract class Subscriber {
	/**
	 * Subscribe to events.
	 *
	 * Register event listeners within this method.
	 *
	 * @param Dispatcher $events The event dispatcher instance.
	 *
	 * @return void
	 */
	abstract public function subscribe( Dispatcher $events ): void;

	/**
	 * Handle multiple events to a single method.
	 *
	 * Helper method for subscribing multiple events to one handler.
	 *
	 * @param Dispatcher $events The event dispatcher.
	 * @param array<string> $eventClasses The event class names.
	 * @param string $method The handler method name.
	 *
	 * @return void
	 */
	protected function listenTo( Dispatcher $events, array $eventClasses, string $method ): void {
		foreach ( $eventClasses as $eventClass ) {
			$events->listen( $eventClass, [ $this, $method ] );
		}
	}
}
