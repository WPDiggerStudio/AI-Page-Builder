<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Select Field
 *
 * Dropdown select field for forms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class SelectField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'select';
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
		$attrs       = $this->getInputAttributes( $config );
		$options     = $config['options'] ?? [];
		$multiple    = ! empty( $config['multiple'] );
		$placeholder = $config['placeholder'] ?? '';

		// Handle multiple select
		if ( $multiple ) {
			$attrs['multiple'] = 'multiple';
			$attrs['name']     = ( $attrs['name'] ?? '' ) . '[]';
		}

		// Add size for multiple
		if ( isset( $config['size'] ) ) {
			$attrs['size'] = (int) $config['size'];
		}

		// Add a CSS class with Select2 support
		$useSelect2     = $config['select2'] ?? true; // Default to true
		$baseClass      = $config['class'] ?? '';
		$attrs['class'] = $useSelect2 ? trim( 'wpj-select2 ' . $baseClass ) : $baseClass;

		// Add Select2 data attributes
		if ( $useSelect2 && ! empty( $placeholder ) ) {
			$attrs['data-placeholder'] = $placeholder;
		}
		if ( $useSelect2 && isset( $config['allow_clear'] ) ) {
			$attrs['data-allow-clear'] = $config['allow_clear'] ? 'true' : 'false';
		}

		// Convert value to array for easier comparison
		$values = $multiple ? (array) $value : [ $value ];

		$html = sprintf( '<select %s>', $this->buildAttributesString( $attrs ) );

		// Add a placeholder option if specified
		if ( ! empty( $placeholder ) && ! $multiple ) {
			$selected = in_array( '', $values, true ) ? ' selected' : '';
			$html     .= sprintf( '<option value=""%s>%s</option>', $selected, esc_html( $placeholder ) );
		}

		// Add options
		foreach ( $options as $optionValue => $optionLabel ) {
			// Support for option groups
			if ( is_array( $optionLabel ) ) {
				$html .= sprintf( '<optgroup label="%s">', esc_attr( (string) $optionValue ) );
				foreach ( $optionLabel as $subValue => $subLabel ) {
					$selected = in_array( (string) $subValue, array_map( 'strval', $values ), true ) ? ' selected' : '';
					$html     .= sprintf(
						'<option value="%s"%s>%s</option>',
						esc_attr( (string) $subValue ),
						$selected,
						esc_html( (string) $subLabel )
					);
				}
				$html .= '</optgroup>';
			} else {
				$selected = in_array( (string) $optionValue, array_map( 'strval', $values ), true ) ? ' selected' : '';
				$html     .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( (string) $optionValue ),
					$selected,
					esc_html( (string) $optionLabel )
				);
			}
		}

		$html .= '</select>';

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string|array<string> Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		$options    = $this->getFlatOptions( $config['options'] ?? [] );
		$optionKeys = array_keys( $options );
		$multiple   = ! empty( $config['multiple'] );

		if ( $multiple ) {
			$values = (array) $value;

			// Filter to only include valid options
			return array_values( array_filter(
				$values,
				fn( $v ) => in_array( (string) $v, array_map( 'strval', $optionKeys ), true )
			) );
		}

		// For single select, return the value if valid
		return in_array( (string) $value, array_map( 'strval', $optionKeys ), true ) ? (string) $value : '';
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
		$result     = parent::validate( $value, $config );
		$options    = $this->getFlatOptions( $config['options'] ?? [] );
		$optionKeys = array_keys( $options );
		$multiple   = ! empty( $config['multiple'] );

		// Validate selected values are in options
		if ( $multiple ) {
			$values = (array) $value;
			foreach ( $values as $v ) {
				if ( ! in_array( (string) $v, array_map( 'strval', $optionKeys ), true ) ) {
					$result['errors']['invalid'] = __( 'Invalid selection.', 'wp-jarvis' );
					$result['valid']             = false;
					break;
				}
			}
		} else {
			if ( ! empty( $value ) && ! in_array( (string) $value, array_map( 'strval', $optionKeys ), true ) ) {
				$result['errors']['invalid'] = __( 'Invalid selection.', 'wp-jarvis' );
				$result['valid']             = false;
			}
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
		$default  = $config['default'] ?? null;
		$multiple = ! empty( $config['multiple'] );

		if ( $multiple ) {
			return is_array( $default ) ? $default : [];
		}

		return $default ?? '';
	}

	/**
	 * Flatten nested options (opt groups) to a single array.
	 *
	 * @param array<string|int, mixed> $options Options array.
	 *
	 * @return array<string|int, string> Flattened options.
	 */
	private function getFlatOptions( array $options ): array {
		$flat = [];

		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, $value );
			} else {
				$flat[ $key ] = $value;
			}
		}

		return $flat;
	}
}
