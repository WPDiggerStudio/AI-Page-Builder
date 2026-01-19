<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * MediumTextField - Medium width text field.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class MediumTextField extends TextField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'text_medium';
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
		// Force medium size
		$config['size'] = 'medium';

		return parent::renderInput( $config, $value, $context );
	}
}
