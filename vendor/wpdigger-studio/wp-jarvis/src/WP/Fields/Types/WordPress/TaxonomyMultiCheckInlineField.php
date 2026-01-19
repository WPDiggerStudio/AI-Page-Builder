<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

/**
 * TaxonomyMultiCheckInlineField - Inline taxonomy checkboxes.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class TaxonomyMultiCheckInlineField extends TaxonomyMultiCheckField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'taxonomy_multicheck_inline';
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
		$config['inline'] = true;

		return parent::renderInput( $config, $value, $context );
	}
}
