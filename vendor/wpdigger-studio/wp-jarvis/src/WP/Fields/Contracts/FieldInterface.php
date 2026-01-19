<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Contracts;

/**
 * Field Interface
 *
 * All field types must implement this interface to provide
 * consistent rendering, sanitization, validation, and storage behavior.
 *
 * @package WPJarvis\Framework\WP\Fields\Contracts
 */
interface FieldInterface {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type (e.g., 'text', 'textarea', 'select', 'checkbox').
	 */
	public function getType(): string;

	/**
	 * Render the field for display.
	 *
	 * @param array<string, mixed> $config Field configuration including id, label, options, etc.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context (metabox, widget, shortcode, settings, block).
	 *
	 * @return string The rendered HTML output.
	 */
	public function render( array $config, mixed $value, string $context ): string;

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed Sanitized value safe for storage.
	 */
	public function sanitize( mixed $value, array $config ): mixed;

	/**
	 * Validate a field value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array{valid: bool, errors: array<string>} Validation result with valid flag and error messages.
	 */
	public function validate( mixed $value, array $config ): array;

	/**
	 * Prepare a field value for storage.
	 *
	 * This method allows fields to transform values before storage
	 * (e.g., serialize arrays, format dates, etc.).
	 *
	 * @param mixed $value Input value to prepare.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed Prepared value for storage.
	 */
	public function prepare( mixed $value, array $config ): mixed;

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed The default value for this field.
	 */
	public function getDefault( array $config ): mixed;

	/**
	 * Get the field's assets (scripts and styles).
	 *
	 * @return array{scripts: array, styles: array} Assets to enqueue.
	 */
	public function getAssets(): array;
}
