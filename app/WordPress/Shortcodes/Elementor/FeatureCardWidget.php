<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes\Elementor;

use BraCalculator\App\WordPress\Shortcodes\FeatureCard;
use WPJarvis\Framework\WP\Frontend\Shortcode\Adapters\ElementorWidget;

/**
 * FeatureCardWidget - Elementor widget adapter for FeatureCard shortcode.
 *
 * Automatically maps shortcode attributes to Elementor controls.
 *
 * @package BraCalculator\App\WordPress\Shortcodes\Elementor
 */
class FeatureCardWidget extends ElementorWidget {
	/**
	 * Get the shortcode class this widget wraps.
	 *
	 * @return class-string<\WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode>
	 */
	protected function getShortcodeClass(): string {
		return FeatureCard::class;
	}

	/**
	 * Get widget name.
	 *
	 * Override if you want a different widget ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'bra-calculator_feature_card';
	}

	/**
	 * Get widget title.
	 *
	 * Override for a custom display title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Feature Card', 'bra-calculator' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Icon class (Elementor icon classes start with 'eicon-').
	 */
	public function get_icon(): string {
		return 'eicon-shortcode';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<string>
	 */
	public function get_categories(): array {
		return [ 'general' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return [ 'feature_card', 'shortcode', 'feature-card' ];
	}

	// To add custom controls beyond the shortcode's attributes,
	// override register_controls():
	//
	// protected function register_controls(): void
	// {
	//     parent::register_controls();
	//
	//     $this->start_controls_section(
	//         'style_section',
	//         [
	//             'label' => __('Style', 'bra-calculator'),
	//             'tab' => \Elementor\Controls_Manager::TAB_STYLE,
	//         ]
	//     );
	//
	//     // Add custom style controls here
	//
	//     $this->end_controls_section();
	// }
}
