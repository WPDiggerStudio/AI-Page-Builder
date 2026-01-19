<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes\Elementor;

use BraCalculator\App\WordPress\Shortcodes\AlertBox;
use WPJarvis\Framework\WP\Frontend\Shortcode\Adapters\ElementorWidget;

/**
 * AlertBoxWidget - Elementor widget adapter for AlertBox shortcode.
 *
 * Automatically maps shortcode attributes to Elementor controls.
 *
 * @package BraCalculator\\App\WordPress\Shortcodes\Elementor
 */
class AlertBoxWidget extends ElementorWidget {
	/**
	 * Get the shortcode class this widget wraps.
	 *
	 * @return class-string<\WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode>
	 */
	protected function getShortcodeClass(): string {
		return AlertBox::class;
	}

	/**
	 * Get widget name.
	 *
	 * Override if you want a different widget ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'wpjarvis_alert_box';
	}

	/**
	 * Get widget title.
	 *
	 * Override for a custom display title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Alert Box', 'bra-calculator' );
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
		return [ 'alert_box', 'shortcode', 'alert-box' ];
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
