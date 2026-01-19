<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend\Shortcode\TinyMCE;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;
use WPJarvis\Framework\WP\Frontend\Shortcode\AbstractShortcode;
use WPJarvis\Framework\WP\Frontend\Shortcode\ShortcodeRegistry;

/**
 * ShortcodeDialogController - AJAX handler for shortcode field dialogs.
 *
 * Renders shortcode configuration dialogs using the Field System,
 * providing the same field rendering experience as Metabox.
 *
 * @package WPJarvis\Framework\WP\Frontend\Shortcode\TinyMCE
 */
class ShortcodeDialogController {
    /**
     * Shortcode registry instance.
     */
    private ShortcodeRegistry $registry;

    /**
     * Field registry instance.
     */
    private FieldRegistryInterface $fieldRegistry;

    /**
     * Create a new dialog controller.
     *
     * @param ShortcodeRegistry $registry Shortcode registry.
     * @param FieldRegistryInterface $fieldRegistry Field registry.
     */
    public function __construct( ShortcodeRegistry $registry, FieldRegistryInterface $fieldRegistry ) {
        $this->registry      = $registry;
        $this->fieldRegistry = $fieldRegistry;
    }

    /**
     * Register AJAX hooks.
     *
     * @return void
     */
    public function register(): void {
        Hooks::action( 'wp_ajax_wpj_shortcode_dialog', [ $this, 'handleDialogRequest' ] );
    }

    /**
     * Handle AJAX request for shortcode dialog.
     *
     * @return void
     */
    public function handleDialogRequest(): void {
        // Verify nonce
        if ( ! check_ajax_referer( 'wpj_shortcode_dialog', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'wp-jarvis' ) ], 403 );
        }

        // Check capabilities
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wp-jarvis' ) ], 403 );
        }

        $shortcodeTag = sanitize_text_field( $_POST['shortcode'] ?? '' );

        if ( empty( $shortcodeTag ) ) {
            wp_send_json_error( [ 'message' => __( 'Shortcode tag is required.', 'wp-jarvis' ) ], 400 );
        }

        $shortcode = $this->registry->get( $shortcodeTag );

        if ( $shortcode === null ) {
            wp_send_json_error( [ 'message' => __( 'Shortcode not found.', 'wp-jarvis' ) ], 404 );
        }

        $html = $this->renderDialog( $shortcode );

        wp_send_json_success( [
                'html'         => $html,
                'title'        => $shortcode->getMetadata()['title'] ?? $shortcodeTag,
                'allowContent' => $shortcode->getAllowContent(),
        ] );
    }

    /**
     * Render the shortcode dialog HTML.
     *
     * @param AbstractShortcode $shortcode The shortcode instance.
     *
     * @return string Rendered HTML.
     */
    public function renderDialog( AbstractShortcode $shortcode ): string {
        $fields   = $shortcode->getFields();
        $metadata = $shortcode->getMetadata();
        $tag      = $shortcode::getTag();

        ob_start();
        ?>
        <div class="wpj-shortcode-dialog" data-shortcode="<?php echo esc_attr( $tag ); ?>">
            <form class="wpj-shortcode-form">
                <?php if ( ! empty( $metadata['description'] ) ): ?>
                    <p class="wpj-shortcode-description">
                        <?php echo esc_html( $metadata['description'] ); ?>
                    </p>
                <?php endif; ?>

                <div class="wpj-shortcode-fields">
                    <?php foreach ( $fields as $field ): ?>
                        <?php echo $this->renderField( $field, $tag ); ?>
                    <?php endforeach; ?>
                </div>

                <?php if ( $shortcode->getAllowContent() ): ?>
                    <div class="wpj-shortcode-content-field">
                        <?php
                        echo $this->renderField( [
                                'type'        => 'textarea',
                                'id'          => '_content',
                                'label'       => __( 'Content', 'wp-jarvis' ),
                                'description' => __( 'Content between opening and closing shortcode tags.', 'wp-jarvis' ),
                                'rows'        => 4,
                        ], $tag );
                        ?>
                    </div>
                <?php endif; ?>

                <div class="wpj-shortcode-actions">
                    <button type="button" class="button button-primary wpj-shortcode-insert">
                        <?php esc_html_e( 'Insert Shortcode', 'wp-jarvis' ); ?>
                    </button>
                    <button type="button" class="button wpj-shortcode-cancel">
                        <?php esc_html_e( 'Cancel', 'wp-jarvis' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single field.
     *
     * @param array<string, mixed> $field Field configuration.
     * @param string $shortcodeTag Shortcode tag for namespacing.
     *
     * @return string Rendered field HTML.
     */
    private function renderField( array $field, string $shortcodeTag ): string {
        $fieldType  = $field['type'] ?? 'text';
        $fieldId    = $field['id'] ?? '';
        $fieldName  = 'wpj_sc_' . $shortcodeTag . '_' . $fieldId;
        $fieldLabel = $field['label'] ?? '';

        // Get or create field instance from registry
        $fieldInstance = $this->fieldRegistry->create( $fieldType, $field );

        // Prepare field config
        $fieldConfig = array_merge( $field, [
                'id'     => $fieldName,
                'name'   => $fieldName,
                'label'  => $fieldLabel,
                'layout' => $field['layout'] ?? 'stacked',
        ] );

        // Get default value
        $value = $field['default'] ?? $fieldInstance->getDefault( $fieldConfig );

        // Render using the Field System
        return $fieldInstance->render( $fieldConfig, $value, 'shortcode' );
    }

    /**
     * Get registered shortcodes for the dialog selector.
     *
     * @return array<string, array<string, mixed>> Shortcode configurations.
     */
    public function getShortcodesForSelector(): array {
        $shortcodes = [];

        foreach ( $this->registry->all() as $tag => $shortcode ) {
            $metadata           = $shortcode->getMetadata();
            $shortcodes[ $tag ] = [
                    'tag'         => $tag,
                    'title'       => $metadata['title'] ?? $tag,
                    'description' => $metadata['description'] ?? '',
                    'icon'        => $metadata['icon'] ?? 'shortcode',
                    'category'    => $metadata['category'] ?? 'general',
            ];
        }

        return $shortcodes;
    }
}
