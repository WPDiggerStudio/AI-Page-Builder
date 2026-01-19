<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use Illuminate\Support\Arr;
use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * MultiSelectField - Multiple selection dropdown field.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class MultiSelectField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'multi_select';
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
		$attrs   = $this->getInputAttributes( $config );
		$options = $config['options'] ?? [];
		$values  = Arr::wrap( $value );

		$attrs['multiple'] = 'multiple';
		$attrs['name']     = ( $attrs['name'] ?? '' ) . '[]';
		$attrs['class']    = $config['class'] ?? '';

		$html = sprintf( '<select %s>', $this->buildAttributesString( $attrs ) );

		// Render options
		foreach ( $options as $optValue => $optLabel ) {
			// Support opt groups
			if ( is_array( $optLabel ) ) {
				$html .= sprintf( '<optgroup label="%s">', esc_attr( $optValue ) );
				foreach ( $optLabel as $subValue => $subLabel ) {
					$selected = in_array( (string) $subValue, array_map( 'strval', $values ), true ) ? ' selected' : '';
					$html     .= sprintf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $subValue ),
						$selected,
						esc_html( $subLabel )
					);
				}
				$html .= '</optgroup>';
			} else {
				$selected = in_array( (string) $optValue, array_map( 'strval', $values ), true ) ? ' selected' : '';
				$html     .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $optValue ),
					$selected,
					esc_html( $optLabel )
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
	 * @return array<string> Sanitized values.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
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
		$result  = parent::validate( $value, $config );
		$options = $config['options'] ?? [];
		$values  = is_array( $value ) ? $value : [];

		// Validate against allowed options
		$allowedValues = $this->flattenOptions( $options );
		foreach ( $values as $val ) {
			if ( ! in_array( (string) $val, array_map( 'strval', $allowedValues ), true ) ) {
				$result['errors']['invalid'] = __( 'One or more selected values are invalid.', 'wp-jarvis' );
				$result['valid']             = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * Flatten options array (including optgroups).
	 *
	 * @param array<string, mixed> $options Options array.
	 *
	 * @return array<string> Flat array of allowed values.
	 */
	protected function flattenOptions( array $options ): array {
		$flat = [];
		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, array_keys( $value ) );
			} else {
				$flat[] = $key;
			}
		}

		return $flat;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array<string> The default value.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? [];
	}
}
