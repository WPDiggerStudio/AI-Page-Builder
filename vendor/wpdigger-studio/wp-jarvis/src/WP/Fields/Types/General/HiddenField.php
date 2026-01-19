<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * HiddenField - Hidden input field.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class HiddenField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'hidden';
	}

	/**
	 * Render the complete field (no wrapper for hidden fields).
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The rendered HTML output.
	 */
	public function render( array $config, mixed $value, string $context ): string {
		return $this->renderInput( $config, $value, $context );
	}

	/**
	 * Render the input element.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The input HTML.
	 */
	protected function renderInput( array $config, mixed $value, string $context ): string {
		$id   = $config['id'] ?? '';
		$name = $config['name'] ?? $id;

		return sprintf(
			'<input type="hidden" id="%s" name="%s" value="%s" />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): string {
		return sanitize_text_field( (string) $value );
	}
}
