<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Admin;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldInterface;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;

/**
 * Settings - Fluent builder for settings pages.
 *
 * Uses centralized Field System for rendering and managing settings fields.
 * Supports tabs, rows, and the full field type system.
 */
class Settings {
    /**
     * The settings page slug.
     *
     * @var string
     */
    private string $slug;

    /**
     * The settings page title.
     *
     * @var string
     */
    private string $title;

    /**
     * The required capability.
     *
     * @var string
     */
    private string $capability = 'manage_options';

    /**
     * The option group name.
     *
     * @var string
     */
    private string $optionGroup;

    /**
     * The option name for storing all settings.
     *
     * @var string
     */
    private string $optionName;

    /**
     * The settings fields.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $fields = [];

    /**
     * Tab definitions.
     *
     * @var array<int, array{id: string, title: string, icon: string, fields: array<string>}>
     */
    private array $tabs = [];

    /**
     * Whether to use vertical tabs.
     *
     * @var bool
     */
    private bool $verticalTabs = false;

    /**
     * Parent menu slug.
     *
     * @var string
     */
    private string $parentSlug = '';

    /**
     * Field registry instance.
     *
     * @var FieldRegistryInterface
     */
    private FieldRegistryInterface $fieldRegistry;

    /**
     * Field instance cache.
     *
     * @var array<string, FieldInterface>
     */
    private array $fieldInstances = [];

    /**
     * Create a new Settings instance.
     *
     * @param string $slug The settings page slug.
     * @param string $title The settings page title.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct( string $slug, string $title ) {
        $this->slug          = $slug;
        $this->title         = $title;
        $this->optionGroup   = $slug . '_options';
        $this->optionName    = $slug . '_settings';
        $this->fieldRegistry = wpj_app( 'field_registry' );
    }

    /**
     * Create a new Settings instance.
     *
     * @param string $slug The settings page slug.
     * @param string $title The settings page title.
     *
     * @return static The new Settings instance.
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function make( string $slug, string $title ): static {
        return new static( $slug, $title );
    }

    /**
     * Set the required capability.
     *
     * @param string $capability The required capability.
     *
     * @return static The current instance for chaining.
     */
    public function capability( string $capability ): static {
        $this->capability = $capability;

        return $this;
    }

    /**
     * Set the parent menu slug.
     *
     * @param string $parentSlug The parent menu slug.
     *
     * @return static The current instance for chaining.
     */
    public function parent( string $parentSlug ): static {
        $this->parentSlug = $parentSlug;

        return $this;
    }

    /**
     * Add a field to this settings page.
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
     * Add a tab to this settings page.
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
     * Add a row of fields in columns.
     *
     * @param array<int, array<string, mixed>|string> $columns Array of field configs or field IDs with optional column classes.
     *
     * @return static The current instance for chaining.
     */
    public function row( array $columns ): static {
        $rowFields = [];

        foreach ( $columns as $column ) {
            if ( is_string( $column ) ) {
                $rowFields[] = [ 'field_id' => $column, 'class' => 'wpj-col' ];
            } elseif ( is_array( $column ) ) {
                if ( isset( $column['type'] ) ) {
                    $fieldId = $column['id'] ?? uniqid( 'field_', true );
                    $this->field( $column['type'], $fieldId, $column['label'] ?? '', $column );
                    $colClass    = $column['col_class'] ?? 'wpj-col';
                    $rowFields[] = [ 'field_id' => $fieldId, 'class' => $colClass ];
                } else {
                    $fieldId     = $column[0] ?? $column['field_id'] ?? '';
                    $colClass    = $column['class'] ?? $column[1] ?? 'wpj-col';
                    $rowFields[] = [ 'field_id' => $fieldId, 'class' => $colClass ];
                }
            }
        }

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
     * Get or create a field instance.
     *
     * @param string $fieldId The field ID.
     * @param array<string, mixed> $fieldConfig The field configuration.
     *
     * @return FieldInterface The field instance.
     */
    private function getFieldInstance( string $fieldId, array $fieldConfig ): FieldInterface {
        if ( ! isset( $this->fieldInstances[ $fieldId ] ) ) {
            $fieldType                        = $fieldConfig['type'] ?? 'text';
            $this->fieldInstances[ $fieldId ] = $this->fieldRegistry->create( $fieldType, $fieldConfig );
        }

        return $this->fieldInstances[ $fieldId ];
    }

    /**
     * Render a single field.
     *
     * @param array<string, mixed> $field Field configuration.
     * @param array<string, mixed> $values All settings values.
     *
     * @return void
     */
    private function renderField( array $field, array $values ): void {
        $fieldId   = $field['id'];
        $fieldType = $field['type'] ?? 'text';
        $value     = $values[ $fieldId ] ?? ( $field['default'] ?? '' );

        // Get field instance from registry
        $fieldInstance = $this->getFieldInstance( $fieldId, $field );

        // Build field config
        $fieldConfig = array_merge( $field, [
                'id'     => $this->optionName . '[' . $fieldId . ']',
                'name'   => $this->optionName . '[' . $fieldId . ']',
                'label'  => $field['label'],
                'layout' => $field['layout'] ?? 'stacked',
        ] );

        echo '<div class="wpj-field wpj-field-' . esc_attr( $fieldType ) . '">';
        echo $fieldInstance->render( $fieldConfig, $value, 'settings' );
        echo '</div>';
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function render(): void {
        if ( ! current_user_can( $this->capability ) ) {
            return;
        }

        $values    = get_option( $this->optionName, [] );
        $hasTabs   = ! empty( $this->tabs );
        $wrapClass = 'wp-jarvis-settings';

        if ( $hasTabs ) {
            $wrapClass .= ' wpj-tabs-wrap wpj-tabs-' . ( $this->verticalTabs ? 'vertical' : 'horizontal' );
        }

        // Output built-in styles
        //$this->renderInlineStyles();

        echo '<div class="' . esc_attr( $wrapClass ) . '">';
        echo '<form method="post" action="options.php">';

        settings_fields( $this->optionGroup );
        echo '<div class="settings-wrapper">';
        if ( $hasTabs ) {
            // Render tabs navigation
            echo '<div class="wpj-tabs">';
            $firstTab = true;
            foreach ( $this->tabs as $tab ) {
                $activeClass = $firstTab ? ' active' : '';
                $tabId       = esc_attr( $this->slug . '-tab-' . $tab['id'] );

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

            // Render tab content
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
                                    $this->renderField( $this->fields[ $colFieldId ], $values );
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                        } else {
                            $this->renderField( $field, $values );
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
                            $this->renderField( $this->fields[ $colFieldId ], $values );
                            echo '</div>';
                            $renderedFields[] = $colFieldId;
                        }
                    }
                    echo '</div>';
                } else {
                    $this->renderField( $field, $values );
                }
            }
        }
        echo '</div>';

        submit_button( __( 'Save Changes', 'wp-jarvis' ) );
        echo '</form>';
        echo '</div>';

        // Add tabs JS
        if ( $hasTabs ) {
            $this->renderTabsScript();
        }
    }

    /**
     * Render the JavaScript for tabs functionality.
     *
     * @return void
     */
    private function renderTabsScript(): void {
        ?>
        <script>
            (function () {
                const tabs = document.querySelectorAll('.wp-jarvis-settings .wpj-tab');
                const contents = document.querySelectorAll('.wp-jarvis-settings .wpj-tab-content');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function () {
                        const tabId = this.getAttribute('data-tab-id');

                        // Update tab active state
                        tabs.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');

                        // Update content visibility
                        contents.forEach(c => {
                            if (c.getAttribute('data-tab-id') === tabId) {
                                c.classList.add('wpj-tab-content-active');
                            } else {
                                c.classList.remove('wpj-tab-content-active');
                            }
                        });
                    });
                });
            })();
        </script>
        <?php
    }

    /**
     * Register the settings with WordPress (including menu page).
     *
     * Use this for standalone settings pages.
     * For settings used within a Menu-registered page, use registerSettingsOnly().
     *
     * @return void
     */
    public function register(): void {
        Hooks::action( 'admin_menu', [ $this, 'addPage' ] );
        Hooks::action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    /**
     * Register only the settings without adding a menu page.
     *
     * Use this when the page is already registered by Menu builder.
     *
     * @return void
     */
    public function registerSettingsOnly(): void {
        Hooks::action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    /**
     * Add the settings page to WordPress admin.
     *
     * @return void
     */
    public function addPage(): void {
        if ( $this->parentSlug ) {
            add_submenu_page(
                    $this->parentSlug,
                    $this->title,
                    $this->title,
                    $this->capability,
                    $this->slug,
                    [ $this, 'render' ]
            );
        } else {
            add_options_page(
                    $this->title,
                    $this->title,
                    $this->capability,
                    $this->slug,
                    [ $this, 'render' ]
            );
        }
    }

    /**
     * Register the settings option.
     *
     * @return void
     */
    public function registerSettings(): void {
        register_setting(
                $this->optionGroup,
                $this->optionName,
                [
                        'sanitize_callback' => [ $this, 'sanitize' ],
                        'default'           => [],
                ]
        );
    }

    /**
     * Sanitize all settings values.
     *
     * @param array<string, mixed> $input The input values.
     *
     * @return array<string, mixed> The sanitized values.
     */
    public function sanitize( array $input ): array {
        $sanitized = [];

        foreach ( $this->fields as $fieldId => $field ) {
            if ( ! empty( $field['_is_row'] ) ) {
                continue;
            }

            $value = $input[ $fieldId ] ?? ( $field['default'] ?? '' );

            // Get a field instance and use its prepare method
            $fieldInstance         = $this->getFieldInstance( $fieldId, $field );
            $sanitized[ $fieldId ] = $fieldInstance->prepare( $value, $field );
        }

        return $sanitized;
    }

    /**
     * Get the settings slug.
     *
     * @return string
     */
    public function getSlug(): string {
        return $this->slug;
    }

    /**
     * Get the settings title.
     *
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * Get a settings value.
     *
     * @param string $key The setting key.
     * @param mixed $default The default value.
     *
     * @return mixed The setting value.
     */
    public function get( string $key, mixed $default = null ): mixed {
        $values = get_option( $this->optionName, [] );

        return $values[ $key ] ?? $default;
    }

    /**
     * Get all settings values.
     *
     * @return array<string, mixed> All values.
     */
    public function all(): array {
        return get_option( $this->optionName, [] );
    }

}