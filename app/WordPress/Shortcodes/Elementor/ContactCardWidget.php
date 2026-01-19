<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Shortcodes\Elementor;

use BraCalculator\App\WordPress\Shortcodes\ContactCard;
use WPJarvis\Framework\WP\Frontend\Shortcode\Adapters\ElementorWidget;

/**
 * ContactCardWidget - Elementor widget adapter for ContactCard shortcode.
 *
 * Automatically maps shortcode attributes to Elementor controls.
 *
 * @package BraCalculator\\App\WordPress\Shortcodes\Elementor
 */
class ContactCardWidget extends ElementorWidget {
	/**
	 * Get the shortcode class this widget wraps.
	 *
	 * @return class-string<\WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode>
	 */
	protected function getShortcodeClass(): string {
		return ContactCard::class;
	}

	/**
	 * Get a widget name.
	 *
	 * Override if you want a different widget ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'wpjarvis_contact_card';
	}

	/**
	 * Get widget title.
	 *
	 * Override for a custom display title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Contact Card', 'bra-calculator' );
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
		return [ 'contact_card', 'shortcode', 'contact-card' ];
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
