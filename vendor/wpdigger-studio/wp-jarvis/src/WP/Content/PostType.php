<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Content;

use Illuminate\Support\Str;

/**
 * post-type - Fluent builder for custom post-types.
 */

/**
 * PostType - Fluent builder for custom post-types.
 */
class PostType {
	/**
	 * The post-type slug.
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
	 * The post-type arguments.
	 *
	 * @var array<string, mixed>
	 */
	private array $args = [];

	/**
	 * Custom labels.
	 *
	 * @var array<string, string>
	 */
	private array $labels = [];

	/**
	 * The text domain for translations.
	 *
	 * @var string
	 */
	private string $textDomain;

	/**
	 * Create a new PostType instance.
	 *
	 * @param string $slug The post-type slug.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct( string $slug ) {
		$this->slug       = $slug;
		$this->textDomain = wpj_config( 'app.textdomain', 'wp-jarvis' );
		$this->singular   = Str::title( str_replace( [ '-', '_' ], ' ', $slug ) );
		$this->plural     = Str::plural( $this->singular );
	}

	/**
	 * Create a new PostType instance.
	 *
	 * @param string $slug The post-type slug.
	 *
	 * @return static The new PostType instance.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function make( string $slug ): static {
		return new static( $slug );
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
	 * Set the menu icon.
	 *
	 * @param string $icon The dash icon.
	 *
	 * @return static The current instance for chaining.
	 */
	public function icon( string $icon ): static {
		$this->args['menu_icon'] = $icon;

		return $this;
	}

	/**
	 * Set the menu position.
	 *
	 * @param int $position The menu position.
	 *
	 * @return static The current instance for chaining.
	 */
	public function position( int $position ): static {
		$this->args['menu_position'] = $position;

		return $this;
	}

	/**
	 * Set the supported features.
	 *
	 * @param array<string> $supports The supported features.
	 *
	 * @return static The current instance for chaining.
	 */
	public function supports( array $supports ): static {
		$this->args['supports'] = $supports;

		return $this;
	}

	/**
	 * Set the public visibility.
	 *
	 * @param bool $public Whether the post-type is public.
	 *
	 * @return static The current instance for chaining.
	 */
	public function public( bool $public = true ): static {
		$this->args['public'] = $public;

		return $this;
	}

	/**
	 * Set whether the post-type has archives.
	 *
	 * @param bool|string $archive True for default, or slug for custom archive.
	 *
	 * @return static The current instance for chaining.
	 */
	public function hasArchive( bool|string $archive = true ): static {
		$this->args['has_archive'] = $archive;

		return $this;
	}

	/**
	 * Set whether the post-type is hierarchical.
	 *
	 * @param bool $hierarchical Whether the post-type is hierarchical.
	 *
	 * @return static The current instance for chaining.
	 */
	public function hierarchical( bool $hierarchical = true ): static {
		$this->args['hierarchical'] = $hierarchical;

		return $this;
	}

	/**
	 * Set the rewrite rules.
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
	 * Set REST API visibility.
	 *
	 * @param bool $show Whether to show in REST API.
	 * @param string|null $base The REST base.
	 *
	 * @return static The current instance for chaining.
	 */
	public function showInRest( bool $show = true, ?string $base = null ): static {
		$this->args['show_in_rest'] = $show;
		if ( $base ) {
			$this->args['rest_base'] = $base;
		}

		return $this;
	}

	/**
	 * Set the capability type.
	 *
	 * @param string $type The capability type (post, page).
	 *
	 * @return static The current instance for chaining.
	 */
	public function capability( string $type ): static {
		$this->args['capability_type'] = $type;

		return $this;
	}

	/**
	 * Set the associated taxonomies.
	 *
	 * @param string ...$taxonomies The taxonomy slugs.
	 *
	 * @return static The current instance for chaining.
	 */
	public function taxonomies( string ...$taxonomies ): static {
		$this->args['taxonomies'] = $taxonomies;

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
		$s  = $this->singular;
		$p  = $this->plural;
		$sl = strtolower( $s );
		$pl = strtolower( $p );
		$d  = $this->textDomain;

		return array_merge( [
			'name'                  => __( $p, $d ),
			'singular_name'         => __( $s, $d ),
			'add_new'               => __( 'Add New', $d ),
			'add_new_item'          => __( 'Add New ' . $s, $d ),
			'edit_item'             => __( 'Edit ' . $s, $d ),
			'new_item'              => __( 'New ' . $s, $d ),
			'view_item'             => __( 'View ' . $s, $d ),
			'view_items'            => __( 'View ' . $p, $d ),
			'search_items'          => __( 'Search ' . $p, $d ),
			'not_found'             => __( 'No ' . $pl . ' found', $d ),
			'not_found_in_trash'    => __( 'No ' . $pl . ' found in Trash', $d ),
			'all_items'             => __( 'All ' . $p, $d ),
			'archives'              => __( $s . ' Archives', $d ),
			'attributes'            => __( $s . ' Attributes', $d ),
			'insert_into_item'      => __( 'Insert into ' . $sl, $d ),
			'uploaded_to_this_item' => __( 'Uploaded to this ' . $sl, $d ),
			'filter_items_list'     => __( 'Filter ' . $pl . ' list', $d ),
			'items_list_navigation' => __( $p . ' list navigation', $d ),
			'items_list'            => __( $p . ' list', $d ),
			'item_published'        => __( $s . ' published.', $d ),
			'item_updated'          => __( $s . ' updated.', $d ),
		], $this->labels );
	}

	/**
	 * Convert to arguments array.
	 *
	 * @return array<string, mixed> The post-type arguments.
	 */
	public function toArray(): array {
		return array_merge( [
			'labels'       => $this->buildLabels(),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => true,
			'supports'     => [ 'title', 'editor' ],
		], $this->args );
	}

	/**
	 * Register the post-type with WordPress.
	 *
	 * @return \WP_Post_Type|\WP_Error The registered post-type or error.
	 */
	public function register(): \WP_Post_Type|\WP_Error {
		return register_post_type( $this->slug, $this->toArray() );
	}

	/**
	 * Get the post-type slug.
	 *
	 * @return string The post-type slug.
	 */
	public function getSlug(): string {
		return $this->slug;
	}
}
