<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * EmailField - Email input field.
 *
 * Extends TextField with email validation and the default email icon.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class EmailField extends TextField {

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'email';
	}

	/**
	 * Get the input type attribute.
	 *
	 * @return string The input type.
	 */
	protected function getInputType(): string {
		return 'email';
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
		// Default to email icon if no icon specified
		if ( empty( $config['icon_left'] ) && empty( $config['icon'] ) && empty( $config['no_icon'] ) ) {
			$config['icon_left'] = 'email-alt';
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
	public function sanitize( mixed $value, array $config ): string {
		return sanitize_email( (string) $value );
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
			$email = (string) $value;

			// Use WordPress is_email() function for validation
			if ( ! is_email( $email ) ) {
				$result['errors']['email'] = __( 'Please enter a valid email address.', 'wp-jarvis' );
				$result['valid']           = false;
			}
		}

		return $result;
	}
}
