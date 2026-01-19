<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Radio Field
 *
 * Radio button group field for forms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class RadioField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'radio';
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
		$options  = $config['options'] ?? [];
		$inline   = ! empty( $config['inline'] );
		$id       = $config['id'] ?? '';
		$name     = $config['name'] ?? $id;
		$disabled = ! empty( $config['disabled'] );
		$readonly = ! empty( $config['readonly'] );

		$wrapperClass = self::CSS_PREFIX . '__options';
		if ( $inline ) {
			$wrapperClass .= ' ' . self::CSS_PREFIX . '__options--inline';
		}

		$html = sprintf( '<div class="%s" role="radiogroup">', esc_attr( $wrapperClass ) );

		foreach ( $options as $optionValue => $optionLabel ) {
			$optionId = $id . '_' . sanitize_key( (string) $optionValue );
			$checked  = (string) $optionValue === (string) $value ? ' checked' : '';

			$html .= sprintf( '<div class="%s__option">', self::CSS_PREFIX );
			$html .= sprintf(
				'<input type="radio" id="%s" name="%s" value="%s"%s%s%s />',
				esc_attr( $optionId ),
				esc_attr( $name ),
				esc_attr( (string) $optionValue ),
				$checked,
				$disabled ? ' disabled' : '',
				$readonly ? ' disabled' : ''
			);
			$html .= sprintf(
				'<label for="%s">%s</label>',
				esc_attr( $optionId ),
				esc_html( $optionLabel )
			);
			$html .= '</div>';
		}

		$html .= '</div>';

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
	public function sanitize( mixed $value, array $config ): string {
		$options = array_keys( $config['options'] ?? [] );

		return in_array( (string) $value, array_map( 'strval', $options ), true )
			? (string) $value
			: '';
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
		$options = array_keys( $config['options'] ?? [] );

		if ( ! empty( $value ) && ! in_array( (string) $value, array_map( 'strval', $options ), true ) ) {
			$result['errors']['invalid'] = __( 'Invalid selection.', 'wp-jarvis' );
			$result['valid']             = false;
		}

		return $result;
	}
}
