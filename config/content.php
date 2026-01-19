<?php

declare( strict_types=1 );

return [
	/*
	|--------------------------------------------------------------------------
	| Content Configuration
	|--------------------------------------------------------------------------
	|
	| This file contains configuration for all WordPress content types
	| including post types, taxonomies, metaboxes, widgets,
	| shortcodes, settings pages, and blocks.
	|
	*/

	/*
	|--------------------------------------------------------------------------
	| Auto-Discovery
	|--------------------------------------------------------------------------
	|
	| Automatically discover and register post types and taxonomies from
	| app/PostTypes and app/Taxonomies directories.
	|
	| Set to false to use config-based registration only.
	|
	*/
	'auto_discover' => env( 'CONTENT_AUTO_DISCOVER', true ),

	/*
	|--------------------------------------------------------------------------
	| Post Types
	|--------------------------------------------------------------------------
	|
	| Define custom post types with their configuration.
	| The framework will automatically register these post types.
	|
	*/

	'post_types' => [
		// Example post type configuration
		'book' => [
			'label'         => 'Books',
			'description'   => 'Book collection',
			'public'        => true,
			'hierarchical'  => false,
			'supports'      => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'custom-fields',
			],
			'rewrite'       => [
				'slug'       => 'books',
				'with_front' => true,
			],
			'taxonomies'    => [ 'book_category', 'book_tag' ],
			'menu_icon'     => 'dashicons-book-alt',
			'menu_position' => 20,
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Taxonomies
	|--------------------------------------------------------------------------
	|
	| Define custom taxonomies with their configuration.
	| The framework will automatically register these taxonomies.
	|
	*/

	'taxonomies' => [
		// Example taxonomy configuration
		'book_category' => [
			'post_types'        => [ 'book' ],
			'label'             => 'Book Categories',
			'labels'            => [
				'name'                    => 'Book Categories',
				'singular_name'           => 'Book Category',
				'menu_name'               => 'Book Categories',
				'all_items'               => 'All Categories',
				'edit_item'               => 'Edit Category',
				'update_item'             => 'Update Category',
				'add_new_item'            => 'Add New Category',
				'new_item_name'           => 'New Category Name',
				'parent_item'             => 'Parent Category',
				'parent_item_colon'       => 'Parent Category:',
				'search_items'            => 'Search Categories',
				'popular_items'           => 'Popular Categories',
				'separate_items_with_and' => 'and',
				'add_or_remove_items'     => 'Add or remove categories',
				'choose_from_most_used'   => 'Choose from the most used categories',
				'not_found'               => 'No categories found.',
			],
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [
				'slug'         => 'book-categories',
				'with_front'   => false,
				'hierarchical' => true,
			],
			'show_in_rest'      => true,
			'show_in_quickedit' => true,
			'show_in_nav_menus' => false,
		],
		'book_tag'      => [
			'post_types'   => [ 'book' ],
			'label'        => 'Book Tags',
			'public'       => true,
			'hierarchical' => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'show_in_rest' => true,
			'query_var'    => true,
			'rewrite'      => [
				'slug'       => 'book-tags',
				'with_front' => false,
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Metaboxes
	|--------------------------------------------------------------------------
	|
	| Define metaboxes with their fields.
	| The framework will automatically register these metaboxes.
	|
	*/

	'metaboxes' => [
		// Example metabox configuration
		'book_details' => [
			'title'      => 'Book Details',
			'post_types' => [ 'book' ],
			'context'    => 'normal',
			'priority'   => 'high',
			'fields'     => [
				'isbn'         => [
					'type'     => 'text',
					'label'    => 'ISBN',
					'default'  => '',
					'required' => true,
				],
				'author'       => [
					'type'     => 'text',
					'label'    => 'Author',
					'default'  => '',
					'required' => true,
				],
				'publisher'    => [
					'type'    => 'text',
					'label'   => 'Publisher',
					'default' => '',
				],
				'publish_date' => [
					'type'    => 'date',
					'label'   => 'Publish Date',
					'default' => '',
				],
				'price'        => [
					'type'    => 'number',
					'label'   => 'Price',
					'default' => 0,
					'min'     => 0,
					'step'    => 0.01,
				],
				'pages'        => [
					'type'    => 'number',
					'label'   => 'Pages',
					'default' => 0,
					'min'     => 0,
				],
				'cover_image'  => [
					'type'  => 'image_upload',
					'label' => 'Cover Image',
				],
				'description'  => [
					'type'  => 'textarea',
					'label' => 'Description',
					'rows'  => 5,
				],
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Widgets
	|--------------------------------------------------------------------------
	|
	| Define widgets with their fields.
	| The framework will automatically register these widgets.
	|
	*/

	'widgets' => [
		// Example widget configuration
		'contact_info' => [
			'id'          => 'contact_info',
			'name'        => 'Contact Info',
			'description' => 'Display contact information',
			'fields'      => [
				'title'   => [
					'type'    => 'text',
					'label'   => 'Widget Title',
					'default' => 'Contact Us',
				],
				'phone'   => [
					'type'        => 'text',
					'label'       => 'Phone Number',
					'placeholder' => '+1 (555) 123-4567',
				],
				'email'   => [
					'type'        => 'email',
					'label'       => 'Email Address',
					'placeholder' => 'contact@example.com',
				],
				'address' => [
					'type'  => 'textarea',
					'label' => 'Address',
					'rows'  => 3,
				],
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Shortcodes
	|--------------------------------------------------------------------------
	|
	| Define shortcodes with their attributes.
	| The framework will automatically register these shortcodes.
	|
	*/

	'shortcodes' => [
		// Example shortcode configuration
		'alert'  => [
			'tag'           => 'alert',
			'allow_content' => true,
			'attributes'    => [
				'type'    => [
					'type'    => 'text',
					'default' => 'info',
				],
				'title'   => [
					'type'    => 'text',
					'default' => '',
				],
				'content' => [
					'type'    => 'textarea',
					'default' => '',
					'rows'    => 3,
				],
			],
		],
		'button' => [
			'tag'        => 'button',
			'attributes' => [
				'label' => [
					'type'    => 'text',
					'default' => 'Click Me',
				],
				'link'  => [
					'type'    => 'url',
					'default' => '#',
				],
				'size'  => [
					'type'    => 'select',
					'default' => 'medium',
					'choices' => [
						'small'  => 'Small',
						'medium' => 'Medium',
						'large'  => 'Large',
					],
				],
				'color' => [
					'type'    => 'select',
					'default' => 'primary',
					'choices' => [
						'primary'   => 'Primary',
						'secondary' => 'Secondary',
						'success'   => 'Success',
						'danger'    => 'Danger',
					],
				],
				'block' => [
					'type'    => 'checkbox',
					'default' => false,
				],
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Settings Pages
	|--------------------------------------------------------------------------
	|
	| Define settings pages with their sections and fields.
	| The framework will automatically register these settings pages.
	|
	*/

	'settings_pages' => [
		// Example settings page configuration
		'general_settings' => [
			'page_title' => 'General Settings',
			'menu_title' => 'General',
			'capability' => 'manage_options',
			'icon_url'   => 'dashicons-admin-generic',
			'sections'   => [
				'site_info' => [
					'title'       => 'Site Information',
					'description' => 'Configure basic site details',
					'fields'      => [
						'site_name'    => [
							'type'    => 'text',
							'label'   => 'Site Name',
							'default' => get_bloginfo( 'name' ),
						],
						'site_tagline' => [
							'type'    => 'text',
							'label'   => 'Site Tagline',
							'default' => get_bloginfo( 'description' ),
						],
						'site_url'     => [
							'type'    => 'url',
							'label'   => 'Site URL',
							'default' => home_url(),
						],
					],
				],
				'contact'   => [
					'title'       => 'Contact Information',
					'description' => 'Contact details',
					'fields'      => [
						'contact_email' => [
							'type'    => 'email',
							'label'   => 'Contact Email',
							'default' => get_option( 'admin_email' ),
						],
						'contact_phone' => [
							'type'        => 'text',
							'label'       => 'Contact Phone',
							'placeholder' => '+1 (555) 123-4567',
						],
					],
				],
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Blocks
	|--------------------------------------------------------------------------
	|
	| Define Gutenberg blocks with their attributes.
	| The framework will automatically register these blocks.
	|
	*/

	'blocks' => [
		// Example block configuration
		'testimonial' => [
			'name'        => 'testimonial',
			'title'       => 'Testimonial',
			'description' => 'Display customer testimonials',
			'category'    => 'widgets',
			'icon'        => 'format-quote',
			'keywords'    => [ 'quote', 'review', 'feedback' ],
			'attributes'  => [
				'author'  => [
					'type'    => 'text',
					'default' => '',
				],
				'role'    => [
					'type'    => 'text',
					'default' => '',
				],
				'content' => [
					'type'    => 'textarea',
					'default' => '',
					'rows'    => 3,
				],
			],
			'supports'    => [
				'align' => true,
				'color' => [
					'text'       => true,
					'background' => true,
				],
			],
		],
		'cta_button'  => [
			'name'        => 'cta_button',
			'title'       => 'Call to Action Button',
			'description' => 'Add a call to action button',
			'category'    => 'common',
			'icon'        => 'button',
			'attributes'  => [
				'label' => [
					'type'    => 'text',
					'default' => 'Learn More',
				],
				'link'  => [
					'type'    => 'url',
					'default' => '#',
				],
				'style' => [
					'type'    => 'select',
					'default' => 'primary',
					'choices' => [
						'primary'   => 'Primary',
						'secondary' => 'Secondary',
						'outline'   => 'Outline',
						'text'      => 'Text Only',
					],
				],
				'size'  => [
					'type'    => 'select',
					'default' => 'medium',
					'choices' => [
						'small'  => 'Small',
						'medium' => 'Medium',
						'large'  => 'Large',
					],
				],
				'block' => [
					'type'    => 'checkbox',
					'default' => false,
				],
			],
			'supports'    => [
				'align' => true,
				'color' => [
					'text'       => true,
					'background' => true,
				],
			],
		],
	],
];
