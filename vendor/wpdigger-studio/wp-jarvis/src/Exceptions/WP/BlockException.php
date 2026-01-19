<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\WP;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * BlockException - Exception for Gutenberg block operations.
 */
class BlockException extends FrameworkException {
	/**
	 * Create exception for block not found.
	 *
	 * @param string $blockName The block name.
	 *
	 * @return static The exception instance.
	 */
	public static function notFound( string $blockName ): static {
		return ( new static(
			sprintf(
			/* translators: %s: block name */
				__( "Block '%s' not found.", 'wp-jarvis' ),
				$blockName
			)
		) )->withContext( [ 'block_name' => $blockName ] );
	}

	/**
	 * Create exception for already registered block.
	 *
	 * @param string $blockName The block name.
	 *
	 * @return static The exception instance.
	 */
	public static function alreadyRegistered( string $blockName ): static {
		return ( new static(
			sprintf(
			/* translators: %s: block name */
				__( "Block '%s' is already registered.", 'wp-jarvis' ),
				$blockName
			)
		) )->withContext( [ 'block_name' => $blockName ] );
	}

	/**
	 * Create exception for registration failure.
	 *
	 * @param string $blockName The block name.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function registrationFailed( string $blockName, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: block name, 2: error message */
				__( "Failed to register block '%1\$s': %2\$s", 'wp-jarvis' ),
				$blockName,
				$error
			)
		) )->withContext( [ 'block_name' => $blockName, 'error' => $error ] );
	}

	/**
	 * Create exception for render failure.
	 *
	 * @param string $blockName The block name.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function renderFailed( string $blockName, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: block name, 2: error message */
				__( "Failed to render block '%1\$s': %2\$s", 'wp-jarvis' ),
				$blockName,
				$error
			)
		) )->withContext( [ 'block_name' => $blockName, 'error' => $error ] );
	}

	/**
	 * Create an exception for invalid block.json.
	 *
	 * @param string $path The block.json path.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidBlockJson( string $path ): static {
		return ( new static(
			sprintf(
			/* translators: %s: file path */
				__( "Invalid block.json at '%s'.", 'wp-jarvis' ),
				$path
			)
		) )->withContext( [ 'path' => $path ] );
	}

	/**
	 * Create exception for missing render callback.
	 *
	 * @param string $blockName The block name.
	 *
	 * @return static The exception instance.
	 */
	public static function missingRenderCallback( string $blockName ): static {
		return ( new static(
			sprintf(
			/* translators: %s: block name */
				__( "Block '%s' has no render callback.", 'wp-jarvis' ),
				$blockName
			)
		) )->withContext( [ 'block_name' => $blockName ] );
	}
}
