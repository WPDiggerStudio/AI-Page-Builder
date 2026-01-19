<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Content Types Configuration
    |--------------------------------------------------------------------------
    |
    | Define your custom post types and taxonomies here for automatic
    | registration. This provides a config-driven approach to content
    | type management.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    |
    | Define custom post types here. Each key is the post type slug.
    |
    | Supported options:
    | - singular: Singular label
    | - plural: Plural label
    | - icon: Menu icon (dashicons)
    | - supports: Array of supported features
    | - public: Whether publicly visible
    | - has_archive: Whether to have archive pages
    | - show_in_rest: REST API visibility
    | - hierarchical: Page-like hierarchy
    | - rewrite: URL rewrite rules
    | - capability_type: Capability type
    | - taxonomies: Array of associated taxonomies
    |
    */
    'post_types' => [
        // Example:
        // 'portfolio' => [
        //     'singular' => 'Portfolio',
        //     'plural' => 'Portfolios',
        //     'icon' => 'dashicons-portfolio',
        //     'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        //     'has_archive' => true,
        //     'show_in_rest' => true,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Taxonomies
    |--------------------------------------------------------------------------
    |
    | Define custom taxonomies here. Each key is the taxonomy slug.
    |
    | Supported options:
    | - singular: Singular label
    | - plural: Plural label
    | - post_types: Array of associated post types
    | - hierarchical: Category-like (true) or tag-like (false)
    | - show_in_rest: REST API visibility
    | - show_admin_column: Show column in admin list
    | - rewrite: URL rewrite rules
    |
    */
    'taxonomies' => [
        // Example:
        // 'portfolio_category' => [
        //     'singular' => 'Category',
        //     'plural' => 'Categories',
        //     'post_types' => ['portfolio'],
        //     'hierarchical' => true,
        //     'show_in_rest' => true,
        //     'show_admin_column' => true,
        // ],
    ],

];
