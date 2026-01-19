<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Title Field
 *
 * Section title/heading for forms (non-input field).
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class TitleField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'title';
	}

	/**
	 * Render the complete field.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The rendered HTML output.
	 */
	public function render( array $config, mixed $value, string $context ): string {
		$tag   = $config['tag'] ?? 'h3';
		$title = $config['label'] ?? $config['title'] ?? '';
		$class = self::CSS_PREFIX . '--title ' . ( $config['class'] ?? '' );

		$html = sprintf(
			'<div class="%s">',
			esc_attr( trim( $class ) )
		);

		$html .= sprintf(
			'<%1$s class="%2$s__title-text">%3$s</%1$s>',
			esc_attr( $tag ),
			self::CSS_PREFIX,
			esc_html( $title )
		);

		if ( ! empty( $config['description'] ) ) {
			$html .= sprintf(
				'<p class="%s__description">%s</p>',
				self::CSS_PREFIX,
				wp_kses_post( $config['description'] )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the input element (not used for title).
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string Empty string.
	 */
	protected function renderInput( array $config, mixed $value, string $context ): string {
		return '';
	}

	/**
	 * Sanitize a field value (no value for title).
	 *
	 * @param mixed $value Input value.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return null Always null.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		return null;
	}

	/**
	 * Validate a field value (always valid for title).
	 *
	 * @param mixed $value Input value.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array{valid: bool, errors: array<string>} Always valid.
	 */
	public function validate( mixed $value, array $config ): array {
		return [ 'valid' => true, 'errors' => [] ];
	}
}
