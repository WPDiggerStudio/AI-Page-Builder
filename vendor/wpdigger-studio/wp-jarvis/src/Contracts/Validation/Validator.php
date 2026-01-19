<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Validation;

/**
 * Validator Interface
 *
 * Defines the contract for validation.
 */
interface Validator {
	/**
	 * Run the validator's rules against its data.
	 *
	 * @return array<string, mixed> Validated data
	 * @throws \WPJarvis\Framework\Exceptions\Validation\ValidationException
	 */
	public function validate(): array;

	/**
	 * Determine if the data fails the validation rules.
	 */
	public function fails(): bool;

	/**
	 * Determine if the data passes the validation rules.
	 */
	public function passes(): bool;

	/**
	 * Get the failed validation rules.
	 *
	 * @return array<string, array<string>>
	 */
	public function failed(): array;

	/**
	 * Get the error messages.
	 *
	 * @return array<string, array<string>>
	 */
	public function errors(): array;

	/**
	 * Get the validated data.
	 *
	 * @return array<string, mixed>
	 */
	public function validated(): array;

	/**
	 * After validation callback.
	 *
	 * @param callable $callback
	 *
	 * @return static
	 */
	public function after( callable $callback ): static;

	/**
	 * Set custom error messages.
	 *
	 * @param array<string, string> $messages
	 *
	 * @return static
	 */
	public function setCustomMessages( array $messages ): static;

	/**
	 * Set custom attribute names.
	 *
	 * @param array<string, string> $attributes
	 *
	 * @return static
	 */
	public function setAttributeNames( array $attributes ): static;
}
