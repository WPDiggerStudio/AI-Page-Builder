<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;

/**
 * Widget - Fluent builder for WordPress widgets.
 *
 * Provides a Laravel-style fluent interface for creating and registering
 * WordPress widgets without extending WP_Widget directly.
 *
 * @package WPJarvis\Framework\WP\Frontend
 */
class Widget {
	/**
	 * Widget ID.
	 */
	private string $id;

	/**
	 * Widget name/title.
	 */
	private string $name;

	/**
	 * Widget description.
	 */
	private string $description = '';

	/**
	 * CSS class for the widget.
	 */
	private string $classname;

	/**
	 * Widget control options.
	 *
	 * @var array<string, mixed>
	 */
	private array $controlOptions = [];

	/**
	 * Default instance values.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = [];

	/**
	 * Field definitions for this widget.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $fields = [];

	/**
	 * Render callback.
	 *
	 * @var callable|null
	 */
	private $renderCallback = null;

	/**
	 * Form callback.
	 *
	 * @var callable|null
	 */
	private $formCallback = null;

	/**
	 * Update callback.
	 *
	 * @var callable|null
	 */
	private $updateCallback = null;

	/**
	 * Text domain for translations.
	 */
	private string $textDomain;

	/**
	 * Field registry instance.
	 *
	 * @var FieldRegistryInterface|null
	 */
	private ?FieldRegistryInterface $fieldRegistry = null;

	/**
	 * Create a new Widget instance.
	 *
	 * @param string $id Widget ID.
	 * @param string $name Widget name/title.
	 * @param FieldRegistryInterface|null $fieldRegistry Field registry instance.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct( string $id, string $name, ?FieldRegistryInterface $fieldRegistry = null ) {
		$this->textDomain    = wpj_config( 'app.textdomain', 'wp-jarvis' );
		$this->id            = $id;
		$this->name          = $name;
		$this->classname     = 'widget-' . $id;
		$this->fieldRegistry = $fieldRegistry ?? ( function_exists( 'wpj_app' ) ? wpj_app( 'field_registry' ) : null );
	}

	/**
	 * Create a new Widget instance.
	 *
	 * @param string $id Widget ID.
	 * @param string $name Widget name/title.
	 *
	 * @return static
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function make( string $id, string $name ): static {
		return new static( $id, $name );
	}

	/**
	 * Set widget description.
	 *
	 * @param string $description Widget description.
	 *
	 * @return static
	 */
	public function description( string $description ): static {
		$this->description = $description;

		return $this;
	}

	/**
	 * Set CSS class name.
	 *
	 * @param string $classname CSS class name.
	 *
	 * @return static
	 */
	public function classname( string $classname ): static {
		$this->classname = $classname;

		return $this;
	}

	/**
	 * Set control options (width, height, etc.).
	 *
	 * @param array<string, mixed> $options Control options.
	 *
	 * @return static
	 */
	public function controlOptions( array $options ): static {
		$this->controlOptions = $options;

		return $this;
	}

	/**
	 * Set default instance values.
	 *
	 * @param array<string, mixed> $defaults Default values.
	 *
	 * @return static
	 */
	public function defaults( array $defaults ): static {
		$this->defaults = $defaults;

		return $this;
	}

	/**
	 * Add a field to this widget.
	 *
	 * @param string $type The field type identifier.
	 * @param string $id The field ID.
	 * @param string $label The field label.
	 * @param array<string, mixed> $options Additional field options.
	 *
	 * @return static The current instance for chaining.
	 */
	public function field( string $type, string $id, string $label, array $options = [] ): static {
		$this->fields[ $id ] = array_merge( [
			'type'  => $type,
			'id'    => $id,
			'label' => $label,
		], $options );

		return $this;
	}

	/**
	 * Get all fields.
	 *
	 * @return array<string, array<string, mixed>> All fields.
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * Get the field registry instance.
	 *
	 * @return FieldRegistryInterface|null
	 */
	public function getFieldRegistry(): ?FieldRegistryInterface {
		return $this->fieldRegistry;
	}

	/**
	 * Render all fields for the widget form using the Field System.
	 *
	 * @param array<string, mixed> $instance Current instance values.
	 * @param WidgetAdapter $adapter Widget adapter for field IDs/names.
	 *
	 * @return string Rendered HTML for all fields.
	 */
	public function renderFields( array $instance, WidgetAdapter $adapter ): string {
		$output = '<div class="wp-jarvis-widget-container">' . "\n";

		foreach ( $this->fields as $fieldId => $field ) {
			$output .= $this->renderField( $fieldId, $field, $instance, $adapter );
		}
		$output .= '</div>' . "\n";

		return $output;
	}

	/**
	 * Render a single field using the Field System.
	 *
	 * @param string $fieldId Field identifier.
	 * @param array<string, mixed> $field Field configuration.
	 * @param array<string, mixed> $instance Current instance values.
	 * @param WidgetAdapter $adapter Widget adapter for field IDs/names.
	 *
	 * @return string Rendered HTML for the field.
	 */
	public function renderField( string $fieldId, array $field, array $instance, WidgetAdapter $adapter ): string {
		$fieldType  = $field['type'] ?? 'text';
		$fieldLabel = $field['label'] ?? '';
		$default    = $field['default'] ?? '';
		$value      = $instance[ $fieldId ] ?? $default;

		// Get or create field instance from registry
		$fieldInstance = $this->fieldRegistry->create( $fieldType, $field );

		// Prepare field config with widget-specific IDs/names
		$fieldConfig = array_merge( $field, [
			'id'     => $adapter->fieldId( $fieldId ),
			'name'   => $adapter->fieldName( $fieldId ),
			'label'  => $fieldLabel,
			'layout' => $field['layout'] ?? 'stacked',
		] );

		// Render field using the Field System
		return '<div class="wp-jarvis-widget-field">' . $fieldInstance->render( $fieldConfig, $value, 'widget' ) . '</div>';
	}

	/**
	 * Sanitize all field values using the Field System.
	 *
	 * @param array<string, mixed> $newInstance New instance values.
	 * @param array<string, mixed> $oldInstance Old instance values.
	 *
	 * @return array<string, mixed> Sanitized values.
	 */
	public function sanitizeFields( array $newInstance, array $oldInstance ): array {
		$instance = [];

		foreach ( $this->fields as $fieldId => $field ) {
			$type  = $field['type'] ?? 'text';
			$value = $newInstance[ $fieldId ] ?? ( $field['default'] ?? '' );

			// Use Field System for sanitization
			$fieldInstance        = $this->fieldRegistry->create( $type, $field );
			$instance[ $fieldId ] = $fieldInstance->prepare( $value, $field );
		}

		return $instance;
	}

	/**
	 * Set the text domain.
	 *
	 * @param string $textDomain Text domain.
	 *
	 * @return static
	 */
	public function textDomain( string $textDomain ): static {
		$this->textDomain = $textDomain;

		return $this;
	}

	/**
	 * Set the render callback.
	 *
	 * Callback signature: function (array $args, array $instance): string
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
	 * Set the form callback.
	 *
	 * Callback signature: function (array $instance): string
	 *
	 * @param callable $callback Form callback.
	 *
	 * @return static
	 */
	public function form( callable $callback ): static {
		$this->formCallback = $callback;

		return $this;
	}

	/**
	 * Set the update callback.
	 *
	 * Callback signature: function (array $newInstance, array $oldInstance): array
	 *
	 * @param callable $callback Update callback.
	 *
	 * @return static
	 */
	public function update( callable $callback ): static {
		$this->updateCallback = $callback;

		return $this;
	}

	/**
	 * Get the widget ID.
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * Get the widget name.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the widget description.
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Get the CSS class name.
	 *
	 * @return string
	 */
	public function getClassname(): string {
		return $this->classname;
	}

	/**
	 * Get the default values.
	 *
	 * @return array<string, mixed>
	 */
	public function getDefaults(): array {
		return $this->defaults;
	}

	/**
	 * Get the render callback.
	 *
	 * @return callable|null
	 */
	public function getRenderCallback(): ?callable {
		return $this->renderCallback;
	}

	/**
	 * Get the form callback.
	 *
	 * @return callable|null
	 */
	public function getFormCallback(): ?callable {
		return $this->formCallback;
	}

	/**
	 * Get the update callback.
	 *
	 * @return callable|null
	 */
	public function getUpdateCallback(): ?callable {
		return $this->updateCallback;
	}

	/**
	 * Get widget options for WP_Widget constructor.
	 *
	 * @return array<string, mixed>
	 */
	public function getWidgetOptions(): array {
		return [
			'classname'   => $this->classname,
			'description' => $this->description,
		];
	}

	/**
	 * Get control options for WP_Widget constructor.
	 *
	 * @return array<string, mixed>
	 */
	public function getControlOptions(): array {
		return $this->controlOptions;
	}

	/**
	 * Convert to array representation.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'id'              => $this->id,
			'name'            => $this->name,
			'description'     => $this->description,
			'classname'       => $this->classname,
			'defaults'        => $this->defaults,
			'control_options' => $this->controlOptions,
		];
	}

	/**
	 * Register the widget with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		$adapter = new WidgetAdapter( $this );

		// Check if widgets_init has already fired
		if ( Hooks::didAction( 'widgets_init' ) ) {
			// Register immediately since hook already fired
			register_widget( $adapter );
		} else {
			// Hook for normal registration
			Hooks::action( 'widgets_init', static function () use ( $adapter ) {
				register_widget( $adapter );
			} );
		}
	}
}
