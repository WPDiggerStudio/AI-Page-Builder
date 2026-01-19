<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Textarea Field
 *
 * Multi-line text input field for forms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class TextareaField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'textarea';
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
		$attrs = $this->getInputAttributes( $config );

		// Add rows and cols
		$attrs['rows'] = $config['rows'] ?? 5;
		$attrs['cols'] = $config['cols'] ?? 50;

		// Add CSS class
		$class          = $config['class'] ?? 'large-text';
		$attrs['class'] = $class;

		// Remove value from attrs (textarea uses inner content)
		unset( $attrs['value'] );

		return sprintf(
			'<textarea %s>%s</textarea>',
			$this->buildAttributesString( $attrs ),
			esc_textarea( $value ?? '' )
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
	public function sanitize( mixed $value, array $config ): mixed {
		// Allow HTML if configured
		if ( ! empty( $config['allow_html'] ) ) {
			return wp_kses_post( (string) $value );
		}

		return sanitize_textarea_field( (string) $value );
	}

	/**
	 * Validate a field value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array{valid: bool, errors: array<string>} Validation result.
	 */
	public function validate( mixed $value, array $config ): array {
		$result = parent::validate( $value, $config );

		// Check min length
		if ( isset( $config['min_length'] ) && is_string( $value ) && strlen( $value ) < $config['min_length'] ) {
			$result['errors']['min_length'] = sprintf(
				__( 'Minimum length is %d characters.', 'wp-jarvis' ),
				$config['min_length']
			);
			$result['valid']                = false;
		}

		// Check max length
		if ( isset( $config['max_length'] ) && is_string( $value ) && strlen( $value ) > $config['max_length'] ) {
			$result['errors']['max_length'] = sprintf(
				__( 'Maximum length is %d characters.', 'wp-jarvis' ),
				$config['max_length']
			);
			$result['valid']                = false;
		}

		return $result;
	}
}
