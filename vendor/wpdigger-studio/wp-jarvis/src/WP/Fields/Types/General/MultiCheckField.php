<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\General;

/**
 * MultiCheckField - Multiple checkboxes field.
 *
 * Alias for CheckboxField with options - for CMB2 naming compatibility.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\General
 */
class MultiCheckField extends CheckboxField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'multicheck';
	}
}
