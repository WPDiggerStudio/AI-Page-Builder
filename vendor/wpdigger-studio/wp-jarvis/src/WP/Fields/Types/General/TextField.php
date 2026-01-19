<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * TextField - Text input field.
 *
 * Supports icons, placeholders, and various sizes.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class TextField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'text';
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

		$attrs['type']  = $this->getInputType();
		$attrs['value'] = $value ?? '';
		$attrs['class'] = $config['class'] ?? '';

		// Check for icons
		$iconLeft  = $config['icon_left'] ?? $config['icon'] ?? null;
		$iconRight = $config['icon_right'] ?? null;
		$hasIcons  = $iconLeft || $iconRight;

		if ( $hasIcons ) {
			return $this->renderWithIcons( $attrs, $iconLeft, $iconRight );
		}

		return sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );
	}

	/**
	 * Render input with an icon wrapper.
	 *
	 * @param array<string, string> $attrs Input attributes.
	 * @param string|null $iconLeft Left icon (dashicon name or HTML).
	 * @param string|null $iconRight Right icon (dashicon name or HTML).
	 *
	 * @return string HTML output.
	 */
	protected function renderWithIcons( array $attrs, ?string $iconLeft, ?string $iconRight ): string {
		$html = '<div class="' . self::CSS_PREFIX . '__icon-wrapper">';

		// Left icon
		if ( $iconLeft ) {
			$html .= $this->renderIcon( $iconLeft, 'left' );
		}

		// Input
		$html .= sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );

		// Right icon
		if ( $iconRight ) {
			$html .= $this->renderIcon( $iconRight, 'right' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render an icon element.
	 *
	 * @param string $icon Icon name or HTML.
	 * @param string $position Position (left or right).
	 *
	 * @return string HTML output.
	 */
	protected function renderIcon( string $icon, string $position ): string {
		$class = self::CSS_PREFIX . '__icon ' . self::CSS_PREFIX . '__icon--' . $position;

		// Check if it's a dashicon
		if ( strpos( $icon, 'dashicons-' ) === 0 ) {
			return sprintf(
				'<span class="%s"><span class="dashicons %s"></span></span>',
				esc_attr( $class ),
				esc_attr( $icon )
			);
		}

		// If it's a simple icon name, assume dashicons
		if ( preg_match( '/^[a-z\-]+$/', $icon ) ) {
			return sprintf(
				'<span class="%s"><span class="dashicons dashicons-%s"></span></span>',
				esc_attr( $class ),
				esc_attr( $icon )
			);
		}

		// Otherwise, treat as raw HTML/SVG
		return sprintf(
			'<span class="%s">%s</span>',
			esc_attr( $class ),
			$icon
		);
	}

	/**
	 * Get the input type attribute.
	 *
	 * @return string The input type.
	 */
	protected function getInputType(): string {
		return 'text';
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
		return sanitize_text_field( (string) $value );
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

		// Min length validation
		if ( ! empty( $config['minlength'] ) && strlen( (string) $value ) < (int) $config['minlength'] ) {
			$result['errors']['minlength'] = sprintf(
				__( 'This field must be at least %d characters.', 'wp-jarvis' ),
				$config['minlength']
			);
			$result['valid']               = false;
		}

		// Max length validation
		if ( ! empty( $config['maxlength'] ) && strlen( (string) $value ) > (int) $config['maxlength'] ) {
			$result['errors']['maxlength'] = sprintf(
				__( 'This field cannot exceed %d characters.', 'wp-jarvis' ),
				$config['maxlength']
			);
			$result['valid']               = false;
		}

		// Pattern validation
		if ( ! empty( $config['pattern'] ) && ! empty( $value ) ) {
			if ( ! preg_match( '/' . $config['pattern'] . '/', (string) $value ) ) {
				$result['errors']['pattern'] = $config['pattern_message'] ?? __( 'This field does not match the required format.', 'wp-jarvis' );
				$result['valid']             = false;
			}
		}

		return $result;
	}
}
