<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Number Field
 *
 * Numeric input field for forms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class NumberField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'number';
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

		$attrs['type']  = 'number';
		$attrs['value'] = $value ?? '';
		$attrs['class'] = $config['class'] ?? 'small-text';

		// Min/max/step
		if ( isset( $config['min'] ) ) {
			$attrs['min'] = $config['min'];
		}
		if ( isset( $config['max'] ) ) {
			$attrs['max'] = $config['max'];
		}
		if ( isset( $config['step'] ) ) {
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
	 * @return int|float|string Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		if ( $value === '' || $value === null ) {
			return '';
		}

		// Check if float allowed (a step is not integer)
		$step = $config['step'] ?? 1;
		if ( is_float( $step ) || ( is_string( $step ) && str_contains( $step, '.' ) ) ) {
			return (float) $value;
		}

		return (int) $value;
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

		if ( $value === '' || $value === null ) {
			return $result;
		}

		// Check if numeric
		if ( ! is_numeric( $value ) ) {
			$result['errors']['numeric'] = __( 'Please enter a valid number.', 'wp-jarvis' );
			$result['valid']             = false;

			return $result;
		}

		$numValue = (float) $value;

		// Check min
		if ( isset( $config['min'] ) && $numValue < $config['min'] ) {
			$result['errors']['min'] = sprintf(
				__( 'Value must be at least %s.', 'wp-jarvis' ),
				$config['min']
			);
			$result['valid']         = false;
		}

		// Check max
		if ( isset( $config['max'] ) && $numValue > $config['max'] ) {
			$result['errors']['max'] = sprintf(
				__( 'Value must be at most %s.', 'wp-jarvis' ),
				$config['max']
			);
			$result['valid']         = false;
		}

		return $result;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed The default value.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? '';
	}
}
