<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * MoneyField - Currency input with prefix icon.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class MoneyField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'text_money';
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

		$attrs['type']        = 'text';
		$attrs['value']       = $value ?? '';
		$attrs['inputmode']   = 'decimal';
		$attrs['pattern']     = '[0-9]*[.,]?[0-9]*';
		$attrs['placeholder'] = $config['placeholder'] ?? '0.00';

		// Size classes
		$size           = $config['size'] ?? 'medium';
		$sizeClass      = match ( $size ) {
			'small' => 'small-text',
			'large', 'full' => 'large-text',
			default => 'regular-text',
		};
		$attrs['class'] = 'wpj-money-input ' . $sizeClass . ' ' . ( $config['class'] ?? '' );

		$prefix = $config['before_field'] ?? '$';

		// Use icon wrapper like a text field
		$html = '<div class="' . self::CSS_PREFIX . '__icon-wrapper">';
		$html .= '<span class="' . self::CSS_PREFIX . '__icon ' . self::CSS_PREFIX . '__icon--left">';
		$html .= '<span class="' . self::CSS_PREFIX . '__icon-text">' . esc_html( $prefix ) . '</span>';
		$html .= '</span>';
		$html .= sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );
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
	public function sanitize( mixed $value, array $config ): mixed {
		// Remove any currency symbols and clean the value
		$value = preg_replace( '/[^0-9.,\-]/', '', (string) $value );

		return sanitize_text_field( $value );
	}
}
