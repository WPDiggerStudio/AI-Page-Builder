<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\WP;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * PostTypeException - Exception for post type operations.
 */
class PostTypeException extends FrameworkException {
	/**
	 * The post type.
	 */
	protected ?string $postType = null;

	/**
	 * Create exception for post type not found.
	 *
	 * @param string $postType The post type.
	 *
	 * @return static The exception instance.
	 */
	public static function notFound( string $postType ): static {
		return ( new static(
			sprintf(
			/* translators: %s: post-type name */
				__( "Post type '%s' not found.", 'wp-jarvis' ),
				$postType
			)
		) )->withContext( [ 'post_type' => $postType ] );
	}

	/**
	 * Create exception for already registered post-type.
	 *
	 * @param string $postType The post-type.
	 *
	 * @return static The exception instance.
	 */
	public static function alreadyRegistered( string $postType ): static {
		return ( new static(
			sprintf(
			/* translators: %s: post-type name */
				__( "Post type '%s' is already registered.", 'wp-jarvis' ),
				$postType
			)
		) )->withContext( [ 'post_type' => $postType ] );
	}

	/**
	 * Create exception for registration failure.
	 *
	 * @param string $postType The post-type.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function registrationFailed( string $postType, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: post-type name, 2: error message */
				__( "Failed to register post type '%1\$s': %2\$s", 'wp-jarvis' ),
				$postType,
				$error
			)
		) )->withContext( [ 'post_type' => $postType, 'error' => $error ] );
	}

	/**
	 * Create an exception for invalid slug.
	 *
	 * @param string $postType The post-type slug.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidSlug( string $postType ): static {
		return ( new static(
			sprintf(
			/* translators: %s: post-type slug */
				__( "Invalid post type slug: '%s'. Slug must be 20 characters or less.", 'wp-jarvis' ),
				$postType
			)
		) )->withContext( [ 'post_type' => $postType ] );
	}

	/**
	 * Set the post-type.
	 *
	 * @param string $postType The post-type.
	 *
	 * @return static The exception instance for method chaining.
	 */
	public function setPostType( string $postType ): static {
		$this->postType = $postType;

		return $this;
	}

	/**
	 * Get the post-type.
	 *
	 * @return string|null The post-type.
	 */
	public function getPostType(): ?string {
		return $this->postType;
	}
}
