<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Admin\Settings;

use WPJarvis\Framework\WP\Admin\Settings as SettingsBuilder;

/**
 * TestOptions Settings Page
 *
 * Manages the Test Options Settings page.
 *
 * @package BraCalculator\App\WordPress\Admin\Settings
 */
class TestOptions {
	/**
	 * Settings page slug.
	 */
	public const SLUG = 'test-options';

	/**
	 * Option group.
	 */
	public const OPTION_GROUP = 'test_options_options';

	/**
	 * Capability required to access settings.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Parent menu slug.
	 */
	public const PARENT = '';

	/**
	 * The settings builder instance.
	 *
	 * @var SettingsBuilder|null
	 */
	private ?SettingsBuilder $settings = null;

	/**
	 * Define settings fields.
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
			// General Settings Section
			[
				'type'  => 'title',
				'id'    => 'general_section',
				'label' => __( 'General Settings', 'bra-calculator' ),
			],
			[
				'type'        => 'text',
				'id'          => 'site_name',
				'label'       => __( 'Site Name', 'bra-calculator' ),
				'description' => __( 'Enter the site name.', 'bra-calculator' ),
				'default'     => get_bloginfo( 'name' ),
			],
			[
				'type'        => 'textarea',
				'id'          => 'description',
				'label'       => __( 'Description', 'bra-calculator' ),
				'description' => __( 'Enter a description.', 'bra-calculator' ),
			],
			[
				'type'        => 'checkbox',
				'id'          => 'enabled',
				'label'       => __( 'Enable Feature', 'bra-calculator' ),
				'description' => __( 'Check to enable this feature.', 'bra-calculator' ),
			],

			// Display Settings Section
			[
				'type'  => 'title',
				'id'    => 'display_section',
				'label' => __( 'Display Settings', 'bra-calculator' ),
			],
			[
				'type'    => 'number',
				'id'      => 'items_per_page',
				'label'   => __( 'Items Per Page', 'bra-calculator' ),
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
			],
			[
				'type'    => 'select',
				'id'      => 'layout',
				'label'   => __( 'Layout', 'bra-calculator' ),
				'options' => [
					'grid'    => __( 'Grid', 'bra-calculator' ),
					'list'    => __( 'List', 'bra-calculator' ),
					'masonry' => __( 'Masonry', 'bra-calculator' ),
				],
				'default' => 'grid',
			],
			[
				'type'        => 'color_picker',
				'id'          => 'primary_color',
				'label'       => __( 'Primary Color', 'bra-calculator' ),
				'description' => __( 'Choose the primary color.', 'bra-calculator' ),
				'default'     => '#4a90d9',
			],
		];
	}

	/**
	 * Define tabs for the settings page (optional).
	 *
	 * List field IDs - row grouping is handled automatically.
	 *
	 * @return array<int, array<string, mixed>> Array of tab definitions.
	 */
	protected function tabs(): array {
		return [];
		// Example with tabs:
		// return [
		//     [
		//         'id'     => 'general',
		//         'title'  => __( 'General', 'bra-calculator' ),
		//         'icon'   => 'dashicons-admin-generic',
		//         'fields' => [ 'general_section', 'site_name', 'description', 'enabled' ],
		//     ],
		//     [
		//         'id'     => 'display',
		//         'title'  => __( 'Display', 'bra-calculator' ),
		//         'icon'   => 'dashicons-admin-appearance',
		//         'fields' => [ 'display_section', 'items_per_page', 'layout', 'primary_color' ],
		//     ],
		// ];
	}

	/**
	 * Whether to use vertical tabs' layout.
	 *
	 * @return bool True for vertical tabs, false for horizontal.
	 */
	protected function verticalTabs(): bool {
		return false;
	}

	/**
	 * Register the settings page.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->settings = SettingsBuilder::make( self::SLUG, __( 'Test Options Settings', 'bra-calculator' ) )
		                                 ->capability( self::CAPABILITY );

		if ( ! empty( self::PARENT ) ) {
			$this->settings->parent( self::PARENT );
		}

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

		// Register all fields
		foreach ( $fields as $field ) {
			$this->settings->field(
				$field['type'],
				$field['id'],
				$field['label'],
				array_diff_key( $field, array_flip( [ 'type', 'id', 'label', 'row', 'col_class' ] ) )
			);
		}

		// Process tabs
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
						$this->settings->row( $rowColumns );
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
				$this->settings->verticalTabs();
			}

			foreach ( $processedTabs as $tab ) {
				$this->settings->tab(
					$tab['id'],
					$tab['title'],
					$tab['fields'],
					$tab['icon']
				);
			}
		} else {
			// No tabs - create rows for row-grouped fields
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
				$this->settings->row( $rowColumns );
			}
		}

		$this->settings->register();

		// Enqueue admin styles
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStyles' ] );
	}

	/**
	 * Enqueue admin styles for our settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueStyles( string $hook ): void {
		if ( ! str_contains( $hook, self::SLUG ) ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', $this->getInlineStyles() );
	}

	/**
	 * Get inline CSS styles for this settings page.
	 *
	 * @return string CSS styles.
	 */
	protected function getInlineStyles(): string {
		return '
			/* Settings Page Container */
			.wp-jarvis-settings {
				background: #fff;
				border-radius: 12px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.08);
				border: 1px solid #e5e7eb;
				padding: 20px;
				margin: 20px 20px 20px 0;
			}

			.wp-jarvis-settings .settings-wrapper {
				display: block;
			}

			/* Fields */
			.wp-jarvis-settings .wpj-field-title {
			    padding-bottom: 0 !important;
			}

			.wp-jarvis-settings .wpj-field {
				margin-bottom: 24px;
			}

			.wp-jarvis-settings .wpj-field-title {
				border-bottom: 1px solid #e5e7eb;
				padding-bottom: 12px;
				margin-top: 32px;
				margin-bottom: 20px;
			}

			.wp-jarvis-settings .wpj-field-title:first-child {
				margin-top: 0;
			}

			.wp-jarvis-settings .wpj-field-row {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
			}

			.wp-jarvis-settings .wpj-col-half {
				flex: 1;
				min-width: 250px;
			}

			.wp-jarvis-settings .wpj-col-third {
				flex: 0 0 calc(33.333% - 14px);
				min-width: 200px;
			}

			.wp-jarvis-settings .wpj-col-quarter {
				flex: 0 0 calc(25% - 15px);
				min-width: 150px;
			}

			/* Form Controls */
			.wp-jarvis-settings input[type="text"],
			.wp-jarvis-settings input[type="email"],
			.wp-jarvis-settings input[type="url"],
			.wp-jarvis-settings input[type="number"],
			.wp-jarvis-settings select,
			.wp-jarvis-settings textarea {
				width: 100%;
				padding: 10px 14px;
				border: 1px solid #d1d5db;
				border-radius: 8px;
				font-size: 14px;
			}

			.wp-jarvis-settings input:focus,
			.wp-jarvis-settings select:focus,
			.wp-jarvis-settings textarea:focus {
				border-color: #667eea;
				box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
				outline: none;
			}

			/* Vertical Tabs Layout */
			.wp-jarvis-settings.wpj-tabs-vertical {
				display: flex;
				gap: 0;
				padding: 0;
				overflow: hidden;
			}

			.wp-jarvis-settings.wpj-tabs-vertical .settings-wrapper {
				display: flex;
				flex: 1;
			}

			.wp-jarvis-settings .wpj-tabs {
				width: 220px;
				background: #f9fafb;
				border-right: 1px solid #e5e7eb;
				padding: 16px 0;
				flex-shrink: 0;
			}

			.wp-jarvis-settings .wpj-tab {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 14px 20px;
				color: #4b5563;
				cursor: pointer;
				border-left: 3px solid transparent;
				font-weight: 500;
				transition: all 0.2s ease;
			}

			.wp-jarvis-settings .wpj-tab:hover {
				background: #f3f4f6;
				color: #374151;
			}

			.wp-jarvis-settings .wpj-tab.active {
				background: #fff;
				color: #667eea;
				border-left-color: #667eea;
			}

			.wp-jarvis-settings .wpj-tabs-content {
				flex: 1;
				padding: 32px;
			}

			.wp-jarvis-settings .wpj-tab-content {
				display: none;
			}

			.wp-jarvis-settings .wpj-tab-content-active {
				display: block;
			}

			/* Submit Button */
			.wp-jarvis-settings .button-primary {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				border: none;
				padding: 10px 24px;
				font-size: 14px;
				font-weight: 600;
				border-radius: 8px;
			}

			.wp-jarvis-settings .button-primary:hover {
				background: linear-gradient(135deg, #5a67d8 0%, #6b4190 100%);
			}

			.wp-jarvis-settings p.submit {
			    text-align: right;
			    max-width: 100%;
			    margin-top: 20px;
			    padding-top: 10px;
			}

			/* Responsive */
			@media (max-width: 782px) {
				.wp-jarvis-settings.wpj-tabs-vertical {
					flex-direction: column;
				}
				.wp-jarvis-settings.wpj-tabs-vertical .settings-wrapper {
					flex-direction: column;
				}
				.wp-jarvis-settings .wpj-tabs {
					width: 100%;
					border-right: none;
					border-bottom: 1px solid #e5e7eb;
					display: flex;
					flex-wrap: wrap;
					padding: 12px;
				}
				.wp-jarvis-settings .wpj-tab {
					border-left: none;
					border-bottom: 3px solid transparent;
				}
				.wp-jarvis-settings .wpj-tab.active {
					border-bottom-color: #667eea;
					border-left-color: transparent;
				}
			}
		';
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed $default Default value.
	 *
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$values = get_option( self::SLUG . '_settings', [] );

		return $values[ $key ] ?? $default;
	}

	/**
	 * Set a setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed $value Value to set.
	 *
	 * @return bool
	 */
	public static function set( string $key, mixed $value ): bool {
		$values         = get_option( self::SLUG . '_settings', [] );
		$values[ $key ] = $value;

		return update_option( self::SLUG . '_settings', $values );
	}

	/**
	 * Delete a setting.
	 *
	 * @param string $key Setting key.
	 *
	 * @return bool
	 */
	public static function delete( string $key ): bool {
		$values = get_option( self::SLUG . '_settings', [] );
		unset( $values[ $key ] );

		return update_option( self::SLUG . '_settings', $values );
	}

	/**
	 * Get all settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return get_option( self::SLUG . '_settings', [] );
	}

	/**
	 * Check if the feature is enabled.
	 *
	 * @return bool
	 */
	public static function isEnabled(): bool {
		return (bool) self::get( 'enabled', false );
	}

	/**
	 * Get the settings page URL.
	 *
	 * @return string
	 */
	public static function url(): string {
		if ( empty( self::PARENT ) ) {
			return admin_url( 'options-general.php?page=' . self::SLUG );
		}

		if ( str_contains( self::PARENT, '.php' ) ) {
			return admin_url( self::PARENT . '?page=' . self::SLUG );
		}

		return admin_url( 'admin.php?page=' . self::SLUG );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @return void
	 */
	public static function reset(): void {
		delete_option( self::SLUG . '_settings' );
	}
}
