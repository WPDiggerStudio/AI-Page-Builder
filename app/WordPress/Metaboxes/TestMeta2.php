<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Metaboxes;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Content\Metabox as MetaboxBuilder;

/**
 * TestMeta2 Metabox
 *
 * Manages Test Meta2 metabox for post-editing.
 *
 * @package BraCalculator\App\WordPress\Metaboxes
 */
class TestMeta2 {
	/**
	 * The metabox ID.
	 */
	public const ID = 'test_meta2';

	/**
	 * The metabox title.
	 */
	public const TITLE = 'Test Meta2';

	/**
	 * Post-types to attach to.
	 */
	public const POST_TYPES = [ 'test_article' ];

	/**
	 * The metabox builder instance.
	 *
	 * @var MetaboxBuilder|null
	 */
	private ?MetaboxBuilder $metabox = null;

	/**
	 * Define metabox fields.
	 *
	 * Each field: type, id, label, and optional description/options.
	 *
	 * For column layouts, add 'row' and 'col_class':
	 *   'row' => 'row_name' // Fields with same row name appear side-by-side
	 *   'col_class' => 'wpj-col-half' // Column width (wpj-col-half, wpj-col-third, wpj-col-quarter)
	 *
	 * @return array<int, array<string, mixed>> Array of field definitions.
	 */
	protected function fields(): array {
		return [
			[
				'type'        => 'text',
				'id'          => 'subtitle',
				'label'       => __( 'Subtitle', 'bra-calculator' ),
				'description' => __( 'Enter a subtitle for this content.', 'bra-calculator' ),
				'row'         => 'abc',
				'col_class'   => 'wpj-col-half',
			],
			[
				'type'        => 'textarea',
				'id'          => 'description',
				'label'       => __( 'Description', 'bra-calculator' ),
				'description' => __( 'Enter a detailed description.', 'bra-calculator' ),
				'rows'        => 4,
				'row'         => 'abc',
				'col_class'   => 'wpj-col-half'
			],
			[
				'type'    => 'select',
				'id'      => 'status',
				'label'   => __( 'Status', 'bra-calculator' ),
				'options' => [
					'active'   => __( 'Active', 'bra-calculator' ),
					'inactive' => __( 'Inactive', 'bra-calculator' ),
				],
			],
			[
				'type'  => 'checkbox',
				'id'    => 'featured',
				'label' => __( 'Featured', 'bra-calculator' ),
			],
			[
				'type'  => 'location',
				'id'    => 'location_field',
				'label' => __( 'Location (Map)', 'bra-calculator' ),
			],
		];
	}

	/**
	 * Define tabs for the metabox (optional).
	 *
	 * List field IDs - row grouping is handled automatically.
	 *
	 * @return array<int, array<string, mixed>> Array of tab definitions.
	 */
	protected function tabs(): array {
		//return [];
		// Example with tabs:
		return [
			[
				'id'     => 'general',
				'title'  => __( 'General', 'bra-calculator' ),
				'icon'   => 'dashicons-admin-generic',
				'fields' => [ 'subtitle', 'description' ],
			],
			[
				'id'     => 'settings',
				'title'  => __( 'Settings', 'bra-calculator' ),
				'icon'   => 'dashicons-admin-settings',
				'fields' => [ 'status', 'featured', 'location_field' ],
			],
		];
	}

	/**
	 * Whether to use vertical tabs' layout.
	 *
	 * @return bool True for vertical tabs, false for horizontal.
	 */
	protected function verticalTabs(): bool {
		return true;
	}

	/**
	 * Register metabox.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function register(): void {
		$this->metabox = MetaboxBuilder::make( self::ID, __( self::TITLE, 'bra-calculator' ) )
		                               ->forPostTypes( ...self::POST_TYPES )
		                               ->context( 'normal' )
		                               ->priority( 'default' );

		$fields  = $this->fields();
		$tabs    = $this->tabs();
		$hasTabs = ! empty( $tabs );

		// Build field lookup and row groups
		$fieldLookup = [];
		$rowGroups   = [];

		foreach ( $fields as $field ) {
			$fieldLookup[ $field['id'] ] = $field;
			if ( ! empty( $field['row'] ) ) {
				$rowGroups[ $field['row'] ][] = $field['id'];
			}
		}

		// Register all fields first
		foreach ( $fields as $field ) {
			$this->metabox->field(
				$field['type'],
				$field['id'],
				$field['label'],
				array_diff_key( $field, array_flip( [ 'type', 'id', 'label', 'row', 'col_class' ] ) )
			);
		}

		// Process tabs - replace field IDs with row IDs where applicable
		if ( $hasTabs ) {
			$processedTabs = [];
			$rowIndex      = 0;

			foreach ( $tabs as $tab ) {
				$processedFields  = [];
				$processedRowKeys = [];

				foreach ( $tab['fields'] as $fieldId ) {
					if ( ! isset( $fieldLookup[ $fieldId ] ) ) {
						continue;
					}

					$field = $fieldLookup[ $fieldId ];

					if ( ! empty( $field['row'] ) ) {
						$rowKey = $field['row'];

						if ( in_array( $rowKey, $processedRowKeys, true ) ) {
							continue;
						}

						$processedRowKeys[] = $rowKey;

						$rowColumns = [];
						foreach ( $rowGroups[ $rowKey ] as $rowFieldId ) {
							$rowField     = $fieldLookup[ $rowFieldId ];
							$rowColumns[] = [
								$rowFieldId,
								'class' => $rowField['col_class'] ?? 'wpj-col',
							];
						}
						$this->metabox->row( $rowColumns );
						$processedFields[] = 'row_' . $rowIndex;
						$rowIndex ++;
					} else {
						$processedFields[] = $fieldId;
					}
				}

				$processedTabs[] = [
					'id'     => $tab['id'],
					'title'  => $tab['title'],
					'icon'   => $tab['icon'] ?? '',
					'fields' => $processedFields,
				];
			}

			if ( $this->verticalTabs() ) {
				$this->metabox->verticalTabs();
			}

			foreach ( $processedTabs as $tab ) {
				$this->metabox->tab(
					$tab['id'],
					$tab['title'],
					$tab['fields'],
					$tab['icon']
				);
			}
		} else {
			// No tabs - create rows for all row-grouped fields
			$processedRows = [];
			foreach ( $rowGroups as $rowKey => $rowFieldIds ) {
				if ( in_array( $rowKey, $processedRows, true ) ) {
					continue;
				}
				$processedRows[] = $rowKey;

				$rowColumns = [];
				foreach ( $rowFieldIds as $rowFieldId ) {
					$rowField     = $fieldLookup[ $rowFieldId ];
					$rowColumns[] = [
						$rowFieldId,
						'class' => $rowField['col_class'] ?? 'wpj-col',
					];
				}
				$this->metabox->row( $rowColumns );
			}
		}

		Hooks::action( 'add_meta_boxes', [ $this, 'registerMetabox' ] );
	}

	/**
	 * Register metabox with WordPress.
	 *
	 * @return void
	 */
	public function registerMetabox(): void {
		$this->metabox?->register();
	}

	/**
	 * Get a field value for a post.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 * @param mixed $default Default value.
	 *
	 * @return mixed Field value.
	 */
	public static function get( int $postId, string $key, mixed $default = null ): mixed {
		$value = get_post_meta( $postId, '_' . $key, true );

		return $value !== '' ? $value : $default;
	}

	/**
	 * Set a field value for a post.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 * @param mixed $value Value to set.
	 *
	 * @return bool|int Meta-ID on success, false on failure.
	 */
	public static function set( int $postId, string $key, mixed $value ): bool|int {
		return update_post_meta( $postId, '_' . $key, $value );
	}

	/**
	 * Delete field value for a post.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete( int $postId, string $key ): bool {
		return delete_post_meta( $postId, '_' . $key );
	}

	/**
	 * Check if a field has a truthy value.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 *
	 * @return bool True if value is truthy.
	 */
	public static function isTrue( int $postId, string $key ): bool {
		return (bool) self::get( $postId, $key, false );
	}

	/**
	 * Get multiple field values for a post.
	 *
	 * @param int $postId Post ID.
	 * @param array<string> $keys Field keys to retrieve.
	 *
	 * @return array<string, mixed> Array of field values.
	 */
	public static function getMany( int $postId, array $keys ): array {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = self::get( $postId, $key );
		}

		return $values;
	}

	/**
	 * Set multiple field values for a post.
	 *
	 * @param int $postId Post ID.
	 * @param array<string, mixed> $data Key-value pairs to set.
	 *
	 * @return void
	 */
	public static function setMany( int $postId, array $data ): void {
		foreach ( $data as $key => $value ) {
			self::set( $postId, $key, $value );
		}
	}

	/**
	 * Get the metabox builder instance.
	 *
	 * @return MetaboxBuilder|null The metabox builder instance.
	 */
	public function getMetabox(): ?MetaboxBuilder {
		return $this->metabox;
	}

	/**
	 * Get all fields configuration.
	 *
	 * @return array<string, array<string, mixed>> All fields configuration.
	 */
	public function getFields(): array {
		return $this->metabox?->getFields() ?? [];
	}
}
