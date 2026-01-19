<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes\WPBakery;

use BraCalculator\App\WordPress\Shortcodes\TestimonialCard;
use WPJarvis\Framework\WP\Frontend\Shortcode\Adapters\WPBakeryElement;

/**
 * TestimonialCardElement - WPBakery/Visual Composer element adapter for TestimonialCard shortcode.
 *
 * Automatically generates vc_map() params from shortcode attribute schema.
 *
 * @package BraCalculator\\App\WordPress\Shortcodes\WPBakery
 */
class TestimonialCardElement extends WPBakeryElement {
	/**
	 * Get the shortcode class this element wraps.
	 *
	 * @return class-string<\WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode>
	 */
	protected function getShortcodeClass(): string {
		return TestimonialCard::class;
	}

	// To customize the vc_map configuration, you can override these methods:

	/**
	 * Map category to WPBakery category.
	 *
	 * @param string $category Source category.
	 *
	 * @return string WPBakery category.
	 */
	// protected function mapCategory(string $category): string
	// {
	//     return __('My Custom Category', 'bra-calculator');
	// }

	/**
	 * Get icon URL or class.
	 *
	 * @param string $icon Icon name.
	 *
	 * @return string
	 */
	// protected function getIconUrl(string $icon): string
	// {
	//     return plugin_dir_url(__FILE__) . '../../../resources/images/my-icon.png';
	// }

	// To add custom params beyond the shortcode's attributes,
	// override buildParams():
	//
	// protected function buildParams(): array
	// {
	//     $params = parent::buildParams();
	//
	//     // Add custom params
	//     $params[] = [
	//         'type' => 'css_editor',
	//         'heading' => __('CSS Box', 'bra-calculator'),
	//         'param_name' => 'css',
	//         'group' => __('Design Options', 'bra-calculator'),
	//     ];
	//
	//     return $params;
	// }
}
