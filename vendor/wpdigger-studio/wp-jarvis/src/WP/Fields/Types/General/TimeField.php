<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * Time Field
 *
 * Time input field for forms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class TimeField extends TextField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'time';
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

		$attrs['type']  = 'time';
		$attrs['value'] = $value ?? '';
		$attrs['class'] = $config['class'] ?? '';

		// Min/max times
		if ( ! empty( $config['min'] ) ) {
			$attrs['min'] = $config['min'];
		}
		if ( ! empty( $config['max'] ) ) {
			$attrs['max'] = $config['max'];
		}

		// Step (in seconds)
		if ( ! empty( $config['step'] ) ) {
			$attrs['step'] = $config['step'];
		}

		return sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );
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

		if ( empty( $value ) ) {
			return '';
		}

		// Validate a time format (HH:MM or HH:MM:SS)
		if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value ) ) {
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
				$result['errors']['time'] = __( 'Please enter a valid time.', 'wp-jarvis' );
				$result['valid']          = false;
			}
		}

		return $result;
	}
}
