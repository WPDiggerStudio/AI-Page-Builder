<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\WP;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * WidgetException - Exception for widget operations.
 */
class WidgetException extends FrameworkException {
	/**
	 * The widget ID.
	 */
	protected ?string $widgetId = null;

	/**
	 * Create exception for widget not found.
	 *
	 * @param string $widgetId The widget ID.
	 *
	 * @return static The exception instance.
	 */
	public static function notFound( string $widgetId ): static {
		return ( new static(
			sprintf(
			/* translators: %s: widget ID */
				__( "Widget '%s' not found.", 'wp-jarvis' ),
				$widgetId
			)
		) )->withContext( [ 'widget_id' => $widgetId ] );
	}

	/**
	 * Create exception for registration failure.
	 *
	 * @param string $widgetId The widget ID.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function registrationFailed( string $widgetId, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: widget ID, 2: error message */
				__( "Failed to register widget '%1\$s': %2\$s", 'wp-jarvis' ),
				$widgetId,
				$error
			)
		) )->withContext( [ 'widget_id' => $widgetId, 'error' => $error ] );
	}

	/**
	 * Create exception for render failure.
	 *
	 * @param string $widgetId The widget ID.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function renderFailed( string $widgetId, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: widget ID, 2: error message */
				__( "Failed to render widget '%1\$s': %2\$s", 'wp-jarvis' ),
				$widgetId,
				$error
			)
		) )->withContext( [ 'widget_id' => $widgetId, 'error' => $error ] );
	}

	/**
	 * Create exception for invalid configuration.
	 *
	 * @param string $widgetId The widget ID.
	 * @param string $reason The reason for invalid configuration.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidConfiguration( string $widgetId, string $reason ): static {
		return ( new static(
			sprintf(
			/* translators: 1: widget ID, 2: reason */
				__( "Invalid widget configuration for '%1\$s': %2\$s", 'wp-jarvis' ),
				$widgetId,
				$reason
			)
		) )->withContext( [ 'widget_id' => $widgetId, 'reason' => $reason ] );
	}

	/**
	 * Set widget ID.
	 *
	 * @param string $widgetId The widget ID.
	 *
	 * @return static The exception instance for method chaining.
	 */
	public function setWidgetId( string $widgetId ): static {
		$this->widgetId = $widgetId;

		return $this;
	}

	/**
	 * Get widget ID.
	 *
	 * @return string|null The widget ID.
	 */
	public function getWidgetId(): ?string {
		return $this->widgetId;
	}
}
