<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Cache;

use WPJarvis\Framework\Support\Facades\Config;

/**
 * TransientStore - WordPress transient-based cache.
 */

/**
 * TransientStore - WordPress transient-based cache.
 */
class TransientStore {
	/**
	 * The cache key prefix.
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Create a new TransientStore instance.
	 *
	 * @param string $prefix The cache key prefix.
	 */
	public function __construct( string $prefix ) {
		$prefix       = trim( $prefix );
		$this->prefix = $prefix !== '' ? $prefix : (string) Config::get( 'app.slug' . '_', 'WpJarvis_' );
	}


	/**
	 * Create a new TransientStore instance.
	 *
	 * @param string|null $prefix The cache key prefix.
	 *
	 * @return static The new TransientStore instance.
	 */
	public static function make( ?string $prefix = null ): static {
		$prefix = $prefix !== null ? trim( $prefix ) : '';

		return new static( $prefix !== '' ? $prefix : (string) Config::get( 'app.slug' . '_', 'WpJarvis_' ) );
	}


	/**
	 * Get a value from a cache.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param mixed $default The default value.
	 *
	 * @return mixed The cached value or default.
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$value = get_transient( $this->prefix . $key );

		return $value !== false ? $value : $default;
	}

	/**
	 * Set a value in the cache.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param mixed $value The value to cache.
	 * @param int $ttl The time to live in seconds.
	 *
	 * @return bool True if successful.
	 */
	public function set( string $key, mixed $value, int $ttl = 0 ): bool {
		return set_transient( $this->prefix . $key, $value, $ttl );
	}

	/**
	 * Check if a key exists in the cache.
	 *
	 * @param string $key The cache key (without prefix).
	 *
	 * @return bool True if the key exists.
	 */
	public function has( string $key ): bool {
		return get_transient( $this->prefix . $key ) !== false;
	}

	/**
	 * Remove a value from the cache.
	 *
	 * @param string $key The cache key (without prefix).
	 *
	 * @return bool True if successful.
	 */
	public function forget( string $key ): bool {
		return delete_transient( $this->prefix . $key );
	}

	/**
	 * Get or cache a value using callback.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param int $ttl The time to live in seconds.
	 * @param callable $callback The callback to generate value.
	 *
	 * @return mixed The cached or generated value.
	 */
	public function remember( string $key, int $ttl, callable $callback ): mixed {
		$value = $this->get( $key );

		if ( $value !== null ) {
			return $value;
		}

		$value = $callback();
		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Get or cache a value forever.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param callable $callback The callback to generate value.
	 *
	 * @return mixed The cached or generated value.
	 */
	public function rememberForever( string $key, callable $callback ): mixed {
		return $this->remember( $key, 0, $callback );
	}

	/**
	 * Get and remove a value from the cache.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param mixed $default The default value.
	 *
	 * @return mixed The cached value or default.
	 */
	public function pull( string $key, mixed $default = null ): mixed {
		$value = $this->get( $key, $default );
		$this->forget( $key );

		return $value;
	}

	/**
	 * Flush cache entries matching a pattern.
	 *
	 * @param string $pattern The key pattern to match.
	 *
	 * @return int The number of flushed entries.
	 */
	public function flush( string $pattern = '' ): int {
		global $wpdb;

		$prefix = $this->prefix . $pattern;
		$count  = 0;

		$transients = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $prefix . '%',
				'_transient_timeout_' . $prefix . '%'
			)
		);

		foreach ( $transients as $transient ) {
			$key = str_replace( [ '_transient_timeout_', '_transient_' ], '', $transient );
			if ( delete_transient( $key ) ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Increment a cached value.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param int $value The increment value.
	 *
	 * @return int The new value.
	 */
	public function increment( string $key, int $value = 1 ): int {
		$current = (int) $this->get( $key, 0 );
		$new     = $current + $value;
		$this->set( $key, $new );

		return $new;
	}

	/**
	 * Decrement a cached value.
	 *
	 * @param string $key The cache key (without prefix).
	 * @param int $value The decrement value.
	 *
	 * @return int The new value.
	 */
	public function decrement( string $key, int $value = 1 ): int {
		return $this->increment( $key, - $value );
	}
}
