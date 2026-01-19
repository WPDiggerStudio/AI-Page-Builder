<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Admin;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldInterface;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;
use WPJarvis\Framework\WP\Fields\Security\Validator;
use WPJarvis\Framework\WP\Fields\Storage\OptionStorage;

/**
 * SettingsPage - Fluent builder for WordPress admin settings pages.
 *
 * Uses centralized Field System for rendering and managing settings fields.
 *
 * @package WPJarvis\Framework\WP\Admin
 */
class SettingsPage {
    /**
     * The settings page ID.
     *
     * @var string
     */
    private string $pageId;

    /**
     * The settings page title.
     *
     * @var string
     */
    private string $pageTitle;

    /**
     * The settings menu title.
     *
     * @var string
     */
    private string $menuTitle;

    /**
     * The settings page capability.
     *
     * @var string
     */
    private string $capability = 'manage_options';

    /**
     * The settings page menu slug.
     *
     * @var string
     */
    private string $menuSlug;

    /**
     * The settings page icon URL.
     *
     * @var string
     */
    private string $iconUrl = '';

    /**
     * The settings page position.
     *
     * @var null|int
     */
    private ?int $position = null;

    /**
     * The settings page parent slug (for submenus).
     *
     * @var string
     */
    private string $parentSlug = '';

    /**
     * The settings sections.
     *
     * @var array<string, array{title: string, description: string, fields: array<string, array<string, mixed>>}>
     */
    private array $sections = [];

    /**
     * Field registry instance.
     *
     * @var FieldRegistryInterface
     */
    private FieldRegistryInterface $fieldRegistry;

    /**
     * Validator instance.
     *
     * @var Validator
     */
    private Validator $validator;

    /**
     * Storage instance.
     *
     * @var OptionStorage
     */
    private OptionStorage $storage;

    /**
     * Field instance cache.
     *
     * @var array<string, FieldInterface>
     */
    private array $fieldInstances = [];

    /**
     * The settings page callback.
     *
     * @var callable|null
     */
    private $pageCallback = null;

    /**
     * Create a new SettingsPage instance.
     *
     * @param string $pageId The settings page ID.
     * @param string $pageTitle The settings page title.
     * @param string $menuTitle The settings menu title.
     * @param FieldRegistryInterface|null $fieldRegistry Field registry instance.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct( string $pageId, string $pageTitle, string $menuTitle, ?FieldRegistryInterface $fieldRegistry = null ) {
        $this->pageId        = $pageId;
        $this->pageTitle     = $pageTitle;
        $this->menuTitle     = $menuTitle;
        $this->menuSlug      = $pageId;
        $this->fieldRegistry = $fieldRegistry ?? wpj_app( 'field_registry' );
        $this->validator     = new Validator();
        $this->storage       = new OptionStorage();
    }

    /**
     * Create a new SettingsPage instance.
     *
     * @param string $pageId The settings page ID.
     * @param string $pageTitle The settings page title.
     * @param string $menuTitle The settings menu title.
     *
     * @return static The new SettingsPage instance.
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function make( string $pageId, string $pageTitle, string $menuTitle ): static {
        return new static( $pageId, $pageTitle, $menuTitle );
    }

    /**
     * Set the settings page capability.
     *
     * @param string $capability The capability required to access the page.
     *
     * @return static The current instance for chaining.
     */
    public function capability( string $capability ): static {
        $this->capability = $capability;

        return $this;
    }

    /**
     * Set the settings page menu slug.
     *
     * @param string $menuSlug The menu slug.
     *
     * @return static The current instance for chaining.
     */
    public function menuSlug( string $menuSlug ): static {
        $this->menuSlug = $menuSlug;

        return $this;
    }

    /**
     * Set the settings page icon URL.
     *
     * @param string $iconUrl The icon URL.
     *
     * @return static The current instance for chaining.
     */
    public function iconUrl( string $iconUrl ): static {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    /**
     * Set the settings page position.
     *
     * @param int $position The position.
     *
     * @return static The current instance for chaining.
     */
    public function position( int $position ): static {
        $this->position = $position;

        return $this;
    }

    /**
     * Set the settings page as a submenu.
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
     * Set the settings page callback.
     *
     * @param callable $callback The page callback.
     *
     * @return static The current instance for chaining.
     */
    public function callback( callable $callback ): static {
        $this->pageCallback = $callback;

        return $this;
    }

    /**
     * Add a settings section.
     *
     * @param string $sectionId The section ID.
     * @param string $title The section title.
     * @param string $description The section description.
     *
     * @return static The current instance for chaining.
     */
    public function section( string $sectionId, string $title, string $description = '' ): static {
        $this->sections[ $sectionId ] = [
                'title'       => $title,
                'description' => $description,
                'fields'      => [],
        ];

        return $this;
    }

    /**
     * Add a text field to a section.
     *
     * @param string $sectionId The section ID.
     * @param string $fieldId The field ID.
     * @param string $label The field label.
     * @param array<string, mixed> $options Additional field options.
     *
     * @return static The current instance for chaining.
     */
    public function text( string $sectionId, string $fieldId, string $label, array $options = [] ): static {
        return $this->field( $sectionId, 'text', $fieldId, $label, $options );
    }

    /**
     * Add a textarea field to a section.
     *
     * @param string $sectionId The section ID.
     * @param string $fieldId The field ID.
     * @param string $label The field label.
     * @param array<string, mixed> $options Additional field options.
     *
     * @return static The current instance for chaining.
     */
    public function textarea( string $sectionId, string $fieldId, string $label, array $options = [] ): static {
        return $this->field( $sectionId, 'textarea', $fieldId, $label, $options );
    }

    /**
     * Add a checkbox field to a section.
     *
     * @param string $sectionId The section ID.
     * @param string $fieldId The field ID.
     * @param string $label The field label.
     * @param bool $default The default value.
     * @param array<string, mixed> $options Additional field options.
     *
     * @return static The current instance for chaining.
     */
    public function checkbox( string $sectionId, string $fieldId, string $label, bool $default = false, array $options = [] ): static {
        $options['default'] = $default;

        return $this->field( $sectionId, 'checkbox', $fieldId, $label, $options );
    }

    /**
     * Add a select field to a section.
     *
     * @param string $sectionId The section ID.
     * @param string $fieldId The field ID.
     * @param string $label The field label.
     * @param array<string, mixed> $choices The select options.
     * @param array<string, mixed> $options Additional field options.
     *
     * @return static The current instance for chaining.
     */
    public function select( string $sectionId, string $fieldId, string $label, array $choices, array $options = [] ): static {
        $options['choices'] = $choices;

        return $this->field( $sectionId, 'select', $fieldId, $label, $options );
    }

    /**
     * Add a field with a specific type to a section.
     *
     * @param string $sectionId The section ID.
     * @param string $type The field type.
     * @param string $fieldId The field ID.
     * @param string $label The field label.
     * @param array<string, mixed> $options Additional field options.
     *
     * @return static The current instance for chaining.
     */
    public function field( string $sectionId, string $type, string $fieldId, string $label, array $options = [] ): static {
        if ( ! isset( $this->sections[ $sectionId ] ) ) {
            $this->section( $sectionId, ucfirst( $sectionId ) );
        }

        $this->sections[ $sectionId ]['fields'][ $fieldId ] = array_merge( [
                'type'  => $type,
                'id'    => $fieldId,
                'label' => $label,
        ], $options );

        return $this;
    }

    /**
     * Register the settings page with WordPress.
     *
     * @return void
     */
    public function register(): void {
        // Register the menu page
        Hooks::action( 'admin_menu', [ $this, 'registerMenu' ] );

        // Register settings
        Hooks::action( 'admin_init', [ $this, 'registerSettings' ] );

        // Register settings fields
        Hooks::action( 'admin_init', [ $this, 'registerFields' ] );
    }

    /**
     * Register the menu page.
     *
     * @return void
     */
    public function registerMenu(): void {
        if ( ! empty( $this->parentSlug ) ) {
            // Add as submenu
            \add_submenu_page(
                    $this->parentSlug,
                    $this->pageTitle,
                    $this->menuTitle,
                    $this->capability,
                    $this->menuSlug,
                    [ $this, 'renderPage' ]
            );
        } else {
            // Add as a top-level menu
            \add_menu_page(
                    $this->pageTitle,
                    $this->menuTitle,
                    $this->capability,
                    $this->menuSlug,
                    [ $this, 'renderPage' ],
                    $this->iconUrl,
                    $this->position
            );
        }
    }

    /**
     * Register the settings.
     *
     * @return void
     */
    public function registerSettings(): void {
        foreach ( $this->sections as $sectionId => $section ) {
            \register_setting(
                    $this->pageId,
                    $this->pageId,
                    [
                            'sanitize_callback' => [ $this, 'sanitizeSettings' ],
                            'default'           => $this->getDefaults(),
                    ]
            );
        }
    }

    /**
     * Register the settings sections and fields.
     *
     * @return void
     */
    public function registerFields(): void {
        foreach ( $this->sections as $sectionId => $section ) {
            // Register the section
            \add_settings_section(
                    $this->pageId . '_' . $sectionId,
                    $section['title'],
                    static function () use ( $section ) {
                        if ( ! empty( $section['description'] ) ) {
                            echo '<p>' . \esc_html( $section['description'] ) . '</p>';
                        }
                    },
                    $this->pageId
            );

            // Register the fields
            foreach ( $section['fields'] as $fieldId => $field ) {
                \add_settings_field(
                        $this->pageId . '_' . $fieldId,
                        $field['label'],
                        [ $this, 'renderField' ],
                        $this->pageId,
                        $this->pageId . '_' . $sectionId,
                        [
                                'field'      => $field,
                                'field_id'   => $fieldId,
                                'section_id' => $sectionId,
                        ]
                );
            }
        }
    }

    /**
     * Render a settings field.
     *
     * @param array<string, mixed> $args The field arguments.
     *
     * @return void
     */
    public function renderField( array $args ): void {
        $field     = $args['field'];
        $fieldId   = $args['field_id'];
        $sectionId = $args['section_id'];

        // Get the current value
        $options = \get_option( $this->pageId, [] );
        $value   = $options[ $fieldId ] ?? $field['default'] ?? '';

        // Get or create a field instance
        $fieldInstance = $this->getFieldInstance( $fieldId, $field );

        // Prepare field config with name and ID
        $fieldConfig = array_merge( $field, [
                'id'    => $this->pageId . '_' . $fieldId,
                'name'  => $this->pageId . '[' . $fieldId . ']',
                'label' => $field['label'],
        ] );

        // Render field
        echo '<div class="wp-jarvis-field">';
        echo $fieldInstance->render( $fieldConfig, $value, 'settings' );
        if ( ! empty( $field['description'] ?? '' ) ) {
            echo '<p class="description">' . \wp_kses_post( $field['description'] ) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Sanitize the settings.
     *
     * @param array<string, mixed> $input The input data.
     *
     * @return array<string, mixed> The sanitized data.
     */
    public function sanitizeSettings( array $input ): array {
        $sanitized = [];

        foreach ( $this->sections as $sectionId => $section ) {
            foreach ( $section['fields'] as $fieldId => $field ) {
                $value = $input[ $fieldId ] ?? $field['default'] ?? '';

                // Get or create a field instance
                $fieldInstance = $this->getFieldInstance( $fieldId, $field );

                // Validate the value
                $validationResult = $fieldInstance->validate( $value, $field );
                if ( ! $validationResult['valid'] ) {
                    // Add settings error for each validation error
                    foreach ( $validationResult['errors'] as $errorType => $errorMessage ) {
                        \add_settings_error(
                                $this->pageId . '_' . $fieldId,
                                $errorType,
                                sprintf( '%s: %s', $field['label'] ?? $fieldId, $errorMessage ),
                                'error'
                        );
                    }
                    // Use the old value if validation fails
                    $options               = \get_option( $this->pageId, [] );
                    $sanitized[ $fieldId ] = $options[ $fieldId ] ?? $field['default'] ?? '';
                    continue;
                }

                // Sanitize the value
                $sanitized[ $fieldId ] = $fieldInstance->prepare( $value, $field );
            }
        }

        return $sanitized;
    }

    /**
     * Get the default values for all fields.
     *
     * @return array<string, mixed> The default values.
     */
    private function getDefaults(): array {
        $defaults = [];

        foreach ( $this->sections as $sectionId => $section ) {
            foreach ( $section['fields'] as $fieldId => $field ) {
                if ( isset( $field['default'] ) ) {
                    $defaults[ $fieldId ] = $field['default'];
                }
            }
        }

        return $defaults;
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function renderPage(): void {
        // Check capability
        if ( ! \current_user_can( $this->capability ) ) {
            \wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-jarvis' ) );
        }

        // Call a custom callback if provided
        if ( $this->pageCallback ) {
            \call_user_func( $this->pageCallback, $this );

            return;
        }

        // Default page rendering
        ?>
        <div class="wrap">
            <h1><?php echo \esc_html( $this->pageTitle ); ?></h1>

            <?php
            // Display settings errors
            \settings_errors( $this->pageId );
            ?>

            <form action="options.php" method="post">
                <?php
                \settings_fields( $this->pageId );
                \do_settings_sections( $this->pageId );
                \submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get the settings page ID.
     *
     * @return string The settings page ID.
     */
    public function getPageId(): string {
        return $this->pageId;
    }

    /**
     * Get the settings page title.
     *
     * @return string The settings page title.
     */
    public function getPageTitle(): string {
        return $this->pageTitle;
    }

    /**
     * Get the settings sections.
     *
     * @return array<string, array{title: string, description: string, fields: array<string, array<string, mixed>>}> The settings sections.
     */
    public function getSections(): array {
        return $this->sections;
    }

    /**
     * Get a setting value.
     *
     * @param string $fieldId The field ID.
     * @param mixed $default The default value.
     *
     * @return mixed The setting value.
     */
    public function get( string $fieldId, mixed $default = null ): mixed {
        $options = \get_option( $this->pageId, [] );

        return $options[ $fieldId ] ?? $default;
    }

    /**
     * Set a setting value.
     *
     * @param string $fieldId The field ID.
     * @param mixed $value The value.
     *
     * @return bool Whether the value was updated.
     */
    public function set( string $fieldId, mixed $value ): bool {
        $options             = \get_option( $this->pageId, [] );
        $options[ $fieldId ] = $value;

        return \update_option( $this->pageId, $options );
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
}
