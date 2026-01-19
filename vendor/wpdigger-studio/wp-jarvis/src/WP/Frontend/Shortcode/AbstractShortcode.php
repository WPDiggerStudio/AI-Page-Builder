<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend\Shortcode;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;

/**
 * AbstractShortcode - Base class for all shortcodes.
 *
 * Provides a clean, typed foundation for shortcode development with
 * attribute schema, sanitization, view rendering, and asset management.
 *
 * @package WPJarvis\Framework\WP\Frontend\Shortcode
 */
abstract class AbstractShortcode {
	/**
	 * The shortcode tag.
	 *
	 * Override this in child classes with your shortcode tag.
	 */
	protected static string $tag = '';

	/**
	 * Whether the shortcode allows content between opening/closing tags.
	 */
	protected bool $allowContent = false;

	/**
	 * Attribute schema definitions.
	 *
	 * Format:
	 * [
	 *     'attribute_name' => [
	 *         'type' => 'string|integer|number|boolean|select|textarea|color|image',
	 *         'default' => mixed,
	 *         'description' => 'Attribute description',
	 *         'options' => ['opt1', 'opt2'], // For select type
	 *         'min' => 0, // For number/integer
	 *         'max' => 100, // For number/integer
	 *         'required' => false,
	 *     ],
	 * ]
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $attributes = [];

	/**
	 * Whether this shortcode has been registered.
	 */
	private bool $registered = false;

	/**
	 * Field registry for advanced sanitization.
	 */
	private ?FieldRegistryInterface $fieldRegistry = null;

	/**
	 * Cached merged attributes.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $cachedDefaults = null;

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function register(): void {

		if ( $this->registered ) {
			return;
		}

		$tag = static::getTag();

		if ( empty( $tag ) ) {
			throw new \RuntimeException( __( 'Shortcode tag cannot be empty. Override $tag in ', 'wp-jarvis' ) . static::class );
		}

		add_shortcode( $tag, [ $this, 'handle' ] );
		$this->registered = true;

		// Register with a central registry if available
		if ( function_exists( 'wpj_app' ) && wpj_app()->has( ShortcodeRegistry::class ) ) {
			wpj_app()->make( ShortcodeRegistry::class )->register( $this );
		}
		/**
		 * Fires when a shortcode is registered.
		 *
		 * @param AbstractShortcode $shortcode The shortcode instance.
		 * @param string $tag The shortcode tag.
		 */
		Hooks::doAction( 'shortcode_registered', $this, $tag );
	}

	/**
	 * Handle shortcode execution.
	 *
	 * This is the WordPress callback - sanitizing and delegates to render().
	 *
	 * @param array<string, mixed>|string $atts Raw attributes from WordPress.
	 * @param string|null $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 *
	 * @return string Rendered output.
	 */
	public function handle( $atts, ?string $content = null, string $tag = '' ): string {
		// Normalize attributes (WordPress passes empty string if no attributes)
		$atts = is_array( $atts ) ? $atts : [];

		// Merge with defaults
		$atts = shortcode_atts( $this->getDefaults(), $atts, $tag );

		// Sanitize based on attribute schema
		$atts = $this->sanitizeAttributes( $atts );

		// Handle content
		if ( ! $this->allowContent ) {
			$content = null;
		} elseif ( $content !== null ) {
			// Process nested shortcodes and trim
			$content = do_shortcode( trim( $content ) );
		}

		// Enqueue assets only when a shortcode is used
		$this->maybeEnqueueAssets();

		// Delegate to user's render method
		return $this->render( $atts, $content );
	}

	/**
	 * Render shortcode output.
	 *
	 * Override this method to provide your shortcode's HTML output.
	 *
	 * @param array<string, mixed> $atts Sanitized attributes.
	 * @param string|null $content Processed content (if allowed).
	 *
	 * @return string Rendered HTML.
	 */
	abstract public function render( array $atts, ?string $content = null ): string;

	/**
	 * Enqueue assets for this shortcode.
	 *
	 * Override to register scripts/styles that should load only when shortcode is used.
	 *
	 * @return void
	 */
	public function enqueueAssets(): void {
		// Override in child classes
	}

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 */
	public static function getTag(): string {
		return static::$tag;
	}

	/**
	 * Check if content is allowed.
	 *
	 * @return bool
	 */
	public function getAllowContent(): bool {
		return $this->allowContent;
	}

	/**
	 * Get attribute schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getAttributeSchema(): array {
		return $this->attributes;
	}

	/**
	 * Define shortcode fields (Metabox-compatible format).
	 *
	 * Override this method in child classes to define fields using the same
	 * structure as Metabox::fields() for consistency with the Field System.
	 *
	 * @return array<int, array<string, mixed>> Array of field definitions.
	 */
	protected function fields(): array {
		// Default: empty, child classes should override
		return [];
	}

	/**
	 * Get fields in Metabox-compatible format for the Field System.
	 *
	 * Prefers the fields() method if it returns a non-empty array.
	 * Falls back to converting getAttributeSchema() for backward compatibility.
	 *
	 * @return array<int, array<string, mixed>> Array of field definitions.
	 */
	public function getFields(): array {
		// Prefer the new fields() method
		$fields = $this->fields();
		if ( ! empty( $fields ) ) {
			return $fields;
		}

		// Fallback: convert from getAttributeSchema() for backward compatibility
		$schema = $this->getAttributeSchema();
		if ( empty( $schema ) ) {
			return [];
		}

		$converted = [];
		foreach ( $schema as $id => $config ) {
			// Map shortcode attribute types to Field System types
			$fieldType = $this->mapAttributeTypeToFieldType( $config['type'] ?? 'string' );

			$field = [
				'type'    => $fieldType,
				'id'      => $id,
				'label'   => $config['description'] ?? ucfirst( str_replace( '_', ' ', $id ) ),
				'default' => $config['default'] ?? '',
			];

			// Map schema options to field options
			if ( isset( $config['options'] ) ) {
				$field['options'] = $config['options'];
			}
			if ( isset( $config['min'] ) ) {
				$field['min'] = $config['min'];
			}
			if ( isset( $config['max'] ) ) {
				$field['max'] = $config['max'];
			}
			if ( isset( $config['required'] ) ) {
				$field['required'] = $config['required'];
			}
			if ( isset( $config['placeholder'] ) ) {
				$field['placeholder'] = $config['placeholder'];
			}
			if ( isset( $config['rows'] ) ) {
				$field['rows'] = $config['rows'];
			}

			$converted[] = $field;
		}

		return $converted;
	}

	/**
	 * Map a shortcode attribute type to Field System type.
	 *
	 * @param string $attributeType The shortcode attribute type.
	 *
	 * @return string The corresponding Field System type.
	 */
	protected function mapAttributeTypeToFieldType( string $attributeType ): string {
		return match ( $attributeType ) {
			'string', 'text' => 'text',
			'textarea' => 'textarea',
			'html', 'HTML' => 'wysiwyg',
			'integer', 'int' => 'number',
			'number', 'float' => 'number',
			'boolean', 'bool' => 'checkbox',
			'select' => 'select',
			'color' => 'color',
			'image', 'media' => 'image',
			'url' => 'url',
			'email' => 'email',
			default => 'text',
		};
	}

	/**
	 * Get default values from fields or attribute schema.
	 *
	 * @return array<string, mixed>
	 */
	public function getDefaults(): array {
		if ( $this->cachedDefaults !== null ) {
			return $this->cachedDefaults;
		}

		$defaults = [];

		// Try fields() method first
		$fields = $this->fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$id = $field['id'] ?? '';
				if ( $id !== '' ) {
					$defaults[ $id ] = $field['default'] ?? '';
				}
			}
		} else {
			// Fallback to getAttributeSchema()
			foreach ( $this->getAttributeSchema() as $name => $schema ) {
				$defaults[ $name ] = $schema['default'] ?? '';
			}
		}

		$this->cachedDefaults = $defaults;

		return $defaults;
	}

	/**
	 * Check if this shortcode is registered.
	 *
	 * @return bool
	 */
	public function isRegistered(): bool {
		return $this->registered;
	}

	/**
	 * Sanitize attributes based on schema.
	 *
	 * @param array<string, mixed> $atts Raw attributes.
	 *
	 * @return array<string, mixed> Sanitized attributes.
	 */
	protected function sanitizeAttributes( array $atts ): array {
		$schema = $this->getAttributeSchema();

		foreach ( $atts as $name => $value ) {
			if ( ! isset( $schema[ $name ] ) ) {
				// Attribute not in schema, apply basic sanitization
				$atts[ $name ] = sanitize_text_field( (string) $value );
				continue;
			}

			$atts[ $name ] = $this->sanitizeByType( $value, $schema[ $name ] );
		}

		return $atts;
	}

	/**
	 * Sanitize a value based on its type schema.
	 *
	 * @param mixed $value The value to sanitize.
	 * @param array<string, mixed> $schema The attribute schema.
	 *
	 * @return mixed Sanitized value.
	 */
	protected function sanitizeByType( mixed $value, array $schema ): mixed {
		$type = $schema['type'] ?? 'string';

		return match ( $type ) {
			'integer' => $this->sanitizeInteger( $value, $schema ),
			'number', 'float' => $this->sanitizeNumber( $value, $schema ),
			'boolean', 'bool' => $this->sanitizeBoolean( $value ),
			'select' => $this->sanitizeSelect( $value, $schema ),
			'textarea' => sanitize_textarea_field( (string) $value ),
			'html' => wp_kses_post( (string) $value ),
			'url' => esc_url_raw( (string) $value ),
			'email' => sanitize_email( (string) $value ),
			'color' => sanitize_hex_color( (string) $value ) ?: ( $schema['default'] ?? '' ),
			'image', 'media' => absint( $value ),
			default => sanitize_text_field( (string) $value ),
		};
	}

	/**
	 * Sanitize integer value with optional min/max bounds.
	 *
	 * @param mixed $value The value.
	 * @param array<string, mixed> $schema The schema.
	 *
	 * @return int
	 */
	protected function sanitizeInteger( mixed $value, array $schema ): int {
		$value = (int) $value;

		if ( isset( $schema['min'] ) ) {
			$value = max( (int) $schema['min'], $value );
		}
		if ( isset( $schema['max'] ) ) {
			$value = min( (int) $schema['max'], $value );
		}

		return $value;
	}

	/**
	 * Sanitize number (float) value with optional min/max bounds.
	 *
	 * @param mixed $value The value.
	 * @param array<string, mixed> $schema The schema.
	 *
	 * @return float
	 */
	protected function sanitizeNumber( mixed $value, array $schema ): float {
		$value = (float) $value;

		if ( isset( $schema['min'] ) ) {
			$value = max( (float) $schema['min'], $value );
		}
		if ( isset( $schema['max'] ) ) {
			$value = min( (float) $schema['max'], $value );
		}

		return $value;
	}

	/**
	 * Sanitize boolean value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	protected function sanitizeBoolean( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), [ 'true', '1', 'yes', 'on' ], true );
		}

		return (bool) $value;
	}

	/**
	 * Sanitize select value against allowed options.
	 *
	 * @param mixed $value The value.
	 * @param array<string, mixed> $schema The schema.
	 *
	 * @return string
	 */
	protected function sanitizeSelect( mixed $value, array $schema ): string {
		$value   = (string) $value;
		$options = $schema['options'] ?? [];

		if ( empty( $options ) || in_array( $value, $options, true ) ) {
			return $value;
		}

		return $schema['default'] ?? ( $options[0] ?? '' );
	}

	/**
	 * Render a view template.
	 *
	 * Helper for template-based rendering.
	 *
	 * @param string $template Template name (relative to views/shortcodes/).
	 * @param array<string, mixed> $data Data to pass to the template.
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|string Rendered HTML.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function view( string $template, array $data = [] ): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|string {
		// Try to use the framework's view system if available
		if ( function_exists( 'wpj_view' ) ) {
			return wpj_view( 'shortcodes.' . $template, $data );
		}

		// Fallback: attempt to load from the views directory
		$paths = [
			wpj_app()->basePath( 'resources/views/shortcodes/' . $template . '.blade.php' ),
			wpj_app()->basePath( 'resources/views/shortcodes/' . $template . '.php' ),
		];

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $this->renderPhpTemplate( $path, $data );
			}
		}

		return '';
	}

	/**
	 * Render a PHP template with data.
	 *
	 * @param string $path Template path.
	 * @param array<string, mixed> $data Data for extraction.
	 *
	 * @return string
	 */
	private function renderPhpTemplate( string $path, array $data ): string {
		extract( $data, EXTR_SKIP );

		ob_start();
		include $path;

		return ob_get_clean() ?: '';
	}

	/**
	 * Maybe enqueue assets (only once).
	 *
	 * @return void
	 */
	private function maybeEnqueueAssets(): void {
		static $enqueued = [];

		$tag = static::getTag();
		if ( isset( $enqueued[ $tag ] ) ) {
			return;
		}

		$enqueued[ $tag ] = true;
		$this->enqueueAssets();
	}

	/**
	 * Build a shortcode tag string.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @param string|null $content Content.
	 *
	 * @return string
	 */
	public static function buildTag( array $atts = [], ?string $content = null ): string {
		$tag = '[' . static::getTag();

		foreach ( $atts as $key => $value ) {
			if ( $value !== '' && $value !== null ) {
				$tag .= sprintf( ' %s="%s"', $key, esc_attr( (string) $value ) );
			}
		}

		$tag .= ']';

		if ( $content !== null ) {
			$tag .= $content . '[/' . static::getTag() . ']';
		}

		return $tag;
	}

	/**
	 * Execute shortcode programmatically.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @param string|null $content Content.
	 *
	 * @return string
	 */
	public static function output( array $atts = [], ?string $content = null ): string {
		return do_shortcode( static::buildTag( $atts, $content ) );
	}

	/**
	 * Set the field registry for advanced sanitization.
	 *
	 * @param FieldRegistryInterface $registry
	 *
	 * @return static
	 */
	public function setFieldRegistry( FieldRegistryInterface $registry ): static {
		$this->fieldRegistry = $registry;

		return $this;
	}

	/**
	 * Get metadata for adapters (Elementor, WPBakery, TinyMCE).
	 *
	 * Override to customize metadata.
	 *
	 * @return array<string, mixed>
	 */
	public function getMetadata(): array {
		$tag       = static::getTag();
		$className = class_basename( static::class );

		return [
			'tag'         => $tag,
			'title'       => str_replace( [ '_', '-' ], ' ', ucwords( $tag, '_-' ) ),
			'description' => sprintf( __( 'Renders the [%s] shortcode.', 'wp-jarvis' ), $tag ),
			'icon'        => 'shortcode',
			'category'    => 'general',
			'keywords'    => [ $tag, 'shortcode', strtolower( $className ) ],
		];
	}
}
