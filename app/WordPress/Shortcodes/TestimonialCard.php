<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes;

use WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode;

/**
 * TestimonialCard Shortcode
 *
 * Provides [testimonial_card] shortcode functionality.
 *
 * Usage:
 *   [testimonial_card title="Hello World"]
 *   [testimonial_card]Your content here[/testimonial_card]
 *
 * @package BraCalculator\App\WordPress\Shortcodes
 */
class TestimonialCard extends AbstractShortcode {
	/**
	 * The shortcode tag.
	 */
	protected static string $tag = 'testimonial_card';

	/**
	 * Whether shortcode allows content between opening/closing tags.
	 */
	protected bool $allowContent = true;

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
				'type'    => 'textarea',
				'id'      => 'quote',
				'label'   => __( 'Testimonial Quote', '' ),
				'default' => '',
			],
			[
				'type'    => 'text',
				'id'      => 'author',
				'label'   => __( 'Author Name', '' ),
				'default' => '',
			],
			[
				'type'    => 'text',
				'id'      => 'position',
				'label'   => __( 'Author Position', '' ),
				'default' => '',
			],
			[
				'type'    => 'image',
				'id'      => 'photo',
				'label'   => __( 'Author Photo', '' ),
				'default' => '',
			],
			[
				'type'    => 'number',
				'id'      => 'rating',
				'label'   => __( 'Star Rating', '' ),
				'default' => 5,
			],
			[
				'type'    => 'url',
				'id'      => 'company_url',
				'label'   => __( 'Company Website', '' ),
				'default' => '',
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
		$classes = [ 'testimonial_card-shortcode' ];
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
			$output .= '<h3 class="testimonial_card-title">' . esc_html( $atts['title'] ) . '</h3>';
		}

		// Content (if allowed and provided)
		if ( $content !== null ) {
			$output .= '<div class="testimonial_card-content">';
			$output .= $content;
			$output .= '</div>';
		}

		// TODO: Add your custom rendering logic here

		$output .= '</div>';

		return $output;

		// Alternative: Use a template file
		// return $this->view('testimonial_card', [
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
		//     'testimonial_card-style',
		//     plugin_dir_url(__FILE__) . '../../../resources/css/testimonial_card.css',
		//     [],
		//     '1.0.0'
		// );
		//
		// wp_enqueue_script(
		//     'testimonial_card-script',
		//     plugin_dir_url(__FILE__) . '../../../resources/js/testimonial_card.js',
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
			'title'       => __( 'Testimonial Card', 'bra-calculator' ),
			'description' => __( 'Renders the [testimonial_card] shortcode.', 'bra-calculator' ),
			'icon'        => 'shortcode',
			'category'    => 'general',
			'keywords'    => [ 'testimonial_card', 'shortcode' ],
		];
	}
}
