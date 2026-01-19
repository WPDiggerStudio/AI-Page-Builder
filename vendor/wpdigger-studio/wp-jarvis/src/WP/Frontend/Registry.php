<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend;

/**
 * Registry - Frontend component registry for bulk registration.
 *
 * Provides centralized registration of widgets and shortcodes,
 * with support for auto-discovery.
 *
 * @package WPJarvis\Framework\WP\Frontend
 */
class Registry {
	/**
	 * Registered widgets.
	 *
	 * @var array<string, Widget>
	 */
	private array $widgets = [];

	/**
	 * Registered shortcodes.
	 *
	 * @var array<string, Shortcode>
	 */
	private array $shortcodes = [];

	/**
	 * Create a new Registry instance.
	 *
	 * @return static
	 */
	public static function make(): static {
		return new static();
	}

	/**
	 * Register a widget.
	 *
	 * @param Widget|callable $widget Widget instance or factory callback.
	 *
	 * @return static
	 */
	public function registerWidget( Widget|callable $widget ): static {
		if ( is_callable( $widget ) ) {
			$widget = $widget();
		}

		if ( $widget instanceof Widget ) {
			$this->widgets[ $widget->getId() ] = $widget;
			$widget->register();
		}

		return $this;
	}

	/**
	 * Register multiple widgets.
	 *
	 * @param array<Widget|callable> $widgets Widget instances or factories.
	 *
	 * @return static
	 */
	public function registerWidgets( array $widgets ): static {
		foreach ( $widgets as $widget ) {
			$this->registerWidget( $widget );
		}

		return $this;
	}

	/**
	 * Register a shortcode.
	 *
	 * @param Shortcode|callable $shortcode Shortcode instance or factory callback.
	 *
	 * @return static
	 */
	public function registerShortcode( Shortcode|callable $shortcode ): static {
		if ( is_callable( $shortcode ) ) {
			$shortcode = $shortcode();
		}

		if ( $shortcode instanceof Shortcode ) {
			$this->shortcodes[ $shortcode->getTag() ] = $shortcode;
			$shortcode->register();
		}

		return $this;
	}

	/**
	 * Register multiple shortcodes.
	 *
	 * @param array<Shortcode|callable> $shortcodes Shortcode instances or factories.
	 *
	 * @return static
	 */
	public function registerShortcodes( array $shortcodes ): static {
		foreach ( $shortcodes as $shortcode ) {
			$this->registerShortcode( $shortcode );
		}

		return $this;
	}

	/**
	 * Check if a widget is registered.
	 *
	 * @param string $id Widget ID.
	 *
	 * @return bool
	 */
	public function hasWidget( string $id ): bool {
		return isset( $this->widgets[ $id ] );
	}

	/**
	 * Check if a shortcode is registered.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return bool
	 */
	public function hasShortcode( string $tag ): bool {
		return isset( $this->shortcodes[ $tag ] );
	}

	/**
	 * Get a registered widget.
	 *
	 * @param string $id Widget ID.
	 *
	 * @return Widget|null
	 */
	public function getWidget( string $id ): ?Widget {
		return $this->widgets[ $id ] ?? null;
	}

	/**
	 * Get a registered shortcode.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return Shortcode|null
	 */
	public function getShortcode( string $tag ): ?Shortcode {
		return $this->shortcodes[ $tag ] ?? null;
	}

	/**
	 * Get all registered widgets.
	 *
	 * @return array<string, Widget>
	 */
	public function getWidgets(): array {
		return $this->widgets;
	}

	/**
	 * Get all registered shortcodes.
	 *
	 * @return array<string, Shortcode>
	 */
	public function getShortcodes(): array {
		return $this->shortcodes;
	}

	/**
	 * Unregister a widget.
	 *
	 * @param string $id Widget ID.
	 *
	 * @return static
	 */
	public function unregisterWidget( string $id ): static {
		if ( isset( $this->widgets[ $id ] ) ) {
			unregister_widget( $id );
			unset( $this->widgets[ $id ] );
		}

		return $this;
	}

	/**
	 * Unregister a shortcode.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return static
	 */
	public function unregisterShortcode( string $tag ): static {
		if ( isset( $this->shortcodes[ $tag ] ) ) {
			$this->shortcodes[ $tag ]->unregister();
			unset( $this->shortcodes[ $tag ] );
		}

		return $this;
	}

	/**
	 * Clear all registered components.
	 *
	 * @return static
	 */
	public function clear(): static {
		foreach ( array_keys( $this->widgets ) as $id ) {
			$this->unregisterWidget( $id );
		}

		foreach ( array_keys( $this->shortcodes ) as $tag ) {
			$this->unregisterShortcode( $tag );
		}

		return $this;
	}
}
