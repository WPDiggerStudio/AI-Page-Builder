<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Storage;

use WPJarvis\Framework\WP\Fields\Contracts\StorageInterface;

/**
 * Transient Storage
 *
 * Handles storage and retrieval of field values as WordPress transients.
 *
 * @package WPJarvis\Framework\WP\Fields\Storage
 */
class TransientStorage implements StorageInterface {
	/**
	 * Get a field value from transients.
	 *
	 * @param string $objectId Object ID (used as a transient key prefix).
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return mixed Field value or default if not found.
	 */
	public function get( string $objectId, string $fieldKey, string $context ): mixed {
		$key   = $objectId . '_' . $fieldKey;
		$value = get_transient( $key );

		if ( $value === false ) {
			return null;
		}

		return $value;
	}

	/**
	 * Set a field value in transients.
	 *
	 * @param string $objectId Object ID (used as a transient key prefix).
	 * @param string $fieldKey Field key.
	 * @param mixed $value Field value.
	 * @param string $context Storage context.
	 * @param int $expiration Expiration time in seconds (default: 0 = no expiration).
	 *
	 * @return bool True if value was successfully stored.
	 */
	public function set( string $objectId, string $fieldKey, mixed $value, string $context, int $expiration = 0 ): bool {
		$key = $objectId . '_' . $fieldKey;

		return set_transient( $key, $value, $expiration );
	}

	/**
	 * Delete a field value from transients.
	 *
	 * @param string $objectId Object ID (used as a transient key prefix).
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if a value was successfully deleted.
	 */
	public function delete( string $objectId, string $fieldKey, string $context ): bool {
		$key = $objectId . '_' . $fieldKey;

		return delete_transient( $key );
	}

	/**
	 * Get multiple field values from transients.
	 *
	 * @param string $objectId Object ID (used as a transient key prefix).
	 * @param array<string> $fieldKeys Field keys to retrieve.
	 * @param string $context Storage context.
	 *
	 * @return array<string, mixed> Field values keyed by a field key.
	 */
	public function getMultiple( string $objectId, array $fieldKeys, string $context ): array {
		$values = [];

		foreach ( $fieldKeys as $key ) {
			$value = $this->get( $objectId, $key, $context );
			if ( $value !== null ) {
				$values[ $key ] = $value;
			}
		}

		return $values;
	}

	/**
	 * Set multiple field values in transients.
	 *
	 * @param string $objectId Object ID (used as a transient key prefix).
	 * @param array<string, mixed> $values Field values keyed by a field key.
	 * @param string $context Storage context.
	 * @param int $expiration Expiration time in seconds (default: 0 = no expiration).
	 *
	 * @return bool True if values were successfully stored.
	 */
	public function setMultiple( string $objectId, array $values, string $context, int $expiration = 0 ): bool {
		$result = true;

		foreach ( $values as $key => $value ) {
			if ( ! $this->set( $objectId, $key, $value, $context, $expiration ) ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Delete multiple field values from transients.
	 *
	 * @param string $objectId Object ID (used as a transient key prefix).
	 * @param array<string> $fieldKeys Field keys to delete.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully deleted.
	 */
	public function deleteMultiple( string $objectId, array $fieldKeys, string $context ): bool {
		$result = true;

		foreach ( $fieldKeys as $key ) {
			if ( ! $this->delete( $objectId, $key, $context ) ) {
				$result = false;
			}
		}

		return $result;
	}
}
