<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes;

use WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode;

/**
 * AlertBox Shortcode
 *
 * Provides [alert_box] shortcode functionality.
 *
 * Usage:
 *   [alert_box title="Hello World"]
 *   or [alert_box title="Example" /]
 *
 * @package BraCalculator\App\WordPress\Shortcodes
 */
class AlertBox extends AbstractShortcode {
	/**
	 * The shortcode tag.
	 */
	protected static string $tag = 'alert_box';

	/**
	 * Whether shortcode allows content between opening/closing tags.
	 */
	protected bool $allowContent = false;

	/**
	 * Define shortcode fields.
	 *
	 * Uses the same structure as Metabox::fields() for consistency.
	 * Each field: type, id, label, and optional description/options.
	 *
	 * Supported types:
	 * - text: Basic text input
	 * - textarea: Multi-line text
	 * - wysiwyg: HTML content (WYSIWYG editor)
	 * - number: Numeric input with optional min/max
	 * - checkbox: True/false toggle
	 * - select: Dropdown with predefined options
	 * - color: Hex color picker
	 * - image: Media library selection
	 * - url: URL input
	 * - email: Email input
	 *
	 * @return array<int, array<string, mixed>> Array of field definitions.
	 */
	protected function fields(): array {
		return [
			[
				'type'        => 'text',
				'id'          => 'id',
				'label'       => __( 'CSS ID', 'bra-calculator' ),
				'description' => __( 'CSS ID for the wrapper element.', 'bra-calculator' ),
			],
			[
				'type'        => 'text',
				'id'          => 'class',
				'label'       => __( 'CSS Classes', 'bra-calculator' ),
				'description' => __( 'Additional CSS classes.', 'bra-calculator' ),
			],
			[
				'type'    => 'text',
				'id'      => 'title',
				'label'   => __( 'Alert Title', '' ),
				'default' => '',
			],
			[
				'type'    => 'textarea',
				'id'      => 'message',
				'label'   => __( 'Alert Message', '' ),
				'default' => '',
			],
			[
				'type'    => 'select',
				'id'      => 'type',
				'label'   => __( 'Alert Type', '' ),
				'default' => 'info',
			],
			[
				'type'    => 'checkbox',
				'id'      => 'dismissible',
				'label'   => __( 'Dismissible', '' ),
				'default' => 'false',
			],
			[
				'type'    => 'color_picker',
				'id'      => 'bg_color',
				'label'   => __( 'Background Color', '' ),
				'default' => '#f0f0f0',
			],
		];
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, mixed> $atts Sanitized attributes.
	 * @param string|null $content Processed content (if allowed).
	 *
	 * @return string Rendered HTML.
	 */
	public function render( array $atts, ?string $content = null ): string {
		// Build CSS classes
		$classes = [ 'alert_box-shortcode' ];
		if ( ! empty( $atts['class'] ) ) {
			$classes[] = esc_attr( $atts['class'] );
		}

		// Build wrapper attributes
		$wrapperAtts = [
			'class' => implode( ' ', $classes ),
		];
		if ( ! empty( $atts['id'] ) ) {
			$wrapperAtts['id'] = esc_attr( $atts['id'] );
		}

		// Build attribute string
		$attrString = '';
		foreach ( $wrapperAtts as $key => $value ) {
			$attrString .= sprintf( ' %s="%s"', $key, $value );
		}

		// Start output
		$output = '<div' . $attrString . '>';

		// Title
		if ( ! empty( $atts['title'] ) ) {
			$output .= '<h3 class="alert_box-title">' . esc_html( $atts['title'] ) . '</h3>';
		}

		// Content (if allowed and provided)
		if ( $content !== null ) {
			$output .= '<div class="alert_box-content">';
			$output .= $content;
			$output .= '</div>';
		}

		// TODO: Add your custom rendering logic here

		$output .= '</div>';

		return $output;

		// Alternative: Use a template file
		// return $this->view('alert_box', [
		//     'atts' => $atts,
		//     'content' => $content,
		// ]);
	}

	/**
	 * Enqueue assets for this shortcode.
	 *
	 * Called only when the shortcode is actually used on the page.
	 *
	 * @return void
	 */
	public function enqueueAssets(): void {
		// Uncomment and modify as needed:
		// wp_enqueue_style(
		//     'alert_box-style',
		//     plugin_dir_url(__FILE__) . '../../../resources/css/alert_box.css',
		//     [],
		//     '1.0.0'
		// );
		//
		// wp_enqueue_script(
		//     'alert_box-script',
		//     plugin_dir_url(__FILE__) . '../../../resources/js/alert_box.js',
		//     ['jquery'],
		//     '1.0.0',
		//     true
		// );
	}

	/**
	 * Get metadata for adapters (Elementor, WPBakery, TinyMCE).
	 *
	 * @return array<string, mixed>
	 */
	public function getMetadata(): array {
		return [
			'tag'         => static::$tag,
			'title'       => __( 'Alert Box', 'bra-calculator' ),
			'description' => __( 'Renders the [alert_box] shortcode.', 'bra-calculator' ),
			'icon'        => 'shortcode',
			'category'    => 'general',
			'keywords'    => [ 'alert_box', 'shortcode' ],
		];
	}
}
