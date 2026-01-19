<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend;

use WP_Widget;

/**
 * WidgetAdapter - Bridges fluent Widget builder to WordPress WP_Widget.
 *
 * This class extends WP_Widget and delegates all functionality to the
 * user-defined callbacks from the Widget builder.
 *
 * @package WPJarvis\Framework\WP\Frontend
 */
class WidgetAdapter extends WP_Widget {
    /**
     * The widget configuration.
     */
    private Widget $config;

    /**
     * Create a new WidgetAdapter instance.
     *
     * @param Widget $config Widget configuration.
     */
    public function __construct( Widget $config ) {
        $this->config = $config;

        parent::__construct(
                $config->getId(),
                $config->getName(),
                $config->getWidgetOptions(),
                $config->getControlOptions()
        );
    }

    /**
     * Front-end display of widget.
     *
     * @param array<string, mixed> $args Widget arguments.
     * @param array<string, mixed> $instance Saved values from a database.
     *
     * @return void
     */
    public function widget( $args, $instance ): void {
        // Merge instance with defaults
        $instance = wp_parse_args( $instance, $this->config->getDefaults() );

        $callback = $this->config->getRenderCallback();
        if ( $callback !== null ) {
            $output = $callback( $args, $instance );
            if ( is_string( $output ) ) {
                echo $output;
            }
        } else {
            // Default rendering if no callback provided
            echo $args['before_widget'] ?? '';
            if ( ! empty( $instance['title'] ) ) {
                echo ( $args['before_title'] ?? '' ) . esc_html( $instance['title'] ) . ( $args['after_title'] ?? '' );
            }
            echo '<div class="widget-content">';
            echo esc_html( $instance['content'] ?? '' );
            echo '</div>';
            echo $args['after_widget'] ?? '';
        }
    }

    /**
     * Back-end widget form.
     *
     * @param array<string, mixed> $instance Previously saved values from a database.
     *
     * @return string
     */
    public function form( $instance ): string {
        // Merge instance with defaults
        $instance = wp_parse_args( $instance, $this->config->getDefaults() );

        $callback = $this->config->getFormCallback();
        if ( $callback !== null ) {
            $output = $callback( $instance, $this );
            if ( is_string( $output ) ) {
                echo $output;
            }
        } else {
            // Default form if no callback provided
            $this->renderDefaultForm( $instance );
        }

        return '';
    }

    /**
     * Render default form fields.
     *
     * @param array<string, mixed> $instance Instance values.
     *
     * @return void
     */
    private function renderDefaultForm( array $instance ): void {
        $title = $instance['title'] ?? '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-jarvis' ); ?>
            </label>
            <input
                    class="widefat"
                    id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                    type="text"
                    value="<?php echo esc_attr( $title ); ?>"
            >
        </p>
        <?php
    }

    /**
     * Sanitize the widget form values as they are saved.
     *
     * @param array<string, mixed> $new_instance Values just sent to be saved.
     * @param array<string, mixed> $old_instance Previously saved values from a database.
     *
     * @return array<string, mixed> Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ): array {
        $callback = $this->config->getUpdateCallback();
        if ( $callback !== null ) {
            return $callback( $new_instance, $old_instance );
        }

        // Default sanitization
        $instance = [];
        foreach ( $new_instance as $key => $value ) {
            if ( is_string( $value ) ) {
                $instance[ $key ] = sanitize_text_field( $value );
            } elseif ( is_array( $value ) ) {
                $instance[ $key ] = array_map( 'sanitize_text_field', $value );
            } else {
                $instance[ $key ] = $value;
            }
        }

        return $instance;
    }

    /**
     * Get the underlying Widget configuration.
     *
     * @return Widget
     */
    public function getConfig(): Widget {
        return $this->config;
    }

    /**
     * Get field ID (public accessor for callbacks).
     *
     * @param string $field Field name.
     *
     * @return string
     */
    public function fieldId( string $field ): string {
        return $this->get_field_id( $field );
    }

    /**
     * Get a field name (public accessor for callbacks).
     *
     * @param string $field Field name.
     *
     * @return string
     */
    public function fieldName( string $field ): string {
        return $this->get_field_name( $field );
    }
}
