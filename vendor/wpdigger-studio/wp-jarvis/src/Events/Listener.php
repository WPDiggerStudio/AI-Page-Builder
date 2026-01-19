<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Events;

use WPJarvis\Framework\Support\Facades\Log;

/**
 * Listener - Base class for event listeners.
 */
abstract class Listener {
	/**
	 * The queue connection to use.
	 */
	protected ?string $connection = null;

	/**
	 * The queue name to send the job to.
	 */
	protected ?string $queue = null;

	/**
	 * The delay (in seconds) before processing the listener.
	 */
	protected int $delay = 0;

	/**
	 * Handle the event.
	 *
	 * @param object $event The event instance.
	 *
	 * @return void
	 */
	// FIX: Changed 'Event|object' to 'object'
	abstract public function handle( object $event ): void;

	/**
	 * Determine if the listener should be queued.
	 *
	 * @return bool True if the listener should be queued.
	 */
	public function shouldQueue(): bool {
		return $this->queue !== null || $this->connection !== null;
	}

	/**
	 * Get the queue connection name.
	 *
	 * @return string|null The connection name.
	 */
	public function viaConnection(): ?string {
		return $this->connection;
	}

	/**
	 * Get the queue name.
	 *
	 * @return string|null The queue name.
	 */
	public function viaQueue(): ?string {
		return $this->queue;
	}

	/**
	 * Get the delay before processing.
	 *
	 * @return int The delay in seconds.
	 */
	public function withDelay(): int {
		return $this->delay;
	}

	/**
	 * Handle a job failure.
	 *
	 * @param object $event The event instance.
	 * @param \Throwable $exception The exception that caused the failure.
	 *
	 * @return void
	 */
	// FIX: Changed 'Event|object' to 'object'
	public function failed( object $event, \Throwable $exception ): void {
		// Log the failure by default
		if ( function_exists( 'wpj_logger' ) ) {
			Log::error(
				sprintf( __( '[Listener Failed] %s: %s', 'wp-jarvis' ), static::class, $exception->getMessage() ),
				[
					'event'     => get_class( $event ),
					'exception' => $exception,
				]
			);
		}
	}

	/**
	 * Get the event-listener mapping array.
	 *
	 * Override this method to define which events this listener handles.
	 * This is used for auto-registration via a subscriber pattern.
	 *
	 * @param \WPJarvis\Framework\Events\Dispatcher $events
	 *
	 * @return void
	 */
	public function subscribe( Dispatcher $events ): void {
		// Override in a subclass to subscribe to events
	}
}