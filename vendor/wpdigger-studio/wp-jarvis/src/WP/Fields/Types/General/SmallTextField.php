<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * Small Text Field
 *
 * Short text input field for forms (e.g., for numbers, codes, etc.).
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class SmallTextField extends TextField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'small_text';
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

		$attrs['type']  = 'text';
		$attrs['value'] = $value ?? '';
		$attrs['class'] = $config['class'] ?? 'small-text';

		return sprintf( '<input %s />', $this->buildAttributesString( $attrs ) );
	}
}
