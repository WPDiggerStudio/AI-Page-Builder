<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * Date Field
 *
 * Date input field for forms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class DateField extends TextField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'date';
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

		$attrs['type']  = 'date';
		$attrs['value'] = $value ?? '';
		$attrs['class'] = $config['class'] ?? '';

		// Min/max dates
		if ( ! empty( $config['min'] ) ) {
			$attrs['min'] = $config['min'];
		}
		if ( ! empty( $config['max'] ) ) {
			$attrs['max'] = $config['max'];
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
	public function sanitize( mixed $value, array $config ): string {
		$value = (string) $value;

		if ( empty( $value ) ) {
			return '';
		}

		// Validate date format (YYYY-MM-DD)
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			$date = \DateTime::createFromFormat( 'Y-m-d', $value );
			if ( $date && $date->format( 'Y-m-d' ) === $value ) {
				return $value;
			}
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
				$result['errors']['date'] = __( 'Please enter a valid date.', 'wp-jarvis' );
				$result['valid']          = false;
			}
		}

		return $result;
	}
}
