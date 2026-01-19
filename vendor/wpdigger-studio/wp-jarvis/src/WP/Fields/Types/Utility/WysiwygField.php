<?php

declare(strict_types=1);

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * WYSIWYG Field
 *
 * Rich text editor field using WordPress TinyMCE.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class WysiwygField extends AbstractField
{
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string
	{
		return 'wysiwyg';
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
	protected function renderInput(array $config, mixed $value, string $context): string
	{
		$id = $config['id'] ?? '';
		$name = $config['name'] ?? $id;

		// Sanitize editor ID - wp_editor() doesn't allow brackets in IDs since WP 3.9
		$editor_id = str_replace(['[', ']'], '_', $id);

		$settings = [
			'textarea_name' => $name,
			'textarea_rows' => $config['rows'] ?? 10,
			'media_buttons' => $config['media_buttons'] ?? true,
			'teeny' => $config['teeny'] ?? false,
			'quicktags' => $config['quicktags'] ?? true,
			'tinymce' => $config['tinymce'] ?? true,
		];

		// Merge custom settings
		if (!empty($config['editor_settings']) && is_array($config['editor_settings'])) {
			$settings = array_merge($settings, $config['editor_settings']);
		}

		ob_start();
		wp_editor($value ?? '', $editor_id, $settings);

		return ob_get_clean();
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize(mixed $value, array $config): mixed
	{
		return wp_kses_post((string) $value);
	}
}
