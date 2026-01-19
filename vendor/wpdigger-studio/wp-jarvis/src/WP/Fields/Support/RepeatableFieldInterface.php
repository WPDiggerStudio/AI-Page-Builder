<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Support;

/**
 * Repeatable Field Interface
 *
 * Fields that can contain other fields.
 *
 * @package WPJarvis\Framework\WP\Fields\Support
 */
interface RepeatableFieldInterface {
	/**
	 * Get the subfields.
	 *
	 * @return array<string, array<string, mixed>> Sub-field definitions.
	 */
	public function getSubFields(): array;

	/**
	 * Set the subfields.
	 *
	 * @param array<string, array<string, mixed>> $fields Sub-field definitions.
	 *
	 * @return void
	 */
	public function setSubFields( array $fields ): void;
}
