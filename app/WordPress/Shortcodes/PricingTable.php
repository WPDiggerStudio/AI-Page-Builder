<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes;

use WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode;

/**
 * PricingTable Shortcode
 *
 * Provides [pricing_table] shortcode functionality.
 *
 * Usage:
 *   [pricing_table title="Hello World"]
 *   or [pricing_table title="Example" /]
 *
 * @package BraCalculator\App\WordPress\Shortcodes
 */
class PricingTable extends AbstractShortcode {
	/**
	 * The shortcode tag.
	 */
	protected static string $tag = 'pricing_table';

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
				'label'   => __( 'Plan Name', '' ),
				'default' => '',
			],
			[
				'type'    => 'number',
				'id'      => 'price',
				'label'   => __( 'Price', '' ),
				'default' => 0,
			],
			[
				'type'    => 'text',
				'id'      => 'currency',
				'label'   => __( 'Currency', '' ),
				'default' => 'Symbol',
			],
			[
				'type'    => 'textarea',
				'id'      => 'features',
				'label'   => __( 'Features List', '' ),
				'default' => '',
			],
			[
				'type'    => 'url',
				'id'      => 'cta_link',
				'label'   => __( 'CTA Button Link', '' ),
				'default' => '',
			],
			[
				'type'    => 'checkbox',
				'id'      => 'featured',
				'label'   => __( 'Featured Plan', '' ),
				'default' => 'false',
			],
			[
				'type'    => 'color',
				'id'      => 'accent_color',
				'label'   => __( 'Accent Color', '' ),
				'default' => '#0073aa',
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
		$classes = [ 'pricing_table-shortcode' ];
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
			$output .= '<h3 class="pricing_table-title">' . esc_html( $atts['title'] ) . '</h3>';
		}

		// Content (if allowed and provided)
		if ( $content !== null ) {
			$output .= '<div class="pricing_table-content">';
			$output .= $content;
			$output .= '</div>';
		}

		// TODO: Add your custom rendering logic here

		$output .= '</div>';

		return $output;

		// Alternative: Use a template file
		// return $this->view('pricing_table', [
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
		//     'pricing_table-style',
		//     plugin_dir_url(__FILE__) . '../../../resources/css/pricing_table.css',
		//     [],
		//     '1.0.0'
		// );
		//
		// wp_enqueue_script(
		//     'pricing_table-script',
		//     plugin_dir_url(__FILE__) . '../../../resources/js/pricing_table.js',
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
			'title'       => __( 'Pricing Table', 'bra-calculator' ),
			'description' => __( 'Renders the [pricing_table] shortcode.', 'bra-calculator' ),
			'icon'        => 'shortcode',
			'category'    => 'general',
			'keywords'    => [ 'pricing_table', 'shortcode' ],
		];
	}
}
