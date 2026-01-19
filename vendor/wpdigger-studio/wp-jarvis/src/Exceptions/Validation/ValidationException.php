<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Validation;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * ValidationException - Exception for validation errors.
 */
class ValidationException extends FrameworkException {
	/**
	 * Validation errors.
	 *
	 * @var array<string, array<string>>
	 */
	protected array $errors = [];

	/**
	 * Create a new validation exception.
	 *
	 * @param array<string, array<string>> $errors Validation errors.
	 * @param string $message Custom message.
	 */
	public function __construct( array $errors = [], string $message = '' ) {
		$defaultMessage = __( 'The given data was invalid.', 'wp-jarvis' );
		parent::__construct( $message ?: $defaultMessage, 422 );
		$this->errors = $errors;
	}

	/**
	 * Create from validator errors.
	 *
	 * @param array<string, array<string>> $errors
	 *
	 * @return static
	 */
	public static function withErrors( array $errors ): static {
		return new static( $errors );
	}

	/**
	 * Get the validation errors.
	 *
	 * @return array<string, array<string>>
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * Get the first error for a field.
	 *
	 * @param string $field
	 *
	 * @return string|null
	 */
	public function first( string $field ): ?string {
		return $this->errors[ $field ][0] ?? null;
	}

	/**
	 * Check if a field has errors.
	 *
	 * @param string $field
	 *
	 * @return bool
	 */
	public function has( string $field ): bool {
		return isset( $this->errors[ $field ] ) && ! empty( $this->errors[ $field ] );
	}

	/**
	 * Get all error messages as a flat array.
	 *
	 * @return array<string>
	 */
	public function all(): array {
		$messages = [];
		foreach ( $this->errors as $field => $fieldErrors ) {
			foreach ( $fieldErrors as $error ) {
				$messages[] = $error;
			}
		}

		return $messages;
	}

	/**
	 * Convert to array for JSON response.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'message' => $this->getMessage(),
			'errors'  => $this->errors,
		];
	}
}
