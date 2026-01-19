<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Config;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * ConfigException - Exception for configuration errors.
 */
class ConfigException extends FrameworkException {
	/**
	 * Create an exception for missing a configuration key.
	 *
	 * @param string $key The configuration key.
	 *
	 * @return static The exception instance.
	 */
	public static function missingKey( string $key ): static {
		return ( new static(
			sprintf(
			/* translators: %s: configuration key name */
				__( "Configuration key '%s' is missing.", 'wp-jarvis' ),
				$key
			)
		) )->withContext( [ 'key' => $key ] );
	}

	/**
	 * Create exception for invalid configuration value.
	 *
	 * @param string $key The configuration key.
	 * @param string $expected The expected type/value.
	 * @param mixed $actual The actual value.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidValue( string $key, string $expected, mixed $actual ): static {
		$actualType = get_debug_type( $actual );

		return ( new static(
			sprintf(
			/* translators: 1: configuration key, 2: expected type, 3: actual type */
				__( "Invalid value for '%1\$s'. Expected %2\$s, got %3\$s.", 'wp-jarvis' ),
				$key,
				$expected,
				$actualType
			)
		) )->withContext( [ 'key' => $key, 'expected' => $expected, 'actual_type' => $actualType ] );
	}

	/**
	 * Create an exception for a configuration file not found.
	 *
	 * @param string $path The configuration file path.
	 *
	 * @return static The exception instance.
	 */
	public static function fileNotFound( string $path ): static {
		return ( new static(
			sprintf(
			/* translators: %s: file path */
				__( "Configuration file not found: '%s'.", 'wp-jarvis' ),
				$path
			)
		) )->withContext( [ 'path' => $path ] );
	}

	/**
	 * Create an exception for an invalid configuration file.
	 *
	 * @param string $path The configuration file path.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidFile( string $path, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: file path, 2: error message */
				__( "Invalid configuration file '%1\$s': %2\$s", 'wp-jarvis' ),
				$path,
				$error
			)
		) )->withContext( [ 'path' => $path, 'error' => $error ] );
	}

	/**
	 * Create exception for required configuration.
	 *
	 * @param string $key The configuration key.
	 *
	 * @return static The exception instance.
	 */
	public static function required( string $key ): static {
		return ( new static(
			sprintf(
			/* translators: %s: configuration key */
				__( "Configuration '%s' is required but not set.", 'wp-jarvis' ),
				$key
			)
		) )->withContext( [ 'key' => $key ] );
	}

	/**
	 * Create exception for environment variable not found.
	 *
	 * @param string $name The environment variable name.
	 *
	 * @return static The exception instance.
	 */
	public static function envNotFound( string $name ): static {
		return ( new static(
			sprintf(
			/* translators: %s: environment variable name */
				__( "Environment variable '%s' is not defined.", 'wp-jarvis' ),
				$name
			)
		) )->withContext( [ 'env_var' => $name ] );
	}
}
