<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Blocks;

use WPJarvis\Framework\Support\Facades\Config;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\Support\Facades\Log;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;

/**
 * Block - Fluent builder and utilities for Gutenberg blocks.
 *
 * Supports two registration approaches:
 * 1. block. Json-first (recommended): Use registerFromMetadata() or register_block_type() directly
 * 2. Fluent builder: Use Block::make() for simple PHP-only blocks
 *
 * @see https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/
 * @package WPJarvis\Framework\WP\Blocks
 */
class Block {
	/**
	 * The block name (without namespace).
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The block title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * The block namespace.
	 *
	 * @var string
	 */
	private string $namespace;

	/**
	 * The block description.
	 *
	 * @var string
	 */
	private string $description = '';

	/**
	 * The block category.
	 *
	 * @var string
	 */
	private string $category = 'widgets';

	/**
	 * The block icon.
	 *
	 * @var string
	 */
	private string $icon = 'block-default';

	/**
	 * The block keywords.
	 *
	 * @var array<string>
	 */
	private array $keywords = [];

	/**
	 * The block attributes (field configurations).
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $attributes = [];

	/**
	 * The block supports.
	 *
	 * @var array<string, mixed>
	 */
	private array $supports = [];

	/**
	 * The render callback.
	 *
	 * @var callable|null
	 */
	private $renderCallback;

	/**
	 * Field registry instance.
	 *
	 * @var FieldRegistryInterface
	 */
	private FieldRegistryInterface $fieldRegistry;

	/**
	 * Whether to use server-side rendering.
	 *
	 * @var bool
	 */
	private bool $serverSideRender = false;

	/**
	 * Editor script handle or path.
	 *
	 * @var string|null
	 */
	private ?string $editorScript = null;

	/**
	 * Editor style handle or path.
	 *
	 * @var string|null
	 */
	private ?string $editorStyle = null;

	/**
	 * Frontend style handle or path.
	 *
	 * @var string|null
	 */
	private ?string $style = null;

	/**
	 * View script handle or path (frontend JS).
	 *
	 * @var string|null
	 */
	private ?string $viewScript = null;

	/**
	 * Create a new Block instance.
	 *
	 * @param string $name The block name (without namespace).
	 * @param string $title The block title.
	 * @param FieldRegistryInterface|null $fieldRegistry Field registry instance.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct( string $name, string $title, ?FieldRegistryInterface $fieldRegistry = null ) {
		// Block type names must be all lowercase
		$this->namespace     = strtolower( Config::get( 'app.slug', 'wpjarvis' ) );
		$this->name          = strtolower( $name );
		$this->title         = $title;
		$this->fieldRegistry = $fieldRegistry ?? wpj_app( 'field_registry' );
	}

	/**
	 * Create a new Block instance (fluent builder).
	 *
	 * @param string $name The block name (without namespace).
	 * @param string $title The block title.
	 *
	 * @return static The new Block instance.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function make( string $name, string $title ): static {
		return new static( $name, $title );
	}

	/**
	 * Register a block from its block.json metadata file.
	 *
	 * This is the recommended approach for block registration.
	 *
	 * @param string $blockDir Path to the block directory containing block.json.
	 * @param array<string, mixed> $args Optional. Additional args to pass to register_block_type().
	 *
	 * @return \WP_Block_Type|false The registered block type or false on failure.
	 */
	public static function registerFromMetadata( string $blockDir, array $args = [] ): \WP_Block_Type|false {
		$register = static function () use ( $blockDir, $args ) {
			return register_block_type( $blockDir, $args );
		};

		if ( Hooks::didAction( 'init' ) ) {
			return $register();
		}

		$result = false;
		Hooks::action( 'init', static function () use ( $register, &$result ) {
			$result = $register();
		} );

		return $result;
	}

	/**
	 * Set block namespace.
	 *
	 * @param string $namespace The namespace.
	 *
	 * @return static The current instance for chaining.
	 */
	public function namespace( string $namespace ): static {
		// Block type names must be all lowercase
		$this->namespace = strtolower( $namespace );

		return $this;
	}

	/**
	 * Set block description.
	 *
	 * @param string $description The description.
	 *
	 * @return static The current instance for chaining.
	 */
	public function description( string $description ): static {
		$this->description = $description;

		return $this;
	}

	/**
	 * Set a block category.
	 *
	 * @param string $category The category.
	 *
	 * @return static The current instance for chaining.
	 */
	public function category( string $category ): static {
		$this->category = $category;

		return $this;
	}

	/**
	 * Set the block icon.
	 *
	 * @param string $icon The dash icon.
	 *
	 * @return static The current instance for chaining.
	 */
	public function icon( string $icon ): static {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Set block keywords.
	 *
	 * @param string ...$keywords The keywords.
	 *
	 * @return static The current instance for chaining.
	 */
	public function keywords( string ...$keywords ): static {
		$this->keywords = array_merge( $this->keywords, $keywords );

		return $this;
	}

	/**
	 * Set block attributes.
	 *
	 * @param array<string, array<string, mixed>> $attributes The attributes.
	 *
	 * @return static The current instance for chaining.
	 */
	public function attributes( array $attributes ): static {
		$this->attributes = array_merge( $this->attributes, $attributes );

		return $this;
	}

	/**
	 * Add a text attribute.
	 *
	 * @param string $name The attribute name.
	 * @param string $default The default value.
	 * @param array<string, mixed> $options Additional attribute options.
	 *
	 * @return static The current instance for chaining.
	 */
	public function text( string $name, string $default = '', array $options = [] ): static {
		return $this->attribute( 'text', $name, $default, $options );
	}

	/**
	 * Add a number attribute.
	 *
	 * @param string $name The attribute name.
	 * @param int|float $default The default value.
	 * @param array<string, mixed> $options Additional attribute options.
	 *
	 * @return static The current instance for chaining.
	 */
	public function number( string $name, int|float $default = 0, array $options = [] ): static {
		return $this->attribute( 'number', $name, $default, $options );
	}

	/**
	 * Add a boolean attribute.
	 *
	 * @param string $name The attribute name.
	 * @param bool $default The default value.
	 * @param array<string, mixed> $options Additional attribute options.
	 *
	 * @return static The current instance for chaining.
	 */
	public function bool( string $name, bool $default = false, array $options = [] ): static {
		return $this->attribute( 'checkbox', $name, $default, $options );
	}

	/**
	 * Add a select attribute.
	 *
	 * @param string $name The attribute name.
	 * @param array<string, mixed> $choices The available choices.
	 * @param string $default The default value.
	 * @param array<string, mixed> $options Additional attribute options.
	 *
	 * @return static The current instance for chaining.
	 */
	public function select( string $name, array $choices, string $default = '', array $options = [] ): static {
		$options['choices'] = $choices;

		return $this->attribute( 'select', $name, $default, $options );
	}

	/**
	 * Add an attribute with a specific type.
	 *
	 * @param string $type The attribute type.
	 * @param string $name The attribute name.
	 * @param mixed $default The default value.
	 * @param array<string, mixed> $options Additional attribute options.
	 *
	 * @return static The current instance for chaining.
	 */
	public function attribute( string $type, string $name, mixed $default, array $options = [] ): static {
		$this->attributes[ $name ] = array_merge( [
			'type'    => $type,
			'name'    => $name,
			'default' => $default,
		], $options );

		return $this;
	}

	/**
	 * Set block supports.
	 *
	 * @param array<string, mixed> $supports The supports.
	 *
	 * @return static The current instance for chaining.
	 */
	public function supports( array $supports ): static {
		$this->supports = array_merge( $this->supports, $supports );

		return $this;
	}

	/**
	 * Set server-side rendering.
	 *
	 * @param bool $enabled Whether to enable server-side rendering.
	 *
	 * @return static The current instance for chaining.
	 */
	public function serverSideRender( bool $enabled = true ): static {
		$this->serverSideRender = $enabled;

		return $this;
	}

	/**
	 * Set render callback.
	 *
	 * @param callable $callback The render function.
	 *
	 * @return static The current instance for chaining.
	 */
	public function render( callable $callback ): static {
		$this->renderCallback = $callback;

		return $this;
	}

	/**
	 * Set editor script.
	 *
	 * @param string $script Script handle or path.
	 *
	 * @return static The current instance for chaining.
	 */
	public function editorScript( string $script ): static {
		$this->editorScript = $script;

		return $this;
	}

	/**
	 * Set the editor style.
	 *
	 * @param string $style Style handle or path.
	 *
	 * @return static The current instance for chaining.
	 */
	public function editorStyle( string $style ): static {
		$this->editorStyle = $style;

		return $this;
	}

	/**
	 * Set a frontend style.
	 *
	 * @param string $style Style handle or path.
	 *
	 * @return static The current instance for chaining.
	 */
	public function style( string $style ): static {
		$this->style = $style;

		return $this;
	}

	/**
	 * Set view script (frontend JavaScript).
	 *
	 * @param string $script Script handle or path.
	 *
	 * @return static The current instance for chaining.
	 */
	public function viewScript( string $script ): static {
		$this->viewScript = $script;

		return $this;
	}

	/**
	 * Register a block with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		// Check if init has already fired
		if ( Hooks::didAction( 'init' ) ) {
			// Register immediately since hook already fired
			$this->registerBlock();
		} else {
			// Hook for normal registration
			Hooks::action( 'init', [ $this, 'registerBlock' ] );
		}
	}

	/**
	 * Register a block type.
	 *
	 * @return void
	 */
	public function registerBlock(): void {
		$blockConfig = [
			'title'           => $this->title,
			'description'     => $this->description,
			'category'        => $this->category,
			'icon'            => $this->icon,
			'keywords'        => $this->keywords,
			'attributes'      => $this->getAttributesForBlock(),
			'supports'        => $this->supports,
			'render_callback' => $this->serverSideRender || $this->renderCallback ? [ $this, 'renderBlock' ] : null,
		];

		// Add asset references if set
		if ( $this->editorScript !== null ) {
			$blockConfig['editor_script'] = $this->editorScript;
		}
		if ( $this->editorStyle !== null ) {
			$blockConfig['editor_style'] = $this->editorStyle;
		}
		if ( $this->style !== null ) {
			$blockConfig['style'] = $this->style;
		}
		if ( $this->viewScript !== null ) {
			$blockConfig['view_script'] = $this->viewScript;
		}

		register_block_type( $this->namespace . '/' . $this->name, $blockConfig );
	}

	/**
	 * Get attributes formatted for Gutenberg block registration.
	 *
	 * @return array<string, array{type: string, default: mixed}> The formatted attributes.
	 */
	private function getAttributesForBlock(): array {
		$attributes = [];

		foreach ( $this->attributes as $name => $config ) {
			$fieldType = $config['type'] ?? 'text';
			$default   = $config['default'] ?? null;

			// Map field types to Gutenberg attribute types
			$gutenbergType = match ( $fieldType ) {
				'number' => 'number',
				'checkbox', 'bool' => 'boolean',
				'array' => 'array',
				'object' => 'object',
				default => 'string',
			};

			$attributes[ $name ] = [
				'type'    => $gutenbergType,
				'default' => $default,
			];

			// Add a source for select fields
			if ( isset( $config['choices'] ) && in_array( $fieldType, [ 'select', 'radio' ], true ) ) {
				$attributes[ $name ]['enum'] = array_keys( $config['choices'] );
			}
		}

		return $attributes;
	}

	/**
	 * Render block.
	 *
	 * @param array<string, mixed> $attributes The block attributes.
	 * @param string $content The block content (inner blocks).
	 * @param \WP_Block|null $block The block instance.
	 *
	 * @return string The rendered output.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function renderBlock( array $attributes, string $content = '', ?\WP_Block $block = null ): string {
		if ( ! $this->renderCallback ) {
			return '';
		}

		// Sanitize and validate attributes using Field System
		$attributes = $this->sanitizeAttributes( $attributes );

		// Validate attributes
		$validationErrors = $this->validateAttributes( $attributes );
		if ( ! empty( $validationErrors ) ) {
			// Log validation errors (in production, you might want to handle this differently)
			Log::error(
				'Block validation errors: ' . print_r( $validationErrors, true ),
				[ 'exception' => $validationErrors ]
			);
		}

		ob_start();
		$result = call_user_func( $this->renderCallback, $attributes, $content, $block );
		$output = ob_get_clean();

		return is_string( $result ) ? $result : $output;
	}

	/**
	 * Sanitize attributes using Field System.
	 *
	 * @param array<string, mixed> $attributes The attributes to sanitize.
	 *
	 * @return array<string, mixed> The sanitized attributes.
	 */
	private function sanitizeAttributes( array $attributes ): array {
		$sanitized = [];

		foreach ( $attributes as $name => $value ) {
			if ( ! isset( $this->attributes[ $name ] ) ) {
				// Unknown attribute, use default sanitization
				$sanitized[ $name ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
				continue;
			}

			$attribute = $this->attributes[ $name ];
			$fieldType = $attribute['type'] ?? 'text';

			try {
				$fieldInstance      = $this->fieldRegistry->create( $fieldType, $attribute );
				$sanitized[ $name ] = $fieldInstance->prepare( $value, $attribute );
			} catch ( \Exception $e ) {
				// Fallback to default sanitization if field creation fails
				$sanitized[ $name ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Validate attributes using Field System.
	 *
	 * @param array<string, mixed> $attributes The attributes to validate.
	 *
	 * @return array<string, string> Validation errors keyed by attribute name.
	 */
	private function validateAttributes( array $attributes ): array {
		$errors = [];

		foreach ( $attributes as $name => $value ) {
			if ( ! isset( $this->attributes[ $name ] ) ) {
				continue;
			}

			$attribute = $this->attributes[ $name ];
			$fieldType = $attribute['type'] ?? 'text';

			try {
				$fieldInstance    = $this->fieldRegistry->create( $fieldType, $attribute );
				$validationResult = $fieldInstance->validate( $value, $attribute );

				if ( $validationResult !== true ) {
					$errors[ $name ] = is_string( $validationResult ) ? $validationResult : __( 'Invalid value', 'wp-jarvis' );
				}
			} catch ( \Exception $e ) {
				$errors[ $name ] = $e->getMessage();
			}
		}

		return $errors;
	}

	/**
	 * Get the full block name (namespace/name).
	 *
	 * @return string The full block name.
	 */
	public function getFullName(): string {
		return $this->namespace . '/' . $this->name;
	}

	/**
	 * Get the block name.
	 *
	 * @return string The block name.
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the block title.
	 *
	 * @return string The block title.
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Get the block attributes.
	 *
	 * @return array<string, array<string, mixed>> The block attributes.
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}

	/**
	 * Get default values for all attributes.
	 *
	 * @return array<string, mixed> The default values.
	 */
	public function getDefaults(): array {
		$defaults = [];

		foreach ( $this->attributes as $name => $config ) {
			if ( isset( $config['default'] ) ) {
				$defaults[ $name ] = $config['default'];
			}
		}

		return $defaults;
	}

	/**
	 * Get the block namespace.
	 *
	 * @return string The block namespace.
	 */
	public function getNamespace(): string {
		return $this->namespace;
	}
}
