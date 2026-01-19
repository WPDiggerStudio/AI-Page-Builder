<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Foundation;

use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Bootstrap - Framework bootstrapping utilities.
 *
 * Handles the bootstrapping process of the framework, including
 * provider registration, config loading, and hook binding.
 *
 * @package WPJarvis\Framework\Foundation
 */
class Bootstrap {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	private Application $app;

	/**
	 * Indicates if the application has been bootstrapped.
	 *
	 * @var bool
	 */
	private bool $bootstrapped = false;

	/**
	 * The bootstrappers to run in order.
	 *
	 * @var array<string>
	 */
	private array $bootstrappers = [
		'loadEnvironment',
		'loadConfiguration',
		'registerProviders',
		'bootProviders',
		'bindWordPressHooks',
	];

	/**
	 * Provider lifecycle events callbacks.
	 *
	 * @var array<string, array<callable>>
	 */
	private array $providerCallbacks = [
		'registering' => [],
		'registered'  => [],
		'booting'     => [],
		'booted'      => [],
	];

	/**
	 * Registered providers cache to prevent duplicates.
	 *
	 * @var array<class-string, bool>
	 */
	private array $registeredProviders = [];

	/**
	 * Create a new bootstrap instance.
	 *
	 * @param Application $app The application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Bootstrap the application.
	 *
	 * This method runs all bootstrapper methods in the configured order.
	 * Each bootstrapper is executed exactly once, and the process is
	 * idempotent - calling bootstrap() multiple times has no effect.
	 *
	 * @return void
	 */
	public function bootstrap(): void {
		if ( $this->bootstrapped ) {
			return;
		}

		foreach ( $this->bootstrappers as $bootstrapper ) {
			$this->$bootstrapper();
		}

		$this->bootstrapped = true;
	}

	/**
	 * Load environment variables from the .env file.
	 *
	 * Uses vlucas/phpdotenv library to safely parse and load environment
	 * variables. The library handles:
	 * - Complex values (quoted strings, multiline, nested variables)
	 * - Comments (both full line and inline)
	 * - Proper escaping and validation
	 * - Security best practices
	 *
	 * @return void
	 */
	protected function loadEnvironment(): void {
		$basePath = $this->app->basePath();
		$envFile  = $this->app->basePath( '.env' );

		// Only load if .env exists and phpdotenv is available
		if ( ! file_exists( $envFile ) || ! class_exists( \Dotenv\Dotenv::class ) ) {
			return;
		}

		try {
			// Use the production-ready phpdotenv library
			$dotenv = \Dotenv\Dotenv::createImmutable( $basePath );
			// safeLoad() won't override existing environment variables
			$dotenv->safeLoad();
		} catch ( \Exception $e ) {
			// Log error but don't crash - environment variables are optional
			if ( function_exists( 'wpj_logger' ) ) {
				wpj_logger()?->warning(
					'Failed to load .env file: ' . $e->getMessage(),
					[ 'exception' => $e ]
				);
			}
		}
	}

	/**
	 * Load configuration files from the config directory.
	 *
	 * Loads all PHP files in the config directory and registers them
	 * with the configuration repository. Each file name becomes a
	 * configuration key (e.g., 'app.php' becomes 'app' config).
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException If config cannot be resolved.
	 */
	protected function loadConfiguration(): void {
		$config     = $this->app->make( 'config' );
		$configPath = $this->app->configPath();

		if ( ! is_dir( $configPath ) ) {
			return;
		}

		$files = glob( $configPath . '/*.php' );

		if ( $files === false ) {
			return;
		}

		foreach ( $files as $file ) {
			$key = basename( $file, '.php' );
			$config->set( $key, require $file );
		}
	}

	/**
	 * Register all service providers.
	 *
	 * This method merges the core framework providers with the application's
	 * configured providers and registers them with the container. It ensures:
	 * - Framework providers are registered first
	 * - No duplicate providers are registered
	 * - Proper lifecycle callbacks are fired
	 *
	 * The registration follows Laravel's pattern: all providers are registered
	 * first (calling their register() method), then booted later (calling boot()).
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException If config cannot be resolved.
	 */
	protected function registerProviders(): void {
		// Get all providers: framework (base) + application (config)
		$allProviders = $this->mergeProviders();

		// Filter out already registered providers to prevent duplicates
		$providersToRegister = $this->filterNewProviders( $allProviders );

		// Fire registering callbacks before registration
		$this->fireProviderCallbacks( 'registering', $providersToRegister );

		// Register each provider
		foreach ( $providersToRegister as $provider ) {
			$this->app->register( $provider );
			$this->registeredProviders[ $provider ] = true;
		}

		// Fire registered callbacks after registration
		$this->fireProviderCallbacks( 'registered', $providersToRegister );
	}

	/**
	 * Get the base framework service providers.
	 *
	 * Returns an array of core framework service providers that are
	 * automatically registered by the framework. These providers should
	 * NOT be listed in the application's config/app.php file.
	 *
	 * Providers are organized into three categories:
	 * 1. Foundation providers - Core framework services
	 * 2. Application providers - WordPress integration services
	 * 3. Deferred providers - Loaded on demand when needed
	 *
	 * @return array<class-string> Array of fully qualified provider class names.
	 */
	protected function baseProviders(): array {
		return [
			// Foundation providers - Core framework services
			\WPJarvis\Framework\Providers\FacadeServiceProvider::class,
			\WPJarvis\Framework\Providers\RequirementsServiceProvider::class,
			\WPJarvis\Framework\Providers\ConsoleServiceProvider::class,
			\WPJarvis\Framework\Providers\CoreServiceProvider::class,
			\WPJarvis\Framework\Providers\FieldServiceProvider::class,

			// Application providers - WordPress integration (loaded on init)
			\WPJarvis\Framework\Providers\ContentServiceProvider::class,
			\WPJarvis\Framework\Providers\ShortcodeServiceProvider::class,
			\WPJarvis\Framework\Providers\EventServiceProvider::class,
			\WPJarvis\Framework\Providers\I18nServiceProvider::class,
			\WPJarvis\Framework\Providers\RouterServiceProvider::class,
			\WPJarvis\Framework\Providers\ValidationServiceProvider::class,

			// Deferred providers - Loaded on demand (lazy loading)
			\WPJarvis\Framework\Providers\MailServiceProvider::class,
			\WPJarvis\Framework\Providers\QueueServiceProvider::class,
			\WPJarvis\Framework\Providers\ScheduleServiceProvider::class,
		];
	}

	/**
	 * Merge framework and application providers.
	 *
	 * Combines the base framework providers with the application's
	 * configured providers from config/app.php. Framework providers
	 * are always registered first to establish core services.
	 *
	 * @return array<class-string> Merged array of provider class names.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException If config cannot be resolved.
	 */
	protected function mergeProviders(): array {
		$frameworkProviders = $this->baseProviders();
		$appProviders       = $this->app->make( 'config' )?->get( 'app.providers', [] ) ?? [];

		// Ensure framework providers come first
		return array_merge( $frameworkProviders, $appProviders );
	}

	/**
	 * Filter out providers that are already registered.
	 *
	 * Prevents duplicate registration by checking the registered
	 * providers cache. This is important when the same provider
	 * might be listed in both baseProviders() and config.
	 *
	 * @param array<class-string> $providers All providers to check.
	 *
	 * @return array<class-string> Providers that haven't been registered yet.
	 */
	protected function filterNewProviders( array $providers ): array {
		return array_filter(
			$providers,
			fn( string $provider ) => ! isset( $this->registeredProviders[ $provider ] )
		);
	}

	/**
	 * Boot all registered service providers.
	 *
	 * Calls the boot() method on all registered providers. This is done
	 * after all providers have been registered to ensure all services
	 * are available when booting.
	 *
	 * @return void
	 */
	protected function bootProviders(): void {
		$this->app->boot();
	}

	/**
	 * Check if the application has been bootstrapped.
	 *
	 * @return bool True if bootstrapped, false otherwise.
	 */
	public function isBootstrapped(): bool {
		return $this->bootstrapped;
	}

	/**
	 * Add a bootstrapper method name to the bootstrap sequence.
	 *
	 * Bootstrappers are executed in the order they are added. Use
	 * $prepend = true to add before existing bootstrappers.
	 *
	 * @param string $bootstrapper Method name to call during bootstrap.
	 * @param bool $prepend Whether to add before existing bootstrappers.
	 *
	 * @return static
	 */
	public function addBootstrapper( string $bootstrapper, bool $prepend = false ): static {
		if ( $prepend ) {
			array_unshift( $this->bootstrappers, $bootstrapper );
		} else {
			$this->bootstrappers[] = $bootstrapper;
		}

		return $this;
	}

	/**
	 * Remove a bootstrapper from the bootstrap sequence.
	 *
	 * @param string $bootstrapper Method name to remove.
	 *
	 * @return static
	 */
	public function removeBootstrapper( string $bootstrapper ): static {
		$this->bootstrappers = array_values(
			array_filter(
				$this->bootstrappers,
				static fn( string $b ) => $b !== $bootstrapper
			)
		);

		return $this;
	}

	/**
	 * Get the bootstrappers in their execution order.
	 *
	 * @return array<string> Array of bootstrapper method names.
	 */
	public function getBootstrappers(): array {
		return $this->bootstrappers;
	}

	/**
	 * Bind WordPress lifecycle hooks to framework events.
	 *
	 * This integrates the framework with WordPress action hooks,
	 * allowing the framework to respond to WordPress lifecycle events
	 * such as plugins_loaded, init, admin_init, etc.
	 *
	 * CRITICAL: Checks did_action() before adding hooks to prevent
	 * "Missed Train" race conditions. If a hook has already fired,
	 * the framework event is triggered immediately instead of being
	 * queued for a hook that will never fire.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function bindWordPressHooks(): void {
		$prefix = wpj_config( 'app.slug' );

		// Helper to bind or fire immediately if already passed
		$bindOrFire = function ( string $wpHook, string $frameworkEvent, int $priority = 5 ) use ( $prefix ) {
			$callback = function () use ( $frameworkEvent ) {
				Hooks::doAction( $frameworkEvent, $this->app );
			};

			// Check if the WordPress hook already fired
			if ( did_action( $wpHook ) ) {
				// Hook already passed - fire our event immediately
				$callback();
			} else {
				// Hook hasn't fired yet - add it normally
				Hooks::action( $wpHook, $callback, $priority );
			}
		};

		// Framework loaded - All plugins have been loaded
		$bindOrFire( 'plugins_loaded', $prefix . '_loaded', 5 );

		// WordPress init - Core WordPress initialization
		$bindOrFire( 'init', $prefix . '_init', 5 );

		// Admin initialization
		$bindOrFire( 'admin_init', $prefix . '_admin_init', 5 );

		// REST API initialization
		$bindOrFire( 'rest_api_init', $prefix . '_rest_init', 5 );

		// Frontend assets enqueuing
		$bindOrFire( 'wp_enqueue_scripts', $prefix . '_enqueue_scripts', 5 );

		// Admin assets enqueuing
		$bindOrFire( 'admin_enqueue_scripts', $prefix . '_admin_enqueue_scripts', 5 );

		// Shutdown/cleanup - Always bind (never fires before initialization)
		Hooks::action( 'shutdown', function () use ( $prefix ) {
			Hooks::doAction( $prefix . '_shutdown', $this->app );
			$this->app->terminate();
		} );
	}

	/**
	 * Register a provider lifecycle callback.
	 *
	 * Allows hooking into provider lifecycle events for logging,
	 * debugging, or custom logic.
	 *
	 * Supported events:
	 * - 'registering': Fired before any providers are registered
	 * - 'registered': Fired after all providers are registered
	 * - 'booting': Fired before any providers are booted
	 * - 'booted': Fired after all providers are booted
	 *
	 * @param string $event The event name (registering, registered, booting, booted).
	 * @param callable $callback The callback to execute. Receives (array $providers, Application $app).
	 *
	 * @return void
	 */
	public function onProvider( string $event, callable $callback ): void {
		if ( ! isset( $this->providerCallbacks[ $event ] ) ) {
			$this->providerCallbacks[ $event ] = [];
		}
		$this->providerCallbacks[ $event ][] = $callback;
	}

	/**
	 * Fire provider lifecycle callbacks for a specific event.
	 *
	 * @param string $event The event name.
	 * @param array<class-string> $providers The providers being processed.
	 *
	 * @return void
	 */
	protected function fireProviderCallbacks( string $event, array $providers ): void {
		if ( isset( $this->providerCallbacks[ $event ] ) ) {
			foreach ( $this->providerCallbacks[ $event ] as $callback ) {
				$callback( $providers, $this->app );
			}
		}
	}

	/**
	 * Get the application instance.
	 *
	 * @return Application The application instance.
	 */
	public function getApplication(): Application {
		return $this->app;
	}
}
