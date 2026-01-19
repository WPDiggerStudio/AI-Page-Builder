<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend\Shortcode\Adapters;

use WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode;

/**
 * WPBakeryElement - Base element adapter for WPBakery/Visual Composer.
 *
 * Wraps any AbstractShortcode as a WPBakery element by automatically
 * generating vc_map() params from the shortcode's field definitions.
 *
 * Usage in generated element:
 * class MyShortcodeElement extends WPBakeryElement {
 *     protected function getShortcodeClass(): string {
 *         return MyShortcode::class;
 *     }
 * }
 *
 * @package WPJarvis\Framework\WP\Frontend\Shortcode\Adapters
 */
abstract class WPBakeryElement {
	/**
	 * Shortcode instance cache.
	 */
	private ?AbstractShortcode $shortcodeInstance = null;

	/**
	 * Whether the element is registered.
	 */
	private bool $registered = false;

	/**
	 * Get the shortcode class to wrap.
	 *
	 * @return class-string<AbstractShortcode>
	 */
	abstract protected function getShortcodeClass(): string;

	/**
	 * Get the shortcode instance.
	 *
	 * @return AbstractShortcode
	 */
	protected function getShortcode(): AbstractShortcode {
		if ( $this->shortcodeInstance === null ) {
			$class                   = $this->getShortcodeClass();
			$this->shortcodeInstance = new $class();
		}

		return $this->shortcodeInstance;
	}

	/**
	 * Register the WPBakery element.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		// WPBakery must be active
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}

		$shortcode = $this->getShortcode();
		$metadata  = $shortcode->getMetadata();

		// Build vc_map configuration
		$config = [
			'name'        => $metadata['title'] ?? $shortcode::getTag(),
			'base'        => $shortcode::getTag(),
			'description' => $metadata['description'] ?? '',
			'category'    => $this->mapCategory( $metadata['category'] ?? 'general' ),
			'icon'        => $this->getIconUrl( $metadata['icon'] ?? 'shortcode' ),
			'params'      => $this->buildParams(),
		];

		// Add content param if shortcode allows content
		if ( $shortcode->getAllowContent() ) {
			$config['params'][] = [
				'type'       => 'textarea_html',
				'holder'     => 'div',
				'heading'    => __( 'Content', 'wp-jarvis' ),
				'param_name' => 'content',
				'value'      => '',
			];
		}

		vc_map( $config );

		$this->registered = true;
	}

	/**
	 * Build params array from field definitions.
	 *
	 * Uses getFields() which returns a Metabox-compatible format.
	 * Falls back to getAttributeSchema() for backward compatibility.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function buildParams(): array {
		$shortcode = $this->getShortcode();
		$fields    = $shortcode->getFields();
		$params    = [];

		foreach ( $fields as $field ) {
			$param = $this->mapFieldToParam( $field );
			if ( $param !== null ) {
				$params[] = $param;
			}
		}

		return $params;
	}

	/**
	 * Map a field definition to WPBakery param config.
	 *
	 * @param array<string, mixed> $field Field definition.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function mapFieldToParam( array $field ): ?array {
		$type = $field['type'] ?? 'text';
		$id   = $field['id'] ?? '';

		if ( empty( $id ) ) {
			return null;
		}

		// Skip utility-only fields
		if ( $type === 'group' ) {
			return null;
		}

		// Get WPBakery param type from a field type
		$paramType = $this->mapFieldTypeToParamType( $type, $field );

		$param = [
			'type'       => $paramType,
			'heading'    => $field['label'] ?? ucfirst( str_replace( '_', ' ', $id ) ),
			'param_name' => $id,
		];

		// Default value
		if ( isset( $field['default'] ) && ! is_array( $field['default'] ) ) {
			$param['value'] = $field['default'];
		}

		// Description
		if ( isset( $field['desc'] ) ) {
			$param['description'] = $field['desc'];
		}

		// Placeholder as a description hint
		if ( isset( $field['placeholder'] ) && ! isset( $param['description'] ) ) {
			$param['description'] = sprintf( __( 'e.g., %s', 'wp-jarvis' ), $field['placeholder'] );
		}

		// Handle options for dropdowns
		if ( isset( $field['options'] ) && in_array( $paramType, [ 'dropdown', 'checkbox' ], true ) ) {
			$param['value'] = $this->formatOptionsForWPBakery( $field['options'], $paramType );
		}

		// Admin label for required fields
		if ( $field['required'] ?? false ) {
			$param['admin_label'] = true;
		}

		// Group support
		if ( isset( $field['group'] ) ) {
			$param['group'] = $field['group'];
		}

		// Dependency support
		if ( isset( $field['dependency'] ) ) {
			$param['dependency'] = $field['dependency'];
		}

		return $param;
	}

	/**
	 * Map WP Jarvis field type to WPBakery param type.
	 *
	 * Comprehensive mapping of all registered field types.
	 *
	 * @param string $type Field type.
	 * @param array<string, mixed> $field Full field configuration.
	 *
	 * @return string WPBakery param type.
	 */
	protected function mapFieldTypeToParamType( string $type, array $field = [] ): string {
		return match ( $type ) {
			// ──────────────────────────────────────────────────────────────
			// General Text Fields
			// ──────────────────────────────────────────────────────────────
			'text', 'small_text', 'text_small', 'text_medium', 'text_money' => 'textfield',
			'email', 'text_email' => 'textfield',
			'url', 'text_url' => 'vc_link',
			'hidden' => 'hidden',

			// ──────────────────────────────────────────────────────────────
			// Textarea Fields
			// ──────────────────────────────────────────────────────────────
			'textarea', 'textarea_small' => 'textarea',
			'textarea_code', 'code' => 'textarea_raw_html',
			'wysiwyg' => 'textarea_html',
			'html' => 'textarea_html',

			// ──────────────────────────────────────────────────────────────
			// Number Field
			// ──────────────────────────────────────────────────────────────
			'number', 'integer', 'float' => 'textfield',

			// ──────────────────────────────────────────────────────────────
			// Select/Choice Fields
			// ──────────────────────────────────────────────────────────────
			'select', 'radio', 'radio_inline' => 'dropdown',
			'multi_select' => 'dropdown',
			'checkbox' => 'checkbox',
			'multicheck', 'multicheck_inline' => 'checkbox',

			// ──────────────────────────────────────────────────────────────
			// Date/Time Fields
			// ──────────────────────────────────────────────────────────────
			'date', 'text_date' => 'textfield',
			'time', 'text_time' => 'textfield',

			// ──────────────────────────────────────────────────────────────
			// WordPress Taxonomy Fields
			// ──────────────────────────────────────────────────────────────
			'taxonomy_select', 'taxonomy_radio', 'taxonomy_radio_inline' => 'dropdown',
			'taxonomy_multicheck', 'taxonomy_multicheck_inline' => 'checkbox',

			// ──────────────────────────────────────────────────────────────
			// WordPress Post Select Field
			// ──────────────────────────────────────────────────────────────
			'post_select' => 'dropdown',

			// ──────────────────────────────────────────────────────────────
			// Media/File Upload Fields
			// ──────────────────────────────────────────────────────────────
			'media_upload', 'file', 'file_upload' => 'attach_image',
			'image_upload' => 'attach_image',
			'file_list' => 'attach_images',

			// ──────────────────────────────────────────────────────────────
			// Utility Fields
			// ──────────────────────────────────────────────────────────────
			'colorpicker', 'color_picker', 'color' => 'colorpicker',
			'title' => 'textfield',

			// ──────────────────────────────────────────────────────────────
			// Country & Timezone Fields
			// ──────────────────────────────────────────────────────────────
			'country', 'select_timezone', 'timezone' => 'dropdown',

			// ──────────────────────────────────────────────────────────────
			// Location Field
			// ──────────────────────────────────────────────────────────────
			'location' => 'textfield',

			// ──────────────────────────────────────────────────────────────
			// oEmbed Field
			// ──────────────────────────────────────────────────────────────
			'oembed' => 'textfield',

			// ──────────────────────────────────────────────────────────────
			// Legacy types (boolean)
			// ──────────────────────────────────────────────────────────────
			'boolean', 'bool' => 'checkbox',

			// Default
			default => 'textfield',
		};
	}

	/**
	 * Format options array for WPBakery.
	 *
	 * WPBakery uses a label => value format (inverted from normal).
	 *
	 * @param array $options Original options.
	 * @param string $paramType Param type (dropdown or checkbox).
	 *
	 * @return array<string, string>
	 */
	protected function formatOptionsForWPBakery( array $options, string $paramType ): array {
		$formatted = [];

		foreach ( $options as $key => $value ) {
			// If it's an indexed array, use value for both
			if ( is_int( $key ) ) {
				$formatted[ (string) $value ] = (string) $value;
			} else {
				// WPBakery uses label => value (inverted)
				$formatted[ (string) $value ] = (string) $key;
			}
		}

		return $formatted;
	}

	/**
	 * Map category to WPBakery category.
	 *
	 * @param string $category Source category.
	 *
	 * @return string WPBakery category.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function mapCategory( string $category ): string {
		$default = wpj_config( 'app.name', 'WP Jarvis' );

		return match ( $category ) {
			'content' => __( 'Content', 'wp-jarvis' ),
			'media' => __( 'Media', 'wp-jarvis' ),
			'social' => __( 'Social', 'wp-jarvis' ),
			default => $default,
		};
	}

	/**
	 * Get icon URL or class.
	 *
	 * @param string $icon Icon name.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getIconUrl( string $icon ): string {
		// Check for a custom icon file
		if ( function_exists( 'wpj_app' ) ) {
			$customPath = wpj_app()->basePath( 'resources/images/vc-' . $icon . '.png' );
			if ( file_exists( $customPath ) ) {
				return wpj_app()->baseUrl( 'resources/images/vc-' . $icon . '.png' );
			}
		}

		// Use dashicon class
		return 'dashicons-' . $icon;
	}

	/**
	 * Check if an element is registered.
	 *
	 * @return bool
	 */
	public function isRegistered(): bool {
		return $this->registered;
	}

	/**
	 * Get element base (shortcode tag).
	 *
	 * @return string
	 */
	public function getBase(): string {
		return $this->getShortcode()::getTag();
	}
}
