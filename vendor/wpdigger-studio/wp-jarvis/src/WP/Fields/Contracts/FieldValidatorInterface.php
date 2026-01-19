<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Contracts;

/**
 * Field Validator Interface
 *
 * Provides validation, capability checking, nonce verification,
 * and output escaping for field data.
 *
 * @package WPJarvis\Framework\WP\Fields\Contracts
 */
interface FieldValidatorInterface {
	/**
	 * Validate a field value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param array<string, mixed> $config Field configuration.
	 * @param string $context Validation context (metabox, widget, shortcode, settings, block).
	 *
	 * @return array{valid: bool, errors: array<string>} Validation result with valid flag and error messages.
	 */
	public function validate( mixed $value, array $config, string $context ): array;

	/**
	 * Check if a user has required capability.
	 *
	 * @param string $capability Required capability (e.g., 'edit_posts').
	 * @param int|null $objectId Object ID for capability check.
	 *
	 * @return bool True if the user has capability.
	 */
	public function checkCapability( string $capability, ?int $objectId = null ): bool;

	/**
	 * Verify nonce for form submission.
	 *
	 * @param string $nonceAction Nonce action name.
	 * @param string $nonceField Nonce field name.
	 * @param string $nonceValue Nonce value to verify.
	 *
	 * @return bool True if nonce is valid.
	 */
	public function verifyNonce( string $nonceAction, string $nonceField, string $nonceValue ): bool;

	/**
	 * Escape output for safe display.
	 *
	 * @param mixed $value Value to escape.
	 * @param string $context Escaping context (html, attr, url, js, sql).
	 *
	 * @return string Escaped value.
	 */
	public function escape( mixed $value, string $context ): string;
}
