<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * oEmbed Field
 *
 * URL input field with inline embed preview.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class OembedField extends AbstractField {

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'oembed';
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

		$attrs                = $this->getInputAttributes( $config );
		$attrs['type']        = 'url';
		$attrs['class']       = 'regular-text wpj-oembed-input ' . ( $config['class'] ?? '' );
		$attrs['data-id']     = $id;
		$placeholder          = $config['placeholder'] ?? __( 'Enter YouTube, Vimeo, Twitter, or other supported URL...', 'wp-jarvis' );
		$attrs['placeholder'] = $placeholder;

		$html = '<div class="' . self::CSS_PREFIX . '__oembed-container" data-field-id="' . esc_attr( $id ) . '">';

		// URL input with the fetch button
		$html .= '<div class="' . self::CSS_PREFIX . '__oembed-input-row">';
		$html .= sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );
		$html .= sprintf(
			'<button type="button" class="button wpj-oembed-fetch" data-field-id="%s" title="%s">
				<span class="dashicons dashicons-visibility"></span> %s
			</button>',
			esc_attr( $id ),
			esc_attr__( 'Fetch embed preview', 'wp-jarvis' ),
			esc_html__( 'Preview', 'wp-jarvis' )
		);
		$html .= '</div>';

		// Embed preview container
		$html .= '<div class="' . self::CSS_PREFIX . '__oembed-preview" id="' . esc_attr( $id ) . '_preview">';

		// Show existing embed if value exists
		if ( ! empty( $value ) ) {
			$embed = $this->getEmbed( $value );
			if ( ! empty( $embed ) ) {
				$html .= $embed;
			} else {
				$html .= '<p class="description">' . esc_html__( 'No preview available. The URL may not support embedding.', 'wp-jarvis' ) . '</p>';
			}
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get the embed HTML for a URL.
	 *
	 * @param string $url The URL to embed.
	 *
	 * @return string The embed HTML.
	 */
	private function getEmbed( string $url ): string {
		if ( empty( $url ) ) {
			return '';
		}

		// Use WordPress oEmbed
		$embed = wp_oembed_get( $url, [ 'width' => 500 ] );

		if ( false === $embed ) {
			return '';
		}

		return $embed;
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
		return esc_url_raw( (string) $value );
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

		if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$result['errors']['url'] = __( 'Please enter a valid URL.', 'wp-jarvis' );
			$result['valid']         = false;
		}

		return $result;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string The default value.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? '';
	}
}
