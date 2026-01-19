<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Color Picker Field
 *
 * Color picker input field using WordPress color picker.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class ColorPickerField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'color_picker';
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

		$attrs['type']       = 'text';
		$attrs['value']      = $value ?? '';
		$attrs['class']      = 'wpj-color-picker';
		$attrs['data-alpha'] = ! empty( $config['alpha'] ) ? 'true' : 'false';

		// Default color
		if ( ! empty( $config['default'] ) ) {
			$attrs['data-default-color'] = $config['default'];
		}

		$html = sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );

		// Add color preview
		$html .= sprintf(
			'<span class="%s__color-preview" style="background-color: %s;"></span>',
			self::CSS_PREFIX,
			esc_attr( $value ?? '#ffffff' )
		);

		return $html;
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
		$value = (string) $value;

		// Allow empty value
		if ( empty( $value ) ) {
			return '';
		}

		// Validate hex color (with or without alpha)
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $value ) ) {
			return $value;
		}

		// Validate rgba
		if ( preg_match( '/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)$/', $value ) ) {
			return $value;
		}

		return '';
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

		if ( ! empty( $value ) ) {
			$sanitized = $this->sanitize( $value, $config );
			if ( empty( $sanitized ) ) {
				$result['errors']['color'] = __( 'Please enter a valid color value.', 'wp-jarvis' );
				$result['valid']           = false;
			}
		}

		return $result;
	}

	/**
	 * Get the field's assets (scripts and styles).
	 *
	 * @return array{scripts: array, styles: array} Assets to enqueue.
	 */
	public function getAssets(): array {
		return [
			'scripts' => [ 'wp-color-picker' ],
			'styles'  => [ 'wp-color-picker' ],
		];
	}
}
