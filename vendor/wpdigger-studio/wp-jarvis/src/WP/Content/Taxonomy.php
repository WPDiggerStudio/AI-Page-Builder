<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Content;

use Illuminate\Support\Str;
use WPJarvis\Framework\Support\Facades\Config;

/**
 * Taxonomy - Fluent builder for custom taxonomies.
 */

/**
 * Taxonomy - Fluent builder for custom taxonomies.
 */
class Taxonomy {
	/**
	 * The taxonomy slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * The singular label.
	 *
	 * @var string
	 */
	private string $singular;

	/**
	 * The plural label.
	 *
	 * @var string
	 */
	private string $plural;

	/**
	 * The taxonomy arguments.
	 *
	 * @var array<string, mixed>
	 */
	private array $args = [];

	/**
	 * The post-types to associate with.
	 *
	 * @var array<string>
	 */
	private array $postTypes = [];

	/**
	 * The text domain for translations.
	 *
	 * @var string
	 */
	private string $textDomain;

	/**
	 * Create a new Taxonomy instance.
	 *
	 * @param string $slug The taxonomy slug.
	 */
	public function __construct( string $slug ) {
		$this->slug       = $slug;
		$this->singular   = Str::title( str_replace( [ '-', '_' ], ' ', $slug ) );
		$this->plural     = Str::plural( $this->singular );
		$this->textDomain = Config::get( 'app.textdomain', 'wp-jarvis' );
	}

	/**
	 * Create a new Taxonomy instance.
	 *
	 * @param string $slug The taxonomy slug.
	 *
	 * @return static The new Taxonomy instance.
	 */
	public static function make( string $slug ): static {
		return new static( $slug );
	}

	/**
	 * Create a category-like taxonomy.
	 *
	 * @param string $slug The taxonomy slug.
	 *
	 * @return static The new Taxonomy instance.
	 */
	public static function category( string $slug ): static {
		return static::make( $slug )->hierarchical( true );
	}

	/**
	 * Create a tag-like taxonomy.
	 *
	 * @param string $slug The taxonomy slug.
	 *
	 * @return static The new Taxonomy instance.
	 */
	public static function tag( string $slug ): static {
		return static::make( $slug )->hierarchical( false );
	}

	/**
	 * Set the singular and plural labels.
	 *
	 * @param string $singular The singular label.
	 * @param string|null $plural The plural label.
	 *
	 * @return static The current instance for chaining.
	 */
	public function labels( string $singular, ?string $plural = null ): static {
		$this->singular = $singular;
		$this->plural   = $plural ?? Str::plural( $singular );

		return $this;
	}

	/**
	 * Set the text domain for translations.
	 *
	 * @param string $textDomain The text domain.
	 *
	 * @return static The current instance for chaining.
	 */
	public function textDomain( string $textDomain ): static {
		$this->textDomain = $textDomain;

		return $this;
	}

	/**
	 * Associate taxonomy with post-types.
	 *
	 * @param string ...$postTypes The post-type slugs.
	 *
	 * @return static The current instance for chaining.
	 */
	public function forPostTypes( string ...$postTypes ): static {
		$this->postTypes = array_merge( $this->postTypes, $postTypes );

		return $this;
	}

	/**
	 * Set whether taxonomy is hierarchical.
	 *
	 * @param bool $hierarchical Whether to make hierarchical.
	 *
	 * @return static The current instance for chaining.
	 */
	public function hierarchical( bool $hierarchical = true ): static {
		$this->args['hierarchical'] = $hierarchical;

		return $this;
	}

	/**
	 * Set whether to show in REST API.
	 *
	 * @param bool $show Whether to show in REST API.
	 *
	 * @return static The current instance for chaining.
	 */
	public function showInRest( bool $show = true ): static {
		$this->args['show_in_rest'] = $show;

		return $this;
	}

	/**
	 * Set whether to show an admin column.
	 *
	 * @param bool $show Whether to show an admin column.
	 *
	 * @return static The current instance for chaining.
	 */
	public function showAdminColumn( bool $show = true ): static {
		$this->args['show_admin_column'] = $show;

		return $this;
	}

	/**
	 * Set rewrite rules.
	 *
	 * @param array|string|bool $rewrite The rewrite configuration.
	 *
	 * @return static The current instance for chaining.
	 */
	public function rewrite( array|string|bool $rewrite ): static {
		$this->args['rewrite'] = is_string( $rewrite ) ? [ 'slug' => $rewrite ] : $rewrite;

		return $this;
	}

	/**
	 * Set a custom argument.
	 *
	 * @param string $key The argument key.
	 * @param mixed $value The argument value.
	 *
	 * @return static The current instance for chaining.
	 */
	public function set( string $key, mixed $value ): static {
		$this->args[ $key ] = $value;

		return $this;
	}

	/**
	 * Build the labels array.
	 *
	 * @return array<string, string> The labels array.
	 */
	protected function buildLabels(): array {
		$s = $this->singular;
		$p = $this->plural;
		$d = $this->textDomain;

		return [
			'name'              => __( $p, $d ),
			'singular_name'     => __( $s, $d ),
			'search_items'      => __( 'Search ' . $p, $d ),
			'all_items'         => __( 'All ' . $p, $d ),
			'parent_item'       => __( 'Parent ' . $s, $d ),
			'parent_item_colon' => __( 'Parent ' . $s . ':', $d ),
			'edit_item'         => __( 'Edit ' . $s, $d ),
			'update_item'       => __( 'Update ' . $s, $d ),
			'add_new_item'      => __( 'Add New ' . $s, $d ),
			'new_item_name'     => __( 'New ' . $s . ' Name', $d ),
			'menu_name'         => __( $p, $d ),
		];
	}

	/**
	 * Convert to arguments array.
	 *
	 * @return array<string, mixed> The taxonomy arguments.
	 */
	public function toArray(): array {
		return array_merge( [
			'labels'       => $this->buildLabels(),
			'public'       => true,
			'show_ui'      => true,
			'hierarchical' => false,
		], $this->args );
	}

	/**
	 * Register the taxonomy with WordPress.
	 *
	 * @return \WP_Taxonomy|\WP_Error The registered taxonomy or error.
	 */
	public function register(): \WP_Taxonomy|\WP_Error {
		return register_taxonomy( $this->slug, $this->postTypes, $this->toArray() );
	}

	/**
	 * Get the taxonomy slug.
	 *
	 * @return string The taxonomy slug.
	 */
	public function getSlug(): string {
		return $this->slug;
	}
}
