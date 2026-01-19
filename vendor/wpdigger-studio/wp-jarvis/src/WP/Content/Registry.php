<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Content;

use Illuminate\Support\Collection;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Registry - Central registry for WordPress content types.
 *
 * Manages registration and tracking of post-types, taxonomies, and metaboxes.
 */
class Registry {
	/**
	 * Registered post types.
	 *
	 * @var Collection<string, PostType>
	 */
	private Collection $postTypes;

	/**
	 * Registered taxonomies.
	 *
	 * @var Collection<string, Taxonomy>
	 */
	private Collection $taxonomies;

	/**
	 * Registered metaboxes.
	 *
	 * @var Collection<string, Metabox>
	 */
	private Collection $metaboxes;

	/**
	 * Create a new registry instance.
	 */
	public function __construct() {
		$this->postTypes  = new Collection();
		$this->taxonomies = new Collection();
		$this->metaboxes  = new Collection();
	}

	/**
	 * Create a post-type builder.
	 *
	 * @param string $slug Post type slug.
	 *
	 * @return PostType
	 */
	public function postType( string $slug ): PostType {
		$postType = PostType::make( $slug );
		$this->postTypes->put( $slug, $postType );

		return $postType;
	}

	/**
	 * Create a taxonomy builder.
	 *
	 * @param string $slug Taxonomy slug.
	 *
	 * @return Taxonomy
	 */
	public function taxonomy( string $slug ): Taxonomy {
		$taxonomy = Taxonomy::make( $slug );
		$this->taxonomies->put( $slug, $taxonomy );

		return $taxonomy;
	}

	/**
	 * Create a category-like taxonomy.
	 *
	 * @param string $slug Taxonomy slug.
	 *
	 * @return Taxonomy
	 */
	public function category( string $slug ): Taxonomy {
		return $this->taxonomy( $slug )->hierarchical( true );
	}

	/**
	 * Create a tag-like taxonomy.
	 *
	 * @param string $slug Taxonomy slug.
	 *
	 * @return Taxonomy
	 */
	public function tag( string $slug ): Taxonomy {
		return $this->taxonomy( $slug )->hierarchical( false );
	}

	/**
	 * Create a metabox builder.
	 *
	 * @param string $id Metabox ID.
	 * @param string $title Metabox title.
	 *
	 * @return Metabox
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function metabox( string $id, string $title ): Metabox {
		$metabox = Metabox::make( $id, $title );
		$this->metaboxes->put( $id, $metabox );

		return $metabox;
	}

	/**
	 * Get all registered post-types.
	 *
	 * @return Collection<string, PostType>
	 */
	public function getPostTypes(): Collection {
		return $this->postTypes;
	}

	/**
	 * Get a registered post-type.
	 *
	 * @param string $slug Post type slug.
	 *
	 * @return PostType|null
	 */
	public function getPostType( string $slug ): ?PostType {
		return $this->postTypes->get( $slug );
	}

	/**
	 * Get all registered taxonomies.
	 *
	 * @return Collection<string, Taxonomy>
	 */
	public function getTaxonomies(): Collection {
		return $this->taxonomies;
	}

	/**
	 * Get a registered taxonomy.
	 *
	 * @param string $slug Taxonomy slug.
	 *
	 * @return Taxonomy|null
	 */
	public function getTaxonomy( string $slug ): ?Taxonomy {
		return $this->taxonomies->get( $slug );
	}

	/**
	 * Get all registered metaboxes.
	 *
	 * @return Collection<string, Metabox>
	 */
	public function getMetaboxes(): Collection {
		return $this->metaboxes;
	}

	/**
	 * Get a registered metabox.
	 *
	 * @param string $id Metabox ID.
	 *
	 * @return Metabox|null
	 */
	public function getMetabox( string $id ): ?Metabox {
		return $this->metaboxes->get( $id );
	}

	/**
	 * Register all content types.
	 */
	public function registerAll(): void {
		Hooks::action( 'init', function () {
			$this->postTypes->each( fn( PostType $pt ) => $pt->register() );
			$this->taxonomies->each( fn( Taxonomy $tax ) => $tax->register() );
		} );

		Hooks::action( 'add_meta_boxes', function () {
			$this->metaboxes->each( fn( Metabox $mb ) => $mb->register() );
		} );
	}

	/**
	 * Check if a post-type is registered.
	 *
	 * @param string $slug Post type slug.
	 *
	 * @return bool
	 */
	public function hasPostType( string $slug ): bool {
		return $this->postTypes->has( $slug );
	}

	/**
	 * Check if a taxonomy is registered.
	 *
	 * @param string $slug Taxonomy slug.
	 *
	 * @return bool
	 */
	public function hasTaxonomy( string $slug ): bool {
		return $this->taxonomies->has( $slug );
	}
}
