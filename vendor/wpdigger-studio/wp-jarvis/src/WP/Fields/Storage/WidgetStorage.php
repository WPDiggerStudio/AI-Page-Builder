<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Storage;

use WPJarvis\Framework\WP\Fields\Contracts\StorageInterface;

/**
 * Widget Storage
 *
 * Handles storage and retrieval of field values as WordPress widget instances.
 *
 * @package WPJarvis\Framework\WP\Fields\Storage
 */
class WidgetStorage implements StorageInterface {
	/**
	 * Get a field value from the widget instance.
	 *
	 * @param string $objectId Widget instance ID.
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return mixed Field value or default if not found.
	 */
	public function get( string $objectId, string $fieldKey, string $context ): mixed {
		$instances = get_option( $objectId );

		if ( $instances === false || ! is_array( $instances ) ) {
			return null;
		}

		return $instances[ $fieldKey ] ?? null;
	}

	/**
	 * Set a field value in the widget instance.
	 *
	 * @param string $objectId Widget instance ID.
	 * @param string $fieldKey Field key.
	 * @param mixed $value Field value.
	 * @param string $context Storage context.
	 *
	 * @return bool True if value was successfully stored.
	 */
	public function set( string $objectId, string $fieldKey, mixed $value, string $context ): bool {
		$instances = get_option( $objectId );

		if ( ! is_array( $instances ) ) {
			$instances = [];
		}

		$instances[ $fieldKey ] = $value;

		return update_option( $objectId, $instances );
	}

	/**
	 * Delete a field value from widget instance.
	 *
	 * @param string $objectId Widget instance ID.
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if a value was successfully deleted.
	 */
	public function delete( string $objectId, string $fieldKey, string $context ): bool {
		$instances = get_option( $objectId );

		if ( ! is_array( $instances ) ) {
			$instances = [];
		}

		unset( $instances[ $fieldKey ] );

		return update_option( $objectId, $instances );
	}

	/**
	 * Get multiple field values from the widget instance.
	 *
	 * @param string $objectId Widget instance ID.
	 * @param array<string> $fieldKeys Field keys to retrieve.
	 * @param string $context Storage context.
	 *
	 * @return array<string, mixed> Field values keyed by a field key.
	 */
	public function getMultiple( string $objectId, array $fieldKeys, string $context ): array {
		$instances = get_option( $objectId );

		if ( ! is_array( $instances ) ) {
			return [];
		}

		$values = [];

		foreach ( $fieldKeys as $key ) {
			if ( isset( $instances[ $key ] ) ) {
				$values[ $key ] = $instances[ $key ];
			}
		}

		return $values;
	}

	/**
	 * Set multiple field values in the widget instance.
	 *
	 * @param string $objectId Widget instance ID.
	 * @param array<string, mixed> $values Field values keyed by a field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully stored.
	 */
	public function setMultiple( string $objectId, array $values, string $context ): bool {
		$instances = get_option( $objectId );

		if ( ! is_array( $instances ) ) {
			$instances = [];
		}

		foreach ( $values as $key => $value ) {
			$instances[ $key ] = $value;
		}

		return update_option( $objectId, $instances );
	}

	/**
	 * Delete multiple field values from widget instance.
	 *
	 * @param string $objectId Widget instance ID.
	 * @param array<string> $fieldKeys Field keys to delete.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully deleted.
	 */
	public function deleteMultiple( string $objectId, array $fieldKeys, string $context ): bool {
		$instances = get_option( $objectId );

		if ( ! is_array( $instances ) ) {
			$instances = [];
		}

		foreach ( $fieldKeys as $key ) {
			unset( $instances[ $key ] );
		}

		return update_option( $objectId, $instances );
	}
}
