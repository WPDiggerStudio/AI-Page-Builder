<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Storage;

use WPJarvis\Framework\WP\Fields\Contracts\StorageInterface;

/**
 * Block Attributes Storage
 *
 * Handles storage and retrieval of block attribute values.
 *
 * @package WPJarvis\Framework\WP\Fields\Storage
 */
class BlockAttributesStorage implements StorageInterface {
	/**
	 * Get a block attribute value.
	 *
	 * @param string $objectId Block instance ID or unique identifier.
	 * @param string $fieldKey Attribute key.
	 * @param string $context Storage context.
	 *
	 * @return mixed Field value or default if not found.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function get( string $objectId, string $fieldKey, string $context ): mixed {
		if ( $context === 'block' ) {
			$prefix = wpj_config( 'app.slug' );

			// Get block data from post-meta
			$blockData = get_post_meta( (int) $objectId, $prefix . '_block_data', true );

			if ( $blockData === false || ! is_array( $blockData ) ) {
				return null;
			}

			$attributes = $blockData['attributes'] ?? [];

			return $attributes[ $fieldKey ] ?? null;
		}

		// Default implementation for other contexts
		return null;
	}

	/**
	 * Set a block attribute value.
	 *
	 * @param string $objectId Block instance ID.
	 * @param string $fieldKey Attribute key.
	 * @param mixed $value Attribute value.
	 * @param string $context Storage context.
	 *
	 * @return bool True if value was successfully stored.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function set( string $objectId, string $fieldKey, mixed $value, string $context ): bool {
		$prefix = wpj_config( 'app.slug' );

		if ( $context === 'block' ) {
			// Get block data from post-meta
			$blockData = get_post_meta( (int) $objectId, $prefix . '_block_data', true );

			if ( $blockData === false || ! is_array( $blockData ) ) {
				$blockData = [];
			}

			$attributes              = $blockData['attributes'] ?? [];
			$attributes[ $fieldKey ] = $value;

			// Update block in a database
			$blockData['attributes'] = $attributes;
			update_post_meta( (int) $objectId, $prefix . '_block_data', $blockData );

			return true;
		}

		// Default implementation for other contexts
		return false;
	}

	/**
	 * Delete a block attribute value.
	 *
	 * @param string $objectId Block instance ID.
	 * @param string $fieldKey Attribute key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if a value was successfully deleted.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function delete( string $objectId, string $fieldKey, string $context ): bool {
		$prefix = wpj_config( 'app.slug' );

		if ( $context === 'block' ) {
			// Get block data from post-meta
			$blockData = get_post_meta( (int) $objectId, $prefix . '_block_data', true );

			if ( $blockData === false || ! is_array( $blockData ) ) {
				return false;
			}

			$attributes = $blockData['attributes'] ?? [];

			if ( isset( $attributes[ $fieldKey ] ) ) {
				unset( $attributes[ $fieldKey ] );
				$blockData['attributes'] = $attributes;
				update_post_meta( (int) $objectId, $prefix . '_block_data', $blockData );

				return true;
			}

			return false;
		}

		// Default implementation for other contexts
		return false;
	}

	/**
	 * Get multiple block attribute values.
	 *
	 * @param string $objectId Block instance ID.
	 * @param array<string> $fieldKeys Attribute keys to retrieve.
	 * @param string $context Storage context.
	 *
	 * @return array<string, mixed> Attribute values keyed by field key.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function getMultiple( string $objectId, array $fieldKeys, string $context ): array {
		$prefix = wpj_config( 'app.slug' );

		if ( $context === 'block' ) {
			// Get block data from post-meta
			$blockData = get_post_meta( (int) $objectId, $prefix . '_block_data', true );

			if ( $blockData === false || ! is_array( $blockData ) ) {
				return [];
			}

			$attributes = $blockData['attributes'] ?? [];

			$values = [];

			foreach ( $fieldKeys as $key ) {
				if ( isset( $attributes[ $key ] ) ) {
					$values[ $key ] = $attributes[ $key ];
				}
			}

			return $values;
		}

		// Default implementation for other contexts
		return [];
	}

	/**
	 * Set multiple block attribute values.
	 *
	 * @param string $objectId Block instance ID.
	 * @param array<string, mixed> $values Attribute values keyed by field key.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully stored.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function setMultiple( string $objectId, array $values, string $context ): bool {
		$prefix = wpj_config( 'app.slug' );

		if ( $context === 'block' ) {
			// Get block data from post-meta
			$blockData = get_post_meta( (int) $objectId, $prefix . '_block_data', true );

			if ( $blockData === false || ! is_array( $blockData ) ) {
				$blockData = [];
			}

			$attributes              = $blockData['attributes'] ?? [];
			$attributes              = array_merge( $attributes, $values );
			$blockData['attributes'] = $attributes;
			update_post_meta( (int) $objectId, $prefix . '_block_data', $blockData );

			return true;
		}

		// Default implementation for other contexts
		return false;
	}

	/**
	 * Delete multiple block attribute values.
	 *
	 * @param string $objectId Block instance ID.
	 * @param array<string> $fieldKeys Attribute keys to delete.
	 * @param string $context Storage context.
	 *
	 * @return bool True if values were successfully deleted.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function deleteMultiple( string $objectId, array $fieldKeys, string $context ): bool {
		$prefix = wpj_config( 'app.slug' );

		if ( $context === 'block' ) {
			// Get block data from post-meta
			$blockData = get_post_meta( (int) $objectId, $prefix . '_block_data', true );

			if ( $blockData === false || ! is_array( $blockData ) ) {
				return false;
			}

			$attributes = $blockData['attributes'] ?? [];

			foreach ( $fieldKeys as $key ) {
				if ( isset( $attributes[ $key ] ) ) {
					unset( $attributes[ $key ] );
				}
			}

			if ( ! empty( $attributes ) ) {
				$blockData['attributes'] = $attributes;
				update_post_meta( (int) $objectId, $prefix . '_block_data', $blockData );

				return true;
			}

			return false;
		}

		// Default implementation for other contexts
		return false;
	}
}
