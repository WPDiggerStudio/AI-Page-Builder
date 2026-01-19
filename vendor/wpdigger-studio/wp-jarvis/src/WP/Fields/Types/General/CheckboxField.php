<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use Illuminate\Support\Arr;
use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * CheckboxField - Toggle switch/checkbox field.
 *
 * Supports a single toggle or multiple checkboxes with toggle UI.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class CheckboxField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'checkbox';
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
		$options = $config['options'] ?? [];
		$id      = $config['id'] ?? '';
		$name    = $config['name'] ?? $id;

		// Multiple checkboxes
		if ( ! empty( $options ) ) {
			return $this->renderMultiple( $config, $value, $options );
		}

		// Single toggle switch
		return $this->renderToggle( $config, $value );
	}

	/**
	 * Render a single toggle switch.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current value.
	 *
	 * @return string HTML output.
	 */
	protected function renderToggle( array $config, mixed $value ): string {
		$id          = $config['id'] ?? '';
		$name        = $config['name'] ?? $id;
		$checked     = ! empty( $value );
		$toggleLabel = $config['checkbox_label'] ?? $config['toggle_label'] ?? '';

		$html = '<label class="' . self::CSS_PREFIX . '__toggle">';

		$html .= sprintf(
			'<input type="checkbox" id="%s" name="%s" value="1" %s />',
			esc_attr( $id ),
			esc_attr( $name ),
			$checked ? 'checked' : ''
		);

		$html .= '<span class="' . self::CSS_PREFIX . '__toggle-switch"></span>';

		if ( $toggleLabel ) {
			$html .= sprintf(
				'<span class="%s__toggle-label">%s</span>',
				self::CSS_PREFIX,
				esc_html( $toggleLabel )
			);
		}

		$html .= '</label>';

		return $html;
	}

	/**
	 * Render multiple checkboxes.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current value(s).
	 * @param array<string, string> $options Checkbox options.
	 *
	 * @return string HTML output.
	 */
	protected function renderMultiple( array $config, mixed $value, array $options ): string {
		$id        = $config['id'] ?? '';
		$name      = $config['name'] ?? $id;
		$values    = Arr::wrap( $value );
		$inline    = ! empty( $config['inline'] );
		$useToggle = $config['toggle'] ?? false;

		$wrapperClass = self::CSS_PREFIX . '__options';
		if ( $inline ) {
			$wrapperClass .= ' ' . self::CSS_PREFIX . '__options--inline';
		}

		$html = '<div class="' . esc_attr( $wrapperClass ) . '">';

		$index = 0;
		foreach ( $options as $optValue => $optLabel ) {
			$optId   = $id . '_' . $index;
			$checked = in_array( (string) $optValue, array_map( 'strval', $values ), true );

			if ( $useToggle ) {
				$html .= '<label class="' . self::CSS_PREFIX . '__toggle">';
				$html .= sprintf(
					'<input type="checkbox" id="%s" name="%s[]" value="%s" %s />',
					esc_attr( $optId ),
					esc_attr( $name ),
					esc_attr( $optValue ),
					$checked ? 'checked' : ''
				);
				$html .= '<span class="' . self::CSS_PREFIX . '__toggle-switch"></span>';
				$html .= sprintf(
					'<span class="%s__toggle-label">%s</span>',
					self::CSS_PREFIX,
					esc_html( $optLabel )
				);
				$html .= '</label>';
			} else {
				$html .= '<div class="' . self::CSS_PREFIX . '__option">';
				$html .= sprintf(
					'<input type="checkbox" id="%s" name="%s[]" value="%s" %s />',
					esc_attr( $optId ),
					esc_attr( $name ),
					esc_attr( $optValue ),
					$checked ? 'checked' : ''
				);
				$html .= sprintf(
					'<label for="%s">%s</label>',
					esc_attr( $optId ),
					esc_html( $optLabel )
				);
				$html .= '</div>';
			}

			++ $index;
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
	 * @return array|int Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): array|int {
		$options = $config['options'] ?? [];

		// Multiple checkboxes
		if ( ! empty( $options ) ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			$allowedValues = array_keys( $options );

			return array_values( array_intersect( array_map( 'strval', $value ), array_map( 'strval', $allowedValues ) ) );
		}

		// Single checkbox - return boolean
		return ! empty( $value ) ? 1 : 0;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed The default value.
	 */
	public function getDefault( array $config ): mixed {
		$options = $config['options'] ?? [];

		if ( ! empty( $options ) ) {
			return $config['default'] ?? [];
		}

		return $config['default'] ?? 0;
	}
}
