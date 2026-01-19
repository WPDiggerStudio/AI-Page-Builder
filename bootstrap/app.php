<?php

declare( strict_types=1 );

use Dotenv\Dotenv;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Foundation\Bootstrap;

/*
|--------------------------------------------------------------------------
| Bootstrap The Application
|--------------------------------------------------------------------------
|
| This file bootstraps the WP Jarvis application. It loads .env first,
| then creates prefixed constants, verifies the framework, and registers
| all WordPress hooks needed for the plugin to function properly.
|
*/

/*
|--------------------------------------------------------------------------
| Load Composer Autoloader FIRST
|--------------------------------------------------------------------------
*/

$autoload = WPJARVIS_PLUGIN_DIR . '/vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
    add_action( 'admin_notices', static function (): void {
        ?>
        <div class="notice notice-error">
            <p>
                <strong>Plugin Error:</strong>
                <?php esc_html_e( 'Composer dependencies are not installed. Please run "composer install" in the plugin directory.', 'wp-jarvis' ); ?>
            </p>
        </div>
        <?php
    } );

    return;
}

require_once $autoload;

/*
|--------------------------------------------------------------------------
| Load Environment Variables BEFORE Anything Else
|--------------------------------------------------------------------------
*/

if ( class_exists( Dotenv::class ) ) {
    $dotenv = Dotenv::createImmutable( WPJARVIS_PLUGIN_DIR );
    $dotenv->safeLoad();
}

/*
|--------------------------------------------------------------------------
| Read Plugin Configuration from .env
|--------------------------------------------------------------------------
*/

$_prefix     = env( 'PLUGIN_PREFIX', 'APP' );
$_version    = env( 'PLUGIN_VERSION', '1.0.0' );
$_slug       = env( 'PLUGIN_SLUG', 'wp-jarvis-app' );
$_name       = env( 'PLUGIN_NAME', 'WP Jarvis App' );
$_textDomain = env( 'PLUGIN_TEXTDOMAIN', 'wp-jarvis' );

/*
|--------------------------------------------------------------------------
| Create Prefixed Constants from .env Values
|--------------------------------------------------------------------------
|
| These constants are generated dynamically based on PLUGIN_PREFIX from .env
| For example, if PLUGIN_PREFIX=BRA, you get: BRA_VERSION, BRA_FILE, etc.
|
*/

defined( "{$_prefix}_VERSION" ) || define( "{$_prefix}_VERSION", $_version );
defined( "{$_prefix}_FILE" ) || define( "{$_prefix}_FILE", WPJARVIS_PLUGIN_FILE );
defined( "{$_prefix}_DIR" ) || define( "{$_prefix}_DIR", WPJARVIS_PLUGIN_DIR );
defined( "{$_prefix}_BASENAME" ) || define( "{$_prefix}_BASENAME", plugin_basename( WPJARVIS_PLUGIN_FILE ) );
defined( "{$_prefix}_SLUG" ) || define( "{$_prefix}_SLUG", $_slug );
defined( "{$_prefix}_NAME" ) || define( "{$_prefix}_NAME", $_name );

// Helper function to get prefixed constant value
$getConst = static fn( string $name ): mixed => constant( "{$_prefix}_{$name}" );

/*
|--------------------------------------------------------------------------
| Verify Framework Is Available
|--------------------------------------------------------------------------
*/

if ( ! class_exists( Application::class ) ) {
    add_action( 'admin_notices', static function () use ( $_name ): void {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php echo esc_html( $_name ); ?>:</strong>
                <?php esc_html_e( 'Framework is not installed. Please run "composer install" to install dependencies.', 'wp-jarvis' ); ?>
            </p>
        </div>
        <?php
    } );

    return;
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Application( WPJARVIS_PLUGIN_DIR );

// Bind plugin info to the container (all from .env)
$app->instance( 'plugin.prefix', $_prefix );
$app->instance( 'plugin.version', $_version );
$app->instance( 'plugin.slug', $_slug );
$app->instance( 'plugin.name', $_name );
$app->instance( 'plugin.file', WPJARVIS_PLUGIN_FILE );
$app->instance( 'plugin.dir', WPJARVIS_PLUGIN_DIR );
$app->instance( 'plugin.basename', plugin_basename( WPJARVIS_PLUGIN_FILE ) );
$app->instance( 'plugin.textdomain', $_textDomain );

/*
|--------------------------------------------------------------------------
| Bootstrap The Application Immediately
|--------------------------------------------------------------------------
|
| Bootstrap the application immediately after creation. This ensures:
| - hasBeenBootstrapped is set to true
| - All configured service providers are registered
| - All providers are booted
| - environmentPath is properly set
|
| The Bootstrap class is still used for WordPress-specific hook binding.
*/

$bootstrap = new Bootstrap( $app );

try {
    // Bootstrap framework first to register base providers (including FacadeServiceProvider)
    $bootstrap->bootstrap();
} catch ( \Illuminate\Contracts\Container\BindingResolutionException $e ) {
    /**
     * Handle initialization errors gracefully
     * Log the error and prevent the plugin from breaking WordPress
     */
    if ( function_exists( 'error_log' ) ) {
        error_log( 'Plugin Initialization Error: ' . $e->getMessage() );
        error_log( 'Stack trace: ' . $e->getTraceAsString() );
    }

    // In development, you might want to display the error
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'wp_die' ) ) {
        wp_die(
                'Plugin failed to initialize: ' . esc_html( $e->getMessage() ),
                'Plugin Initialization Error',
                [ 'back_link' => true ]
        );
    }

    // Return null to indicate failure
    return null;
}


/*
|--------------------------------------------------------------------------
| Register in Global Apps Registry
|--------------------------------------------------------------------------
*/

$GLOBALS[ str_replace( '-', '_', $_slug ) . '_app' ] = $app;

/*
|--------------------------------------------------------------------------
| Global Helper Function
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'wpjarvis_app' ) ) {
    /**
     * Get a WP Jarvis application instance by slug.
     *
     * @param string $slug Plugin slug (from .env PLUGIN_SLUG).
     * @param string|null $abstract Optional binding to resolve.
     *
     * @return Application|mixed|null
     */
    function wpjarvis_app( string $slug, ?string $abstract = null ): mixed {
        $apps = $GLOBALS['wpjarvis_apps'] ?? [];

        if ( ! isset( $apps[ $slug ] ) ) {
            return null;
        }

        if ( $abstract === null ) {
            return $apps[ $slug ];
        }

        return $apps[ $slug ]->make( $abstract );
    }
}

/*
|--------------------------------------------------------------------------
| Register WordPress Hooks via Bootstrap
|--------------------------------------------------------------------------
|
| The Bootstrap class handles WordPress-specific hook binding.
| This is separate from the core application bootstrap which
| happens immediately via $app->bootstrap() above.
|
| WordPress hooks are registered here for proper timing:
| - plugins_loaded: Framework ready event
| - init: WordPress initialization
| - admin_init: Admin initialization
| - rest_api_init: REST API initialization
| - wp_enqueue_scripts: Frontend assets
| - admin_enqueue_scripts: Admin assets
| - shutdown: Cleanup and termination
*/

// Bootstrap already called directly above, no need to call again on plugins_loaded
// add_action( 'plugins_loaded', static function () use ( $bootstrap ): void {
//     $bootstrap->bootstrap();
// }, 5 );

/*
|--------------------------------------------------------------------------
| Register Activation Hook
|--------------------------------------------------------------------------
*/

register_activation_hook( WPJARVIS_PLUGIN_FILE, static function () use ( $app, $_prefix ): void {
    $directories = [
            $app->storagePath(),
            $app->storagePath( 'logs' ),
            $app->storagePath( 'framework' ),
            $app->storagePath( 'framework/cache' ),
            $app->storagePath( 'framework/views' ),
    ];

    foreach ( $directories as $directory ) {
        if ( ! is_dir( $directory ) ) {
            wp_mkdir_p( $directory );
        }

        $htaccess = $directory . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, 'Deny from all' );
        }
    }

    do_action( strtolower( $_prefix ) . '_activate', $app );
    flush_rewrite_rules();
} );

/*
|--------------------------------------------------------------------------
| Register Deactivation Hook
|--------------------------------------------------------------------------
*/

register_deactivation_hook( WPJARVIS_PLUGIN_FILE, static function () use ( $app, $_prefix ): void {
    do_action( strtolower( $_prefix ) . '_deactivate', $app );
    flush_rewrite_rules();
} );

/*
|--------------------------------------------------------------------------
| Register Uninstall Hook
|--------------------------------------------------------------------------
*/

$uninstallOptionName = strtolower( $_prefix ) . '_uninstall_data';
update_option( $uninstallOptionName, [
        'prefix' => $_prefix,
        'dir'    => WPJARVIS_PLUGIN_DIR,
], false );

register_uninstall_hook( WPJARVIS_PLUGIN_FILE, 'wpjarvis_handle_uninstall' );

if ( ! function_exists( 'wpjarvis_handle_uninstall' ) ) {
    /**
     * Handle plugin uninstallation.
     */
    function wpjarvis_handle_uninstall(): void {
        $uninstallFile = $GLOBALS['wp_uninstall_plugin'] ?? '';

        if ( empty( $uninstallFile ) ) {
            return;
        }

        $pluginDir = dirname( $uninstallFile );

        global $wpdb;
        $options = $wpdb->get_results(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '%_uninstall_data'"
        );

        foreach ( $options as $option ) {
            $data = maybe_unserialize( $option->option_value );

            if ( isset( $data['dir'] ) && $data['dir'] === $pluginDir ) {
                $prefix   = $data['prefix'];
                $autoload = $pluginDir . '/vendor/autoload.php';

                if ( file_exists( $autoload ) ) {
                    require_once $autoload;

                    // Load .env for uninstall context
                    if ( class_exists( Dotenv::class ) ) {
                        $dotenv = Dotenv::createImmutable( $pluginDir );
                        $dotenv->safeLoad();
                    }

                    $app              = new \WPJarvis\Framework\Application( $pluginDir );
                    $cleanOnUninstall = env( 'CLEAN_ON_UNINSTALL', false );

                    if ( $cleanOnUninstall ) {
                        $slug = env( 'PLUGIN_SLUG', strtolower( $prefix ) );

                        $wpdb->query(
                                $wpdb->prepare(
                                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                                        $slug . '%'
                                )
                        );

                        $wpdb->query(
                                $wpdb->prepare(
                                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                                        '_transient_' . $slug . '%',
                                        '_transient_timeout_' . $slug . '%'
                                )
                        );
                    }

                    do_action( strtolower( $prefix ) . '_uninstall', $app );
                }

                delete_option( $option->option_name );
                break;
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
*/

return $app;
