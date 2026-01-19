<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * Requirements Service Provider
 *
 * Validates plugin environment compatibility on activation and runtime.
 *
 * @package WPJarvis\Framework\Providers
 */
class RequirementsServiceProvider extends ServiceProvider {
    /**
     * Path to the requirements config file.
     *
     * @var string
     */
    protected string $configPath;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->configPath = $this->app->configPath( 'requirements.php' );

        $this->singleton( 'requirements', function () {
            if ( file_exists( $this->configPath ) ) {
                return require $this->configPath;
            }

            return [];
        } );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot(): void {
        $this->checkRequirements();
    }

    /**
     * Validate all environment and dependency requirements.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function checkRequirements(): void {
        if ( ! file_exists( $this->configPath ) ) {
            return;
        }

        $requirements = require $this->configPath;
        $errors       = [];

        // PHP Version
        if ( isset( $requirements['min_php_version'] ) ) {
            if ( version_compare( PHP_VERSION, $requirements['min_php_version'], '<' ) ) {
                $errors[] = sprintf(
                        __( 'PHP %s+ is required. You are running: %s', 'wp-jarvis' ),
                        $requirements['min_php_version'],
                        PHP_VERSION
                );
            }
        }

        // WordPress Version
        global $wp_version;
        if ( isset( $requirements['min_wp_version'] ) ) {
            if ( version_compare( $wp_version, $requirements['min_wp_version'], '<' ) ) {
                $errors[] = sprintf(
                        __( 'WordPress %s+ is required. You are running: %s', 'wp-jarvis' ),
                        $requirements['min_wp_version'],
                        $wp_version
                );
            }
        }

        // Multisite Compatibility
        if ( isset( $requirements['is_multisite_compatible'] ) &&
             $requirements['is_multisite_compatible'] === false &&
             is_multisite()
        ) {
            $errors[] = __( 'This plugin is not compatible with WordPress multisite.', 'wp-jarvis' );
        }

        // Required PHP Extensions
        if ( ! empty( $requirements['php_extensions'] ) ) {
            foreach ( $requirements['php_extensions'] as $extension ) {
                if ( ! extension_loaded( $extension ) ) {
                    $errors[] = sprintf(
                            __( 'Required PHP extension missing: %s', 'wp-jarvis' ),
                            $extension
                    );
                }
            }
        }

        // Required Plugins
        if ( ! empty( $requirements['required_plugins'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            foreach ( $requirements['required_plugins'] as $name => $plugin ) {
                $slug       = $plugin['plugin_slug'] ?? null;
                $minVersion = $plugin['min_plugin_version'] ?? null;

                if ( ! $slug || ! file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
                    $errors[] = sprintf(
                            __( 'Required plugin <strong>%s</strong> is missing.', 'wp-jarvis' ),
                            $name
                    );
                    continue;
                }

                if ( ! is_plugin_active( $slug ) ) {
                    $errors[] = sprintf(
                            __( 'Required plugin <strong>%s</strong> is not active.', 'wp-jarvis' ),
                            $name
                    );
                    continue;
                }

                if ( $minVersion ) {
                    $data           = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug, false, false );
                    $currentVersion = $data['Version'] ?? '0.0.0';

                    if ( version_compare( $currentVersion, $minVersion, '<' ) ) {
                        $errors[] = sprintf(
                                __( 'Required plugin <strong>%s</strong> must be version %s+ (installed: %s)', 'wp-jarvis' ),
                                $name,
                                $minVersion,
                                $currentVersion
                        );
                    }
                }
            }
        }

        // Handle errors
        if ( ! empty( $errors ) ) {
            $this->handleRequirementErrors( $errors );
        }
    }

    /**
     * Handle requirement errors.
     *
     * @param array<string> $errors
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function handleRequirementErrors( array $errors ): void {
        // Deactivate plugin
        if ( function_exists( 'deactivate_plugins' ) && $this->app->bound( 'plugin.file' ) ) {
            deactivate_plugins( plugin_basename( $this->app->make( 'plugin.file' ) ) );
        }

        // Show admin notice
        add_action( 'admin_notices', function () use ( $errors ) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . esc_html__( 'Plugin Activation Failed', 'wp-jarvis' ) . '</strong></p>';
            echo '<ul style="list-style-type: disc; margin-left: 20px;">';
            foreach ( $errors as $error ) {
                echo '<li>' . wp_kses_post( $error ) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } );

        // Prevent further execution during activation
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' ) {
            wp_die(
                    $this->renderRequirementsError( $errors ),
                    __( 'Plugin Requirements Not Met', 'wp-jarvis' ),
                    [ 'back_link' => true ]
            );
        }
    }

    /**
     * Render requirements error HTML.
     *
     * @param array<string> $errors
     *
     * @return string
     */
    private function renderRequirementsError( array $errors ): string {
        ob_start();
        ?>
        <style>
            .WpJarvis-requirements {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                max-width: 600px;
            }

            .WpJarvis-requirements h1 {
                font-size: 26px;
                color: #dc3232;
                margin-bottom: 20px;
            }

            .WpJarvis-requirements ul {
                margin: 0;
                padding-left: 20px;
            }

            .WpJarvis-requirements li {
                margin-bottom: 10px;
                color: #555d66;
            }

            .WpJarvis-requirements a {
                display: inline-block;
                margin-top: 20px;
                color: #0073aa;
                text-decoration: none;
            }
        </style>
        <div class="WpJarvis-requirements">
            <h1>&#128683; <?php esc_html_e( 'Plugin Activation Failed', 'wp-jarvis' ); ?></h1>
            <ul>
                <?php foreach ( $errors as $error ) : ?>
                    <li><?php echo wp_kses_post( $error ); ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">&laquo; <?php esc_html_e( 'Back to Plugins', 'wp-jarvis' ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }
}
