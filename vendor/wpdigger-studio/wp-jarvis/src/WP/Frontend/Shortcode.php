<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend;

/**
 * Shortcode - Fluent builder for WordPress shortcodes.
 *
 * Provides a Laravel-style fluent interface for creating and registering
 * WordPress shortcodes with attribute validation and default handling.
 *
 * @package WPJarvis\Framework\WP\Frontend
 */
class Shortcode {
	/**
	 * Shortcode tag.
	 */
	private string $tag;

	/**
	 * Default attributes.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = [];

	/**
	 * Whether shortcode allows content.
	 */
	private bool $allowContent = true;

	/**
	 * Render callback.
	 *
	 * @var callable|null
	 */
	private $renderCallback = null;

	/**
	 * Before render callback.
	 *
	 * @var callable|null
	 */
	private $beforeCallback = null;

	/**
	 * After render callback.
	 *
	 * @var callable|null
	 */
	private $afterCallback = null;

	/**
	 * Attribute sanitization rules.
	 *
	 * @var array<string, callable>
	 */
	private array $sanitizers = [];

	/**
	 * Whether the shortcode is registered.
	 */
	private bool $registered = false;

	/**
	 * Create a new Shortcode instance.
	 *
	 * @param string $tag Shortcode tag.
	 */
	public function __construct( string $tag ) {
		$this->tag = $tag;
	}

	/**
	 * Create a new Shortcode instance.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return static
	 */
	public static function make( string $tag ): static {
		return new static( $tag );
	}

	/**
	 * Set default attributes.
	 *
	 * @param array<string, mixed> $defaults Default attribute values.
	 *
	 * @return static
	 */
	public function defaults( array $defaults ): static {
		$this->defaults = $defaults;

		return $this;
	}

	/**
	 * Set whether shortcode allows content.
	 *
	 * @param bool $allow Whether to allow content.
	 *
	 * @return static
	 */
	public function allowContent( bool $allow = true ): static {
		$this->allowContent = $allow;

		return $this;
	}

	/**
	 * Set the render callback.
	 *
	 * Callback signature: function (array $watts,string $content, string $tag): string
	 *
	 * @param callable $callback Render callback.
	 *
	 * @return static
	 */
	public function render( callable $callback ): static {
		$this->renderCallback = $callback;

		return $this;
	}

	/**
	 * Set before render callback.
	 *
	 * Callback signature: function(array &$atts,string &$content, string $tag): void
	 *
	 * @param callable $callback Before callback.
	 *
	 * @return static
	 */
	public function before( callable $callback ): static {
		$this->beforeCallback = $callback;

		return $this;
	}

	/**
	 * Set after render callback.
	 *
	 * Callback signature: function (string $output, array $atts,string $content): string
	 *
	 * @param callable $callback After callback.
	 *
	 * @return static
	 */
	public function after( callable $callback ): static {
		$this->afterCallback = $callback;

		return $this;
	}

	/**
	 * Add a sanitizer for an attribute.
	 *
	 * @param string $attribute Attribute name.
	 * @param callable $sanitizer Sanitizer callback.
	 *
	 * @return static
	 */
	public function sanitize( string $attribute, callable $sanitizer ): static {
		$this->sanitizers[ $attribute ] = $sanitizer;

		return $this;
	}

	/**
	 * Add multiple sanitizers.
	 *
	 * @param array<string, callable> $sanitizers Sanitizers keyed by attribute.
	 *
	 * @return static
	 */
	public function sanitizers( array $sanitizers ): static {
		$this->sanitizers = array_merge( $this->sanitizers, $sanitizers );

		return $this;
	}

	/**
	 * Get the shortcode tag.
	 *
	 * @return string
	 */
	public function getTag(): string {
		return $this->tag;
	}

	/**
	 * Get the default attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function getDefaults(): array {
		return $this->defaults;
	}

	/**
	 * Check if a shortcode allows content.
	 *
	 * @return bool
	 */
	public function getAllowContent(): bool {
		return $this->allowContent;
	}

	/**
	 * Check if a shortcode is registered.
	 *
	 * @return bool
	 */
	public function isRegistered(): bool {
		return $this->registered;
	}

	/**
	 * Handle shortcode execution.
	 *
	 * @param array<string, mixed>|string $atts Raw attributes from WordPress.
	 * @param string|null $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 *
	 * @return string Rendered output.
	 */
	public function handle( $atts, ?string $content = null, string $tag = '' ): string {
		// Normalize attributes
		$atts = is_array( $atts ) ? $atts : [];

		// Merge with defaults
		$atts = shortcode_atts( $this->defaults, $atts, $tag );

		// Apply sanitizers
		foreach ( $this->sanitizers as $key => $sanitizer ) {
			if ( isset( $atts[ $key ] ) ) {
				$atts[ $key ] = $sanitizer( $atts[ $key ] );
			}
		}

		// Handle content
		if ( ! $this->allowContent ) {
			$content = null;
		}

		// Before callback
		if ( $this->beforeCallback !== null ) {
			call_user_func_array( $this->beforeCallback, [ &$atts, &$content, $tag ] );
		}

		// Render
		$output = '';
		if ( $this->renderCallback !== null ) {
			$output = call_user_func( $this->renderCallback, $atts, $content, $tag );
		} else {
			// Default rendering
			$output = $this->defaultRender( $atts, $content );
		}

		// After callback
		if ( $this->afterCallback !== null ) {
			$output = call_user_func( $this->afterCallback, $output, $atts, $content );
		}

		return $output;
	}

	/**
	 * Default render implementation.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @param string|null $content Content.
	 *
	 * @return string
	 */
	private function defaultRender( array $atts, ?string $content ): string {
		$classes = [ $this->tag . '-shortcode' ];
		if ( ! empty( $atts['class'] ) ) {
			$classes[] = esc_attr( $atts['class'] );
		}

		$output = '<div class="' . implode( ' ', $classes ) . '">';

		if ( $content !== null ) {
			$output .= do_shortcode( $content );
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Convert to array representation.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'tag'           => $this->tag,
			'defaults'      => $this->defaults,
			'allow_content' => $this->allowContent,
			'registered'    => $this->registered,
		];
	}

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		add_shortcode( $this->tag, [ $this, 'handle' ] );
		$this->registered = true;
	}

	/**
	 * Unregister the shortcode.
	 *
	 * @return void
	 */
	public function unregister(): void {
		if ( ! $this->registered ) {
			return;
		}

		remove_shortcode( $this->tag );
		$this->registered = false;
	}

	/**
	 * Build a shortcode tag string for programmatic use.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @param string|null $content Content.
	 *
	 * @return string
	 */
	public function buildTag( array $atts = [], ?string $content = null ): string {
		$tag = '[' . $this->tag;

		foreach ( $atts as $key => $value ) {
			if ( $value !== '' && $value !== null ) {
				$tag .= sprintf( ' %s="%s"', $key, esc_attr( (string) $value ) );
			}
		}

		$tag .= ']';

		if ( $content !== null && $this->allowContent ) {
			$tag .= $content . '[/' . $this->tag . ']';
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
	public function output( array $atts = [], ?string $content = null ): string {
		return do_shortcode( $this->buildTag( $atts, $content ) );
	}
}
