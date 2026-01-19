<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend\Shortcode\Adapters;

use WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode;

/**
 * ElementorWidget - Base widget adapter for Elementor.
 *
 * Wraps any AbstractShortcode as an Elementor widget by automatically
 * mapping the shortcode's field definitions to Elementor controls.
 *
 * Usage in the generated widget:
 * class MyShortcodeWidget extends ElementorWidget {
 *     protected function getShortcodeClass(): string {
 *         return MyShortcode::class;
 *     }
 * }
 *
 * @package WPJarvis\Framework\WP\Frontend\Shortcode\Adapters
 */
abstract class ElementorWidget extends \Elementor\Widget_Base {
	/**
	 * Shortcode instance cache.
	 */
	private ?AbstractShortcode $shortcodeInstance = null;

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
	 * Get a widget name.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function get_name(): string {
		return wpj_config( 'app.slug' ) . $this->getShortcode()::getTag();
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function get_title(): string {
		$metadata = $this->getShortcode()->getMetadata();

		return $metadata['title'] ?? $this->get_name();
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		$metadata = $this->getShortcode()->getMetadata();
		$icon     = $metadata['icon'] ?? 'shortcode';

		// Map common names to Elementor icon classes
		return match ( $icon ) {
			'shortcode' => 'eicon-shortcode',
			'code' => 'eicon-code',
			'text' => 'eicon-text',
			'image' => 'eicon-image',
			'gallery' => 'eicon-gallery-grid',
			'video' => 'eicon-video-camera',
			'button' => 'eicon-button',
			'form' => 'eicon-form-horizontal',
			'star' => 'eicon-star',
			default => 'eicon-' . $icon,
		};
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function get_categories(): array {
		$metadata = $this->getShortcode()->getMetadata();
		$category = $metadata['category'] ?? 'general';
		$default  = wpj_config( 'app.slug', 'wp-jarvis' );

		return [ $category === 'general' ? 'general' : $default ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<string>
	 */
	public function get_keywords(): array {
		$metadata = $this->getShortcode()->getMetadata();

		return $metadata['keywords'] ?? [ $this->getShortcode()::getTag() ];
	}

	/**
	 * Register controls.
	 *
	 * Uses getFields() from the shortcode which returns Metabox-compatible
	 * field definitions. Falls back to getAttributeSchema() for backwards compatibility.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$shortcode = $this->getShortcode();
		$fields    = $shortcode->getFields();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'wp-jarvis' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		foreach ( $fields as $field ) {
			$this->addControlFromField( $field );
		}

		// Add content control if shortcode allows content
		if ( $shortcode->getAllowContent() ) {
			$this->add_control(
				'shortcode_content',
				[
					'label'   => __( 'Content', 'wp-jarvis' ),
					'type'    => \Elementor\Controls_Manager::WYSIWYG,
					'default' => '',
				]
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Add a control from the field definition (Metabox-compatible format).
	 *
	 * @param array<string, mixed> $field Field configuration.
	 *
	 * @return void
	 */
	protected function addControlFromField( array $field ): void {
		$type = $field['type'] ?? 'text';
		$id   = $field['id'] ?? '';

		if ( empty( $id ) ) {
			return;
		}

		// Skip utility-only fields that don't render inputs
		if ( $type === 'group' ) {
			return;
		}

		// For title/heading fields, use HEADING control
		if ( $type === 'title' ) {
			$this->add_control(
				$id,
				[
					'label' => $field['label'] ?? '',
					'type'  => \Elementor\Controls_Manager::HEADING,
				]
			);

			return;
		}

		$controlConfig = $this->mapFieldTypeToControl( $type, $field );

		// Set label
		$controlConfig['label'] = $field['label'] ?? ucfirst( str_replace( '_', ' ', $id ) );

		// Set default if provided - but only for control types that don't require array defaults
		// URL, MEDIA, and GALLERY controls have array defaults that must not be overwritten with strings
		$controlsWithArrayDefaults = [
			\Elementor\Controls_Manager::URL,
			\Elementor\Controls_Manager::MEDIA,
			\Elementor\Controls_Manager::GALLERY,
		];

		if ( isset( $field['default'] ) ) {
			// For controls that require array defaults, only override if field default is also an array
			if ( in_array( $controlConfig['type'], $controlsWithArrayDefaults, true ) ) {
				if ( is_array( $field['default'] ) ) {
					$controlConfig['default'] = array_merge( $controlConfig['default'] ?? [], $field['default'] );
				}
				// If field default is a string for these types, we keep the array default from mapFieldTypeToControl
			} else {
				$controlConfig['default'] = $field['default'];
			}
		}

		// Set description if provided
		if ( isset( $field['desc'] ) ) {
			$controlConfig['description'] = $field['desc'];
		}

		// Add a placeholder if applicable
		if (
			isset( $field['placeholder'] ) && in_array( $controlConfig['type'], [
				\Elementor\Controls_Manager::TEXT,
				\Elementor\Controls_Manager::TEXTAREA,
				\Elementor\Controls_Manager::NUMBER,
			], true )
		) {
			$controlConfig['placeholder'] = $field['placeholder'];
		}

		// Add condition support if defined
		if ( isset( $field['condition'] ) ) {
			$controlConfig['condition'] = $field['condition'];
		}

		$this->add_control( $id, $controlConfig );
	}

	/**
	 * Map WP Jarvis field type to Elementor control configuration.
	 *
	 * Comprehensive mapping of all registered field types from FieldServiceProvider
	 * to appropriate Elementor controls.
	 *
	 * @param string $type Field type from the field system.
	 * @param array<string, mixed> $field Full field configuration.
	 *
	 * @return array<string, mixed> Control configuration.
	 */
	protected function mapFieldTypeToControl( string $type, array $field ): array {
		return match ( $type ) {
			// ──────────────────────────────────────────────────────────────
			// General Text Fields
			// ──────────────────────────────────────────────────────────────
			'text', 'small_text', 'text_small', 'text_medium' => [
				'type' => \Elementor\Controls_Manager::TEXT,
			],

			'text_money' => [
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => $field['desc'] ?? __( 'Enter monetary value', 'wp-jarvis' ),
			],

			// ──────────────────────────────────────────────────────────────
			// Textarea Fields
			// ──────────────────────────────────────────────────────────────
			'textarea', 'textarea_small' => [
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'rows' => $field['rows'] ?? 4,
			],

			'textarea_code', 'code' => [
				'type'     => \Elementor\Controls_Manager::CODE,
				'language' => $field['language'] ?? 'html',
			],

			// ──────────────────────────────────────────────────────────────
			// Number Field
			// ──────────────────────────────────────────────────────────────
			'number' => [
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min'  => $field['min'] ?? null,
				'max'  => $field['max'] ?? null,
				'step' => $field['step'] ?? 1,
			],

			// ──────────────────────────────────────────────────────────────
			// URL Fields
			// ──────────────────────────────────────────────────────────────
			'url', 'text_url' => [
				'type'          => \Elementor\Controls_Manager::URL,
				'placeholder'   => $field['placeholder'] ?? __( 'https://example.com', 'wp-jarvis' ),
				'show_external' => true,
				'default'       => [
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				],
			],

			// ──────────────────────────────────────────────────────────────
			// Email Fields
			// ──────────────────────────────────────────────────────────────
			'email', 'text_email' => [
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'email',
				'placeholder' => $field['placeholder'] ?? __( 'email@example.com', 'wp-jarvis' ),
			],

			// ──────────────────────────────────────────────────────────────
			// Hidden Field
			// ──────────────────────────────────────────────────────────────
			'hidden' => [
				'type' => \Elementor\Controls_Manager::HIDDEN,
			],

			// ──────────────────────────────────────────────────────────────
			// Select Fields
			// ──────────────────────────────────────────────────────────────
			'select' => [
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->normalizeOptions( $field['options'] ?? [] ),
			],

			'multi_select' => [
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $this->normalizeOptions( $field['options'] ?? [] ),
				'multiple' => true,
			],

			// ──────────────────────────────────────────────────────────────
			// Checkbox/Boolean Fields
			// ──────────────────────────────────────────────────────────────
			'checkbox' => [
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'wp-jarvis' ),
				'label_off'    => __( 'No', 'wp-jarvis' ),
				'return_value' => '1',
			],

			'multicheck', 'multicheck_inline' => [
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $this->normalizeOptions( $field['options'] ?? [] ),
				'multiple' => true,
			],

			// ──────────────────────────────────────────────────────────────
			// Radio Fields (map to select in Elementor)
			// ──────────────────────────────────────────────────────────────
			'radio', 'radio_inline' => [
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->normalizeOptions( $field['options'] ?? [] ),
			],

			// ──────────────────────────────────────────────────────────────
			// Date/Time Fields
			// ──────────────────────────────────────────────────────────────
			'date', 'text_date' => [
				'type'           => \Elementor\Controls_Manager::DATE_TIME,
				'picker_options' => [
					'enableTime' => false,
				],
			],

			'time', 'text_time' => [
				'type'           => \Elementor\Controls_Manager::DATE_TIME,
				'picker_options' => [
					'enableTime' => true,
					'noCalendar' => true,
					'time_24hr'  => true,
				],
			],

			// ──────────────────────────────────────────────────────────────
			// WordPress Taxonomy Fields
			// ──────────────────────────────────────────────────────────────
			'taxonomy_select', 'taxonomy_radio', 'taxonomy_radio_inline' => [
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $this->getTaxonomyOptions( $field ),
				'multiple' => false,
			],

			'taxonomy_multicheck', 'taxonomy_multicheck_inline' => [
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $this->getTaxonomyOptions( $field ),
				'multiple' => true,
			],

			// ──────────────────────────────────────────────────────────────
			// WordPress Post Select Field
			// ──────────────────────────────────────────────────────────────
			'post_select' => [
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $this->getPostOptions( $field ),
				'multiple' => ! empty( $field['multiple'] ),
			],

			// ──────────────────────────────────────────────────────────────
			// Media/File Upload Fields
			// ──────────────────────────────────────────────────────────────
			'media_upload', 'file', 'image_upload', 'file_upload' => [
				'type'       => \Elementor\Controls_Manager::MEDIA,
				'media_type' => $this->getMediaType( $type, $field ),
				'default'    => [
					'url' => '',
				],
			],

			'file_list' => [
				'type' => \Elementor\Controls_Manager::GALLERY,
			],

			// ──────────────────────────────────────────────────────────────
			// Utility Fields
			// ──────────────────────────────────────────────────────────────
			'wysiwyg' => [
				'type' => \Elementor\Controls_Manager::WYSIWYG,
			],

			'colorpicker', 'color_picker' => [
				'type' => \Elementor\Controls_Manager::COLOR,
			],

			// ──────────────────────────────────────────────────────────────
			// Location Field (simplified to address input in Elementor)
			// ──────────────────────────────────────────────────────────────
			'location' => [
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => __( 'Enter address or coordinates (lat,lng)', 'wp-jarvis' ),
			],

			// ──────────────────────────────────────────────────────────────
			// Country & Timezone Fields
			// ──────────────────────────────────────────────────────────────
			'country' => [
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->getCountryOptions(),
			],

			'select_timezone', 'timezone' => [
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->getTimezoneOptions(),
			],

			// ──────────────────────────────────────────────────────────────
			// oEmbed Field
			// ──────────────────────────────────────────────────────────────
			'oembed' => [
				'type'          => \Elementor\Controls_Manager::URL,
				'placeholder'   => __( 'Enter video/embed URL', 'wp-jarvis' ),
				'show_external' => false,
				'default'       => [
					'url' => '',
				],
			],

			// ──────────────────────────────────────────────────────────────
			// Legacy/Fallback - Maps old schema types
			// ──────────────────────────────────────────────────────────────
			'html' => [
				'type' => \Elementor\Controls_Manager::WYSIWYG,
			],

			'boolean', 'bool' => [
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'wp-jarvis' ),
				'label_off'    => __( 'No', 'wp-jarvis' ),
				'return_value' => 'true',
			],

			'integer', 'float' => [
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min'  => $field['min'] ?? null,
				'max'  => $field['max'] ?? null,
				'step' => $type === 'integer' ? 1 : ( $field['step'] ?? 0.1 ),
			],

			'color' => [
				'type' => \Elementor\Controls_Manager::COLOR,
			],

			'image', 'media' => [
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => [
					'url' => '',
				],
			],

			// Default: text input
			default => [
				'type' => \Elementor\Controls_Manager::TEXT,
			],
		};
	}

	/**
	 * Normalize options array for Elementor.
	 *
	 * Handles both associative arrays and indexed arrays.
	 *
	 * @param array $options Options array.
	 *
	 * @return array<string, string>
	 */
	protected function normalizeOptions( array $options ): array {
		if ( empty( $options ) ) {
			return [];
		}

		// Check if it's an indexed array (e.g., ['option1', 'option2'])
		if ( array_is_list( $options ) ) {
			return array_combine( $options, $options );
		}

		// Already associative
		return array_map( 'strval', $options );
	}

	/**
	 * Get taxonomy terms as options.
	 *
	 * @param array<string, mixed> $field Field configuration.
	 *
	 * @return array<string, string>
	 */
	protected function getTaxonomyOptions( array $field ): array {
		$taxonomy = $field['taxonomy'] ?? 'category';
		$args     = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $field['hide_empty'] ?? false,
			'fields'     => 'id=>name',
		];

		$terms = get_terms( $args );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		return array_map( 'strval', $terms );
	}

	/**
	 * Get posts as options.
	 *
	 * @param array<string, mixed> $field Field configuration.
	 *
	 * @return array<string, string>
	 */
	protected function getPostOptions( array $field ): array {
		$postType = $field['post_type'] ?? 'post';
		$args     = [
			'post_type'      => $postType,
			'post_status'    => 'publish',
			'posts_per_page' => $field['limit'] ?? 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$posts   = get_posts( $args );
		$options = [];

		foreach ( $posts as $post ) {
			$options[ (string) $post->ID ] = $post->post_title;
		}

		return $options;
	}

	/**
	 * Get country options.
	 *
	 * @return array<string, string>
	 */
	protected function getCountryOptions(): array {
		// Common countries list - same as the CountryField class
		return [
			'US' => 'United States',
			'GB' => 'United Kingdom',
			'CA' => 'Canada',
			'AU' => 'Australia',
			'DE' => 'Germany',
			'FR' => 'France',
			'IT' => 'Italy',
			'ES' => 'Spain',
			'NL' => 'Netherlands',
			'BE' => 'Belgium',
			'AT' => 'Austria',
			'CH' => 'Switzerland',
			'SE' => 'Sweden',
			'NO' => 'Norway',
			'DK' => 'Denmark',
			'FI' => 'Finland',
			'IE' => 'Ireland',
			'NZ' => 'New Zealand',
			'JP' => 'Japan',
			'CN' => 'China',
			'IN' => 'India',
			'BR' => 'Brazil',
			'MX' => 'Mexico',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RU' => 'Russia',
			'ZA' => 'South Africa',
			'AE' => 'United Arab Emirates',
			'SG' => 'Singapore',
			'HK' => 'Hong Kong',
			'KR' => 'South Korea',
			'PK' => 'Pakistan',
		];
	}

	/**
	 * Get timezone options.
	 *
	 * @return array<string, string>
	 */
	protected function getTimezoneOptions(): array {
		$timezones = timezone_identifiers_list();
		$options   = [];

		foreach ( $timezones as $timezone ) {
			$options[ $timezone ] = str_replace( '_', ' ', $timezone );
		}

		return $options;
	}

	/**
	 * Get media type for media control.
	 *
	 * @param string $type Field type.
	 * @param array<string, mixed> $field Field configuration.
	 *
	 * @return string
	 */
	protected function getMediaType( string $type, array $field ): string {
		if ( isset( $field['media_type'] ) ) {
			return $field['media_type'];
		}

		return match ( $type ) {
			'image_upload' => 'image',
			default => '',
		};
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$shortcode = $this->getShortcode();
		$fields    = $shortcode->getFields();

		// Build attributes from settings
		$atts = [];
		foreach ( $fields as $field ) {
			$id = $field['id'] ?? '';
			if ( empty( $id ) || ! isset( $settings[ $id ] ) ) {
				continue;
			}

			$type  = $field['type'] ?? 'text';
			$value = $settings[ $id ];

			// Transform value based on a field type
			$value = $this->transformValueForShortcode( $type, $value );

			if ( $value !== null && $value !== '' ) {
				$atts[ $id ] = $value;
			}
		}

		// Get content if allowed
		$content = null;
		if ( $shortcode->getAllowContent() ) {
			$content = $settings['shortcode_content'] ?? null;
		}

		// Call the shortcode's render method directly
		// This ensures the same business logic is used everywhere
		echo $shortcode->handle( $atts, $content, $shortcode::getTag() );
	}

	/**
	 * Transform Elementor setting value for shortcode consumption.
	 *
	 * @param string $type Field type.
	 * @param mixed $value Setting value.
	 *
	 * @return mixed Transformed value.
	 */
	protected function transformValueForShortcode( string $type, mixed $value ): mixed {
		return match ( $type ) {
			// Media fields return an array with url/id - extract ID
			'media_upload', 'file', 'image_upload', 'file_upload', 'image', 'media' => is_array( $value ) ? ( $value['id'] ?? '' ) : $value,

			// URL fields return an array with url - extract URL string
			'url', 'text_url', 'oembed' => is_array( $value ) ? ( $value['url'] ?? '' ) : $value,

			// Gallery/file_list returns an array of attachments
			'file_list' => is_array( $value ) ? implode( ',', array_column( $value, 'id' ) ) : $value,

			// Multi-select returns array - join if needed
			'multi_select', 'multicheck', 'multicheck_inline',
			'taxonomy_multicheck', 'taxonomy_multicheck_inline' => is_array( $value ) ? implode( ',', $value ) : $value,

			// Boolean/switcher
			'checkbox', 'boolean', 'bool' => ! empty( $value ) ? '1' : '',

			// All other types pass through
			default => $value,
		};
	}

	/**
	 * Render widget output in editor.
	 *
	 * @return void
	 */
	protected function content_template(): void {
		// Use PHP rendering in the editor for consistency
		// This could be enhanced with JS preview if needed
	}
}
