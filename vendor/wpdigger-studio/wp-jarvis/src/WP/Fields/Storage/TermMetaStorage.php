<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Storage;

use WPJarvis\Framework\WP\Fields\Contracts\StorageInterface;

/**
 * Term Meta-Storage
 *
 * Handles storage and retrieval of field values as WordPress term meta.
 *
 * @package WPJarvis\Framework\WP\Fields\Storage
 */
class TermMetaStorage implements StorageInterface {
	/**
	 * Get a field value from the term meta.
	 *
	 * @param string $objectId Term ID.
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return mixed Field value or default if not found.
	 */
	public function get( string $objectId, string $fieldKey, string $context ): mixed {
		$value = get_term_meta( (int) $objectId, $fieldKey, true );

		if ( $value === '' || $value === false ) {
			return null;
		}

		return $value;
	}

	/**
	 * Set a field value in the term meta.
	 *
	 * @param string $objectId Term ID.
	 * @param string $fieldKey Field key.
	 * @param mixed $value Field value.
	 * @param string $context Storage context.
	 *
	 * @return bool True if value was successfully stored.
	 */
	public function set( string $objectId, string $fieldKey, mixed $value, string $context ): bool {
		return update_term_meta( (int) $objectId, $fieldKey, $value );
	}

	/**
	 * Delete a field value from term meta.
	 *
	 * @param string $objectId Term ID.
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if a value was successfully deleted.
	 */
	public function delete( string $objectId, string $fieldKey, string $context ): bool {
		return delete_term_meta( (int) $objectId, $fieldKey );
	}

	/**
	 * Get multiple field values from term meta.
	 *
	 * @param string $objectId Term ID.
	 * @param array<string> $fieldKeys Field keys.
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
	 * Set multiple field values in term meta.
	 *
	 * @param string $objectId Term ID.
	 * @param array<string, mixed> $values Field values.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully stored.
	 */
	public function setMultiple( string $objectId, array $values, string $context ): bool {
		$result = true;

		foreach ( $values as $key => $value ) {
			if ( ! $this->set( $objectId, $key, $value, $context ) ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Delete multiple field values from term meta.
	 *
	 * @param string $objectId Term ID.
	 * @param array<string> $fieldKeys Field keys.
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
