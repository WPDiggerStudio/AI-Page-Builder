<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * RadioInlineField - Inline radio buttons.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class RadioInlineField extends RadioField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'radio_inline';
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
		// Force inline layout for options
		$config['inline'] = true;

		return parent::renderInput( $config, $value, $context );
	}
}
