<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Contracts;

/**
 * Storage Interface
 *
 * Abstracts storage operations for field data across different
 * WordPress storage backends (post meta, term meta, user meta,
 * options, widgets, transients, block attributes).
 *
 * @package WPJarvis\Framework\WP\Fields\Contracts
 */
interface StorageInterface {
	/**
	 * Get a field value from storage.
	 *
	 * @param string $objectId Object ID (post ID, user ID, option key, etc.).
	 * @param string $fieldKey Field key/identifier.
	 * @param string $context Storage context (post_meta, term_meta, user_meta, option, widget, shortcode, block).
	 *
	 * @return mixed Field value or default if not found.
	 */
	public function get( string $objectId, string $fieldKey, string $context ): mixed;

	/**
	 * Set a field value in storage.
	 *
	 * @param string $objectId Object ID.
	 * @param string $fieldKey Field key/identifier.
	 * @param mixed $value Field value to store.
	 * @param string $context Storage context.
	 *
	 * @return bool True if value was successfully stored.
	 */
	public function set( string $objectId, string $fieldKey, mixed $value, string $context ): bool;

	/**
	 * Delete a field value from storage.
	 *
	 * @param string $objectId Object ID.
	 * @param string $fieldKey Field key/identifier.
	 * @param string $context Storage context.
	 *
	 * @return bool True if a value was successfully deleted.
	 */
	public function delete( string $objectId, string $fieldKey, string $context ): bool;

	/**
	 * Get multiple field values from storage.
	 *
	 * @param string $objectId Object ID.
	 * @param array<string> $fieldKeys Field keys to retrieve.
	 * @param string $context Storage context.
	 *
	 * @return array<string, mixed> Field values keyed by a field key.
	 */
	public function getMultiple( string $objectId, array $fieldKeys, string $context ): array;

	/**
	 * Set multiple field values in storage.
	 *
	 * @param string $objectId Object ID.
	 * @param array<string, mixed> $values Field values keyed by a field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully stored.
	 */
	public function setMultiple( string $objectId, array $values, string $context ): bool;

	/**
	 * Delete multiple field values from storage.
	 *
	 * @param string $objectId Object ID.
	 * @param array<string> $fieldKeys Field keys to delete.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully deleted.
	 */
	public function deleteMultiple( string $objectId, array $fieldKeys, string $context ): bool;
}
