<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Validation;

/**
 * Rule Interface
 *
 * Defines the contract for custom validation rules.
 */
interface Rule {
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The attribute value.
	 *
	 * @return bool
	 */
	public function passes( string $attribute, mixed $value ): bool;

	/**
	 * Get the validation error message.
	 *
	 * @return string
	 */
	public function message(): string;
}
