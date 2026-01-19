<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\WP;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * ShortcodeException - Exception for shortcode operations.
 */
class ShortcodeException extends FrameworkException {
	/**
	 * Create an exception for already registered shortcode.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return static The exception instance.
	 */
	public static function alreadyRegistered( string $tag ): static {
		return ( new static(
			sprintf(
			/* translators: %s: shortcode tag */
				__( "Shortcode '%s' is already registered.", 'wp-jarvis' ),
				$tag
			)
		) )->withContext( [ 'tag' => $tag ] );
	}

	/**
	 * Create an exception for shortcode not found.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return static The exception instance.
	 */
	public static function notFound( string $tag ): static {
		return ( new static(
			sprintf(
			/* translators: %s: shortcode tag */
				__( "Shortcode '%s' not found.", 'wp-jarvis' ),
				$tag
			)
		) )->withContext( [ 'tag' => $tag ] );
	}

	/**
	 * Create exception for render failure.
	 *
	 * @param string $tag The shortcode tag.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function renderFailed( string $tag, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: shortcode tag, 2: error message */
				__( "Failed to render shortcode '%1\$s': %2\$s", 'wp-jarvis' ),
				$tag,
				$error
			)
		) )->withContext( [ 'tag' => $tag, 'error' => $error ] );
	}

	/**
	 * Create exception for invalid callback.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidCallback( string $tag ): static {
		return ( new static(
			sprintf(
			/* translators: %s: shortcode tag */
				__( "Invalid callback for shortcode '%s'.", 'wp-jarvis' ),
				$tag
			)
		) )->withContext( [ 'tag' => $tag ] );
	}
}
