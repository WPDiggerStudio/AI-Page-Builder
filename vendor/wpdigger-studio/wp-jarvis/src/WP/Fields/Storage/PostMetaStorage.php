<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Storage;

use WPJarvis\Framework\WP\Fields\Contracts\StorageInterface;

/**
 * Post-Meta Storage
 *
 * Handles storage and retrieval of field values as WordPress post-meta.
 *
 * @package WPJarvis\Framework\WP\Fields\Storage
 */
class PostMetaStorage implements StorageInterface {
	/**
	 * Get a field value from post-meta.
	 *
	 * @param string $objectId Post ID.
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return mixed Field value or default if not found.
	 */
	public function get( string $objectId, string $fieldKey, string $context ): mixed {
		$value = get_post_meta( (int) $objectId, '_' . $fieldKey, true );

		if ( $value === '' || $value === false ) {
			return null;
		}

		return $value;
	}

	/**
	 * Set a field value in post-meta.
	 *
	 * @param string $objectId Post ID.
	 * @param string $fieldKey Field key.
	 * @param mixed $value Field value.
	 * @param string $context Storage context.
	 *
	 * @return bool True if value was successfully stored.
	 */
	public function set( string $objectId, string $fieldKey, mixed $value, string $context ): bool {
		$metaKey = '_' . $fieldKey;

		// Don't store empty values (delete instead)
		if ( $value === '' || $value === null || $value === [] ) {
			return delete_post_meta( (int) $objectId, $metaKey );
		}

		return update_post_meta( (int) $objectId, $metaKey, $value );
	}

	/**
	 * Delete a field value from post-meta.
	 *
	 * @param string $objectId Post ID.
	 * @param string $fieldKey Field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if a value was successfully deleted.
	 */
	public function delete( string $objectId, string $fieldKey, string $context ): bool {
		return delete_post_meta( (int) $objectId, '_' . $fieldKey );
	}

	/**
	 * Get multiple field values from post meta.
	 *
	 * @param string $objectId Post ID.
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
	 * Set multiple field values in post-meta.
	 *
	 * @param string $objectId Post ID.
	 * @param array<string, mixed> $values Field values keyed by a field key.
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
	 * Delete multiple field values from post meta.
	 *
	 * @param string $objectId Post ID.
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
