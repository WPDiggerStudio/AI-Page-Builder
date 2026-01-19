<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * UrlField - URL input field.
 *
 * Extends TextField with URL validation and default link icon.
 * Supports 'protocols' option to restrict allowed URL protocols.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class UrlField extends TextField {

	/**
	 * Default allowed protocols.
	 *
	 * @var array<string>
	 */
	protected const DEFAULT_PROTOCOLS = [ 'http', 'https' ];

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'url';
	}

	/**
	 * Get the input type attribute.
	 *
	 * @return string The input type.
	 */
	protected function getInputType(): string {
		return 'url';
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
		// Default to link icon if no icon specified
		if ( empty( $config['icon_left'] ) && empty( $config['icon'] ) && empty( $config['no_icon'] ) ) {
			$config['icon_left'] = 'admin-links';
		}

		return parent::renderInput( $config, $value, $context );
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
		$protocols = $config['protocols'] ?? self::DEFAULT_PROTOCOLS;

		return esc_url_raw( (string) $value, $protocols );
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

		if ( ! empty( $value ) ) {
			$url = (string) $value;

			// Basic URL structure validation
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$result['errors']['url'] = __( 'Please enter a valid URL.', 'wp-jarvis' );
				$result['valid']         = false;
			} else {
				// Protocol validation
				$protocols = $config['protocols'] ?? self::DEFAULT_PROTOCOLS;
				$parsed    = wp_parse_url( $url );
				$scheme    = $parsed['scheme'] ?? '';

				if ( ! empty( $scheme ) && ! in_array( strtolower( $scheme ), $protocols, true ) ) {
					$result['errors']['protocol'] = sprintf(
						__( 'URL must use one of the following protocols: %s', 'wp-jarvis' ),
						implode( ', ', $protocols )
					);
					$result['valid']              = false;
				}
			}
		}

		return $result;
	}
}
