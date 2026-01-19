<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * ContentServiceProvider
 *
 * Registers WordPress content types (post types, taxonomies, metaboxes).
 */
class ContentServiceProvider extends ServiceProvider {
	/**
	 * Register service provider.
	 */
	public function register(): void {
		// Register content registry
		$this->app->singleton( 'wp.content', function () {
			return new \WPJarvis\Framework\WP\Content\Registry();
		} );

		// Register auto-discovery
		$this->app->singleton( 'wp.content.autodiscovery', function () {
			return new \WPJarvis\Framework\WP\Content\AutoDiscovery(
				$this->app,
				$this->app->make( 'files' )
			);
		} );
	}

	/**
	 * Bootstrap service provider.
	 */
	public function boot(): void {
		// Register widgets early (before widgets_init fires at init priority 1)
		Hooks::action( 'init', [ $this, 'registerWidgetsEarly' ], 0 );

		// Register other content types after init
		Hooks::action( 'init', [ $this, 'registerContentTypes' ], 5 );
	}

	/**
	 * Register widgets early so they are available for widgets_init.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerWidgetsEarly(): void {
		$config = $this->app->make( 'config' );

		if ( $config->get( 'content.auto_discover', true ) ) {
			$autoDiscovery = $this->app->make( 'wp.content.autodiscovery' );
			$autoDiscovery->discoverWidgets();
		}
	}

	/**
	 * Register content types using auto-discovery or config.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerContentTypes(): void {
		$config = $this->app->make( 'config' );

		// Use auto-discovery if enabled
		if ( $config->get( 'content.auto_discover', true ) ) {
			$this->registerFromAutoDiscovery();
		} else {
			// Fallback to config-based registration
			$this->registerFromConfig();
		}

		// Register metaboxes from config (always config-based)
		$metaboxes = $config->get( 'content.metaboxes', [] );
		foreach ( $metaboxes as $slug => $settings ) {
			$this->registerMetabox( $slug, $settings );
		}
	}

	/**
	 * Register content types from auto-discovery.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerFromAutoDiscovery(): void {
		$autoDiscovery = $this->app->make( 'wp.content.autodiscovery' );

		// Discover and register post-types
		$autoDiscovery->discoverPostTypes();

		// Discover and register taxonomies
		$autoDiscovery->discoverTaxonomies();

		// Discover and register metaboxes
		$autoDiscovery->discoverMetaboxes();

		// Discover and register settings pages
		$autoDiscovery->discoverSettings();

		// Discover and register blocks
		$autoDiscovery->discoverBlocks();

		// Discover and register admin columns
		$autoDiscovery->discoverColumns();

		// Discover and register admin menus
		$autoDiscovery->discoverMenus();

		// Discover and register shortcodes
		$autoDiscovery->discoverShortcodes();

		// Note: Widgets are registered early in registerWidgetsEarly()
	}

	/**
	 * Register content types from the configuration.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerFromConfig(): void {
		$config = $this->app->make( 'config' );

		// Register post types
		$postTypes = $config->get( 'content.post_types', [] );
		foreach ( $postTypes as $slug => $settings ) {
			$this->registerPostType( $slug, $settings );
		}

		// Register taxonomies
		$taxonomies = $config->get( 'content.taxonomies', [] );
		foreach ( $taxonomies as $slug => $settings ) {
			$this->registerTaxonomy( $slug, $settings );
		}
	}

	/**
	 * Register a post-type from config.
	 *
	 * @param string $slug Post type slug.
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerPostType( string $slug, array $settings ): void {
		$labels = $this->buildPostTypeLabels(
			$settings['singular'] ?? ucfirst( $slug ),
			$settings['plural'] ?? ucfirst( $slug ) . 's'
		);

		$args = array_merge( [
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'supports'     => [ 'title', 'editor', 'thumbnail' ],
			'menu_icon'    => 'dashicons-admin-post',
		], $settings );

		unset( $args['singular'], $args['plural'] );

		\register_post_type( $slug, $args );
	}

	/**
	 * Build post-type labels.
	 *
	 * @param string $singular Singular label.
	 * @param string $plural Plural label.
	 *
	 * @return array<string, string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function buildPostTypeLabels( string $singular, string $plural ): array {
		$textdomain = $this->app->make( 'config' )->get( 'app.textdomain', 'wp-jarvis' );

		return [
			'name'                  => $plural,
			'singular_name'         => $singular,
			'add_new'               => \__( 'Add New', $textdomain ),
			'add_new_item'          => sprintf( \__( 'Add New %s', $textdomain ), $singular ),
			'edit_item'             => sprintf( \__( 'Edit %s', $textdomain ), $singular ),
			'new_item'              => sprintf( \__( 'New %s', $textdomain ), $singular ),
			'view_item'             => sprintf( \__( 'View %s', $textdomain ), $singular ),
			'view_items'            => sprintf( \__( 'View %s', $textdomain ), $plural ),
			'search_items'          => sprintf( \__( 'Search %s', $textdomain ), $plural ),
			'not_found'             => sprintf( \__( 'No %s found', $textdomain ), strtolower( $plural ) ),
			'not_found_in_trash'    => sprintf( \__( 'No %s found in trash', $textdomain ), strtolower( $plural ) ),
			'all_items'             => sprintf( \__( 'All %s', $textdomain ), $plural ),
			'archives'              => sprintf( \__( '%s Archives', $textdomain ), $singular ),
			'attributes'            => sprintf( \__( '%s Attributes', $textdomain ), $singular ),
			'insert_into_item'      => sprintf( \__( 'Insert into %s', $textdomain ), strtolower( $singular ) ),
			'uploaded_to_this_item' => sprintf( \__( 'Uploaded to this %s', $textdomain ), strtolower( $singular ) ),
			'menu_name'             => $plural,
		];
	}

	/**
	 * Register a taxonomy from config.
	 *
	 * @param string $slug Taxonomy slug.
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerTaxonomy( string $slug, array $settings ): void {
		$labels = $this->buildTaxonomyLabels(
			$settings['singular'] ?? ucfirst( $slug ),
			$settings['plural'] ?? ucfirst( $slug ) . 's'
		);

		$postTypes = $settings['post_types'] ?? [];

		$args = array_merge( [
			'labels'            => $labels,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		], $settings );

		unset( $args['singular'], $args['plural'], $args['post_types'] );

		\register_taxonomy( $slug, $postTypes, $args );
	}

	/**
	 * Build taxonomy labels.
	 *
	 * @param string $singular Singular label.
	 * @param string $plural Plural label.
	 *
	 * @return array<string, string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function buildTaxonomyLabels( string $singular, string $plural ): array {
		$textdomain = $this->app->make( 'config' )->get( 'app.textdomain', 'wp-jarvis' );

		return [
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => sprintf( \__( 'Search %s', $textdomain ), $plural ),
			'all_items'         => sprintf( \__( 'All %s', $textdomain ), $plural ),
			'parent_item'       => sprintf( \__( 'Parent %s', $textdomain ), $singular ),
			'parent_item_colon' => sprintf( \__( 'Parent %s:', $textdomain ), $singular ),
			'edit_item'         => sprintf( \__( 'Edit %s', $textdomain ), $singular ),
			'update_item'       => sprintf( \__( 'Update %s', $textdomain ), $singular ),
			'add_new_item'      => sprintf( \__( 'Add New %s', $textdomain ), $singular ),
			'new_item_name'     => sprintf( \__( 'New %s Name', $textdomain ), $singular ),
			'menu_name'         => $plural,
		];
	}

	/**
	 * Register a metabox from config.
	 *
	 * @param string $slug Metabox slug.
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerMetabox( string $slug, array $settings ): void {
		$title     = $settings['title'] ?? ucfirst( $slug );
		$postTypes = $settings['post_types'] ?? [];
		$context   = $settings['context'] ?? 'normal';
		$priority  = $settings['priority'] ?? 'default';
		$fields    = $settings['fields'] ?? [];

		$metabox = \WPJarvis\Framework\WP\Content\Metabox::make( $slug, $title );

		if ( ! empty( $postTypes ) ) {
			$metabox->forPostTypes( ...$postTypes );
		}

		$metabox->context( $context );
		$metabox->priority( $priority );

		// Add fields
		foreach ( $fields as $fieldId => $fieldConfig ) {
			$fieldType    = $fieldConfig['type'] ?? 'text';
			$fieldLabel   = $fieldConfig['label'] ?? ucfirst( $fieldId );
			$fieldOptions = $fieldConfig;

			// Remove keys that are passed as separate parameters
			unset( $fieldOptions['type'], $fieldOptions['label'] );

			$metabox->field( $fieldType, $fieldId, $fieldLabel, $fieldOptions );
		}

		Hooks::action( 'add_meta_boxes', [ $metabox, 'register' ] );
	}
}
