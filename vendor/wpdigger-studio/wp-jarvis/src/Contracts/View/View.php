<?php
declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\View;

/**
 * View Interface
 */
interface View {
	/**
	 * Get the name of the view.
	 */
	public function name(): string;

	/**
	 * Add a piece of data to the view.
	 *
	 * @param string|array $key
	 * @param mixed $value
	 *
	 * @return static
	 */
	public function with( string|array $key, mixed $value = null ): static;

	/**
	 * Get the string contents of the view.
	 */
	public function render(): string;
}