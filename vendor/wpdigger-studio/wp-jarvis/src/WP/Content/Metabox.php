<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Content;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldInterface;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;
use WPJarvis\Framework\WP\Fields\Security\Validator;
use WPJarvis\Framework\WP\Fields\Storage\PostMetaStorage;

/**
 * Metabox - Fluent builder for post-meta boxes.
 *
 * Uses the centralized Field System for rendering and managing fields.
 *
 * @package WPJarvis\Framework\WP\Content
 */
class Metabox {
	/**
	 * The metabox ID.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * The metabox title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * The post types to add metabox to.
	 *
	 * @var array<string>
	 */
	private array $postTypes = [];

	/**
	 * The context (normal, side, advanced).
	 *
	 * @var string
	 */
	private string $context = 'normal';

	/**
	 * The priority (high, core, default, low).
	 *
	 * @var string
	 */
	private string $priority = 'default';

	/**
	 * The fields' configuration.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $fields = [];

	/**
	 * Field registry instance.
	 *
	 * @var FieldRegistryInterface
	 */
	private FieldRegistryInterface $fieldRegistry;

	/**
	 * Storage instance.
	 *
	 * @var PostMetaStorage
	 */
	private PostMetaStorage $storage;

	/**
	 * Validator instance.
	 *
	 * @var Validator
	 */
	private Validator $validator;

	/**
	 * Field instance cache.
	 *
	 * @var array<string, FieldInterface>
	 */
	private array $fieldInstances = [];

	/**
	 * Tabs configuration.
	 *
	 * @var array<array<string, mixed>>
	 */
	private array $tabs = [];

	/**
	 * Whether to use vertical tabs.
	 *
	 * @var bool
	 */
	private bool $verticalTabs = false;

	/**
	 * Create a new Metabox instance.
	 *
	 * @param string $id The metabox ID.
	 * @param string $title The metabox title.
	 * @param FieldRegistryInterface|null $fieldRegistry Field registry instance.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct( string $id, string $title, ?FieldRegistryInterface $fieldRegistry = null ) {
		$this->id            = $id;
		$this->title         = $title;
		$this->fieldRegistry = $fieldRegistry ?? wpj_app( 'field_registry' );
		$this->storage       = new PostMetaStorage();
		$this->validator     = new Validator();
	}

	/**
	 * Create a new Metabox instance.
	 *
	 * @param string $id The metabox ID.
	 * @param string $title The metabox title.
	 *
	 * @return static The new Metabox instance.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function make( string $id, string $title ): static {
		return new static( $id, $title );
	}

	/**
	 * Set the post-types for this metabox.
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
	 * Set the metabox context.
	 *
	 * @param string $context The context (normal, side, advanced).
	 *
	 * @return static The current instance for chaining.
	 */
	public function context( string $context ): static {
		$this->context = $context;

		return $this;
	}

	/**
	 * Set the metabox priority.
	 *
	 * @param string $priority The priority (high, core, default, low).
	 *
	 * @return static The current instance for chaining.
	 */
	public function priority( string $priority ): static {
		$this->priority = $priority;

		return $this;
	}

	/**
	 * Add a field to this metabox.
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
	 * Add a tab to this metabox (CMB2-tabs style).
	 *
	 * @param string $id Tab ID.
	 * @param string $title Tab title.
	 * @param array<string> $fields Array of field IDs in this tab.
	 * @param string $icon Optional dashicon class (e.g., 'dashicons-admin-site').
	 *
	 * @return static The current instance for chaining.
	 */
	public function tab( string $id, string $title, array $fields, string $icon = '' ): static {
		$this->tabs[] = [
			'id'     => $id,
			'title'  => $title,
			'icon'   => $icon,
			'fields' => $fields,
		];

		return $this;
	}

	/**
	 * Enable vertical tabs layout.
	 *
	 * @param bool $vertical Whether to use vertical tabs.
	 *
	 * @return static The current instance for chaining.
	 */
	public function verticalTabs( bool $vertical = true ): static {
		$this->verticalTabs = $vertical;

		return $this;
	}

	/**
	 * Add a row of fields in columns (CMB2-grid style).
	 *
	 * @param array<int, array<string, mixed>|string> $columns Array of field configs or field IDs with optional column classes.
	 *
	 * @return static The current instance for chaining.
	 */
	public function row( array $columns ): static {
		$rowFields = [];

		foreach ( $columns as $column ) {
			if ( is_string( $column ) ) {
				// Just field ID reference
				$rowFields[] = [ 'field_id' => $column, 'class' => 'wpj-col' ];
			} elseif ( is_array( $column ) ) {
				if ( isset( $column['type'] ) ) {
					// Inline field definition
					$fieldId = $column['id'] ?? uniqid( 'field_', true );
					$this->field( $column['type'], $fieldId, $column['label'] ?? '', $column );
					$colClass    = $column['col_class'] ?? 'wpj-col';
					$rowFields[] = [ 'field_id' => $fieldId, 'class' => $colClass ];
				} else {
					// Field ID reference with class
					$fieldId     = $column[0] ?? $column['field_id'] ?? '';
					$colClass    = $column['class'] ?? $column[1] ?? 'wpj-col';
					$rowFields[] = [ 'field_id' => $fieldId, 'class' => $colClass ];
				}
			}
		}

		// Store row as a special entry
		$rowId                  = 'row_' . count( array_filter( $this->fields, static fn( $f ) => isset( $f['_is_row'] ) ) );
		$this->fields[ $rowId ] = [
			'_is_row' => true,
			'columns' => $rowFields,
		];

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
	 * Register the metabox with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		foreach ( $this->postTypes as $postType ) {
			add_meta_box(
				$this->id,
				$this->title,
				[ $this, 'render' ],
				$postType,
				$this->context,
				$this->priority
			);
		}

		Hooks::action( 'save_post', [ $this, 'save' ], 10, 2 );
	}

	/**
	 * Render the metabox.
	 *
	 * @param \WP_Post $post The current post-object.
	 *
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		\wp_nonce_field( $this->id . '_nonce_action', $this->id . '_nonce_field' );

		$hasTabs      = ! empty( $this->tabs );
		$wrapperClass = 'wp-jarvis-metabox';

		if ( $hasTabs ) {
			$wrapperClass .= ' wpj-tabs-wrap wpj-tabs-' . ( $this->verticalTabs ? 'vertical' : 'horizontal' );
		}

		echo '<div class="' . esc_attr( $wrapperClass ) . '">';

		// Render tabs navigation
		if ( $hasTabs ) {
			echo '<div class="wpj-tabs">';
			$firstTab = true;
			foreach ( $this->tabs as $tab ) {
				$activeClass = $firstTab ? ' active' : '';
				$tabId       = esc_attr( $this->id . '-tab-' . $tab['id'] );

				echo '<div id="' . $tabId . '" class="wpj-tab' . $activeClass . '" data-tab-id="' . esc_attr( $tab['id'] ) . '">';

				if ( ! empty( $tab['icon'] ) ) {
					$iconClass = strpos( $tab['icon'], 'dashicons' ) !== false ? 'dashicons ' . $tab['icon'] : $tab['icon'];
					echo '<span class="wpj-tab-icon"><span class="' . esc_attr( $iconClass ) . '"></span></span>';
				}

				if ( ! empty( $tab['title'] ) ) {
					echo '<span class="wpj-tab-title">' . esc_html( $tab['title'] ) . '</span>';
				}

				echo '</div>';
				$firstTab = false;
			}
			echo '</div>';

			// Render tab content panels
			echo '<div class="wpj-tabs-content">';
			$firstTab = true;
			foreach ( $this->tabs as $tab ) {
				$activeClass = $firstTab ? ' wpj-tab-content-active' : '';
				echo '<div class="wpj-tab-content' . $activeClass . '" data-tab-id="' . esc_attr( $tab['id'] ) . '">';

				foreach ( $tab['fields'] as $fieldId ) {
					if ( isset( $this->fields[ $fieldId ] ) ) {
						$field = $this->fields[ $fieldId ];
						if ( ! empty( $field['_is_row'] ) ) {
							echo '<div class="wpj-field-row">';
							foreach ( $field['columns'] as $column ) {
								$colFieldId = $column['field_id'];
								$colClass   = $column['class'];
								if ( isset( $this->fields[ $colFieldId ] ) ) {
									echo '<div class="' . esc_attr( $colClass ) . '">';
									$this->renderField( $this->fields[ $colFieldId ], $post );
									echo '</div>';
								}
							}
							echo '</div>';
						} else {
							$this->renderField( $field, $post );
						}
					}
				}

				echo '</div>';
				$firstTab = false;
			}
			echo '</div>';
		} else {
			// Standard non-tabbed rendering
			$renderedFields = [];

			foreach ( $this->fields as $fieldId => $field ) {
				if ( in_array( $fieldId, $renderedFields, true ) ) {
					continue;
				}

				if ( ! empty( $field['_is_row'] ) ) {
					echo '<div class="wpj-field-row">';
					foreach ( $field['columns'] as $column ) {
						$colFieldId = $column['field_id'];
						$colClass   = $column['class'];

						if ( isset( $this->fields[ $colFieldId ] ) ) {
							echo '<div class="' . esc_attr( $colClass ) . '">';
							$this->renderField( $this->fields[ $colFieldId ], $post );
							echo '</div>';
							$renderedFields[] = $colFieldId;
						}
					}
					echo '</div>';
				} else {
					$this->renderField( $field, $post );
				}
			}
		}

		echo '</div>';
	}


	/**
	 * Render a field.
	 *
	 * @param array<string, mixed> $field The field configuration.
	 * @param \WP_Post $post The current post-object.
	 *
	 * @return void
	 */
	private function renderField( array $field, \WP_Post $post ): void {
		$fieldType    = $field['type'] ?? 'text';
		$fieldId      = $field['id'];
		$fieldName    = $this->id . '_' . $fieldId;
		$fieldLabel   = $field['label'] ?? '';
		$fieldOptions = $field;

		// Get the current value from post-meta
		$metaKey = '_' . $fieldId;
		$value   = \get_post_meta( $post->ID, $metaKey, true );

		// Get or create a field instance
		if ( ! isset( $this->fieldInstances[ $fieldId ] ) ) {
			$fieldInstance                    = $this->fieldRegistry->create( $fieldType, $fieldOptions );
			$this->fieldInstances[ $fieldId ] = $fieldInstance;
		} else {
			$fieldInstance = $this->fieldInstances[ $fieldId ];
		}

		// Prepare field config with name and ID
		// Set layout to inline by default for metabox context
		$fieldConfig = array_merge( $fieldOptions, [
			'id'     => $fieldName,
			'name'   => $fieldName,
			'label'  => $fieldLabel,
			'layout' => $fieldOptions['layout'] ?? 'inline',
		] );

		// Render a field using the field system's wrapper
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $fieldInstance->render( $fieldConfig, $value, 'metabox' );
	}

	/**
	 * Save the metabox data.
	 *
	 * @param int $postId The post-ID.
	 *
	 * @return void
	 */
	public function save( int $postId ): void {
		// Verify nonce
		$nonceField  = $this->id . '_nonce_field';
		$nonceAction = $this->id . '_nonce_action';
		$nonceValue  = $_POST[ $nonceField ] ?? '';

		if ( ! $this->validator->verifyNonce( $nonceAction, $nonceField, $nonceValue ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! $this->validator->checkCapability( 'edit_post', $postId ) ) {
			return;
		}

		// Save each field
		foreach ( $this->fields as $field ) {
			$fieldId   = $field['id'];
			$fieldName = $this->id . '_' . $fieldId;
			$metaKey   = '_' . $fieldId;

			if ( ! isset( $_POST[ $fieldName ] ) ) {
				continue;
			}

			$value = $_POST[ $fieldName ];

			// Get field instance and prepare value
			$fieldInstance = $this->fieldInstances[ $fieldId ] ?? $this->fieldRegistry->create( $field['type'], $field );
			$sanitized     = $fieldInstance->prepare( $value, $field );

			// Save sanitized value
			$this->storage->set( (string) $postId, $metaKey, $sanitized, 'metabox' );

			// Validate
			$validation = $this->validator->validate( $sanitized, $field, 'metabox' );
			if ( ! $validation['valid'] ) {
				// Add admin notice for validation errors
				\add_settings_error(
					$this->id . '_' . $fieldId,
					sprintf( __( 'Field "%s" validation failed: %s', 'wp-jarvis' ), $field['label'] ?? $fieldId, implode( ', ', $validation['errors'] ) ),
					'error'
				);
			}
		}
	}
}
