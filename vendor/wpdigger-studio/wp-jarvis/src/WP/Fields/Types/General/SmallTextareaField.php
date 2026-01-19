<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * SmallTextareaField - Small textarea field.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class SmallTextareaField extends TextareaField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'textarea_small';
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
		// Force smaller rows
		$config['rows'] = $config['rows'] ?? 3;
		$config['size'] = $config['size'] ?? 'small';

		return parent::renderInput( $config, $value, $context );
	}
}
