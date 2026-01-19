<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Code Field
 *
 * Code/preformatted text input field with Ace Editor integration.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class CodeField extends AbstractField {

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'code';
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
		$id   = $config['id'] ?? '';
		$name = $config['name'] ?? $id;

		// Ace Editor options
		$language = $config['options']['language'] ?? $config['language'] ?? 'html';
		$theme    = $config['options']['theme'] ?? $config['theme'] ?? 'chrome';
		$height   = $config['options']['height'] ?? $config['height'] ?? '300px';
		$readOnly = ! empty( $config['readonly'] ) || ! empty( $config['disabled'] );

		// Disable Ace if requested
		$disableAce = ! empty( $config['options']['disable_ace'] );

		if ( $disableAce ) {
			// Plain textarea fallback
			$attrs          = $this->getInputAttributes( $config );
			$attrs['rows']  = $config['rows'] ?? 10;
			$attrs['cols']  = $config['cols'] ?? 80;
			$attrs['class'] = 'large-text code ' . ( $config['class'] ?? '' );
			$attrs['style'] = 'font-family: monospace; font-size: 13px;';
			unset( $attrs['value'] );

			return sprintf(
				'<textarea %s>%s</textarea>',
				$this->buildAttributesString( $attrs ),
				esc_textarea( $value ?? '' )
			);
		}

		// Ace Editor container
		$editorId = $id . '_ace_editor';

		$html = '<div class="' . self::CSS_PREFIX . '__ace-wrapper">';

		// Hidden textarea for form submission
		$html .= sprintf(
			'<textarea id="%s" name="%s" class="wpj-ace-textarea" style="display:none;">%s</textarea>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_textarea( $value ?? '' )
		);

		// Ace Editor container div
		$html .= sprintf(
			'<div id="%s" class="wpj-ace-editor" data-textarea="%s" data-language="%s" data-theme="%s" data-readonly="%s" style="height: %s;"></div>',
			esc_attr( $editorId ),
			esc_attr( $id ),
			esc_attr( $language ),
			esc_attr( $theme ),
			$readOnly ? 'true' : 'false',
			esc_attr( $height )
		);

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
		// Preserve code formatting but sanitize for security
		if ( ! empty( $config['allow_unsafe'] ) ) {
			return (string) $value;
		}

		return wp_kses_post( (string) $value );
	}
}
