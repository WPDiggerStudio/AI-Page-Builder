<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Events;

/**
 * Event - Base class for application events.
 *
 * All application events should extend this class.
 * Events are simple data objects that hold the event payload.
 *
 * @package WPJarvis\Framework\Events
 */
abstract class Event {
	/**
	 * The event name (auto-generated from the class name if not set).
	 */
	protected ?string $name = null;

	/**
	 * Whether the event should stop propagation.
	 */
	protected bool $stopped = false;

	/**
	 * Get the event name.
	 *
	 * @return string The event name.
	 */
	public function getName(): string {
		return $this->name ?? (string) static::class;
	}

	/**
	 * Get the event payload as an array.
	 *
	 * Override this method to customize the event payload.
	 *
	 * @return array<string, mixed> The event data.
	 */
	public function toArray(): array {
		$data       = [];
		$reflection = new \ReflectionClass( $this );

		foreach ( $reflection->getProperties( \ReflectionProperty::IS_PUBLIC ) as $property ) {
			$data[ $property->getName() ] = $property->getValue( $this );
		}

		return $data;
	}

	/**
	 * Stop event propagation.
	 *
	 * @return static The event instance for method chaining.
	 */
	public function stopPropagation(): static {
		$this->stopped = true;

		return $this;
	}

	/**
	 * Check if event propagation is stopped.
	 *
	 * @return bool True if propagation is stopped.
	 */
	public function isPropagationStopped(): bool {
		return $this->stopped;
	}

	/**
	 * Dispatch the event.
	 *
	 * @return array|null The listener responses.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function dispatch(): ?array {
		return wpj_event( $this );
	}

	/**
	 * Broadcast the event to a WordPress hook.
	 *
	 * @param string $hookName The WordPress hook name.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function broadcast( string $hookName = '' ): void {
		$hook = $hookName ?: $this->getDefaultHookName();
		do_action( $hook, $this );
	}

	/**
	 * Get the default WordPress hook name.
	 *
	 * @return string The hook name.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getDefaultHookName(): string {
		// Fast way to get a short class name without Reflection
		$className = substr( strrchr( static::class, '\\' ), 1 ) ?: static::class;

		$prefix = wpj_config( 'app.slug', 'wp-jarvis' ) . '_';

		return $prefix . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $className ) );
	}
}
