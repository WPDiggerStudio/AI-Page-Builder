<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\WP;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * TaxonomyException - Exception for taxonomy operations.
 */
class TaxonomyException extends FrameworkException {
	/**
	 * Create exception for taxonomy not found.
	 *
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return static The exception instance.
	 */
	public static function notFound( string $taxonomy ): static {
		return ( new static(
			sprintf(
			/* translators: %s: taxonomy name */
				__( "Taxonomy '%s' not found.", 'wp-jarvis' ),
				$taxonomy
			)
		) )->withContext( [ 'taxonomy' => $taxonomy ] );
	}

	/**
	 * Create an exception for already registered taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return static The exception instance.
	 */
	public static function alreadyRegistered( string $taxonomy ): static {
		return ( new static(
			sprintf(
			/* translators: %s: taxonomy name */
				__( "Taxonomy '%s' is already registered.", 'wp-jarvis' ),
				$taxonomy
			)
		) )->withContext( [ 'taxonomy' => $taxonomy ] );
	}

	/**
	 * Create exception for registration failure.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function registrationFailed( string $taxonomy, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: taxonomy name, 2: error message */
				__( "Failed to register taxonomy '%1\$s': %2\$s", 'wp-jarvis' ),
				$taxonomy,
				$error
			)
		) )->withContext( [ 'taxonomy' => $taxonomy, 'error' => $error ] );
	}

	/**
	 * Create an exception for invalid slug.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidSlug( string $taxonomy ): static {
		return ( new static(
			sprintf(
			/* translators: %s: taxonomy slug */
				__( "Invalid taxonomy slug: '%s'. Slug must be 32 characters or less.", 'wp-jarvis' ),
				$taxonomy
			)
		) )->withContext( [ 'taxonomy' => $taxonomy ] );
	}
}
