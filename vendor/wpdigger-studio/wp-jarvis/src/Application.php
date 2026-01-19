<?php

declare(strict_types=1);

namespace WPJarvis\Framework;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application as IlluminateApplication;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\FileViewFinder;
use Psr\Log\LoggerInterface;
use WPJarvis\Framework\Console\Kernel;
use ReflectionClass;

/**
 * Application
 *
 * The main application container for WP Jarvis framework.
 * Composes Laravel's Container with WordPress-specific functionality.
 *
 * @package WPJarvis\Framework
 */
class Application implements IlluminateApplication, \ArrayAccess
{
	/**
	 * The internal container instance.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * The WP Jarvis framework version.
	 *
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * The base path for the application.
	 *
	 * @var string
	 */
	protected string $basePath;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected bool $booted = false;

	/**
	 * The array of booting callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $bootingCallbacks = [];

	/**
	 * The array of booted callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $bootedCallbacks = [];

	/**
	 * The array of terminating callbacks.
	 *
	 * @var array<callable>
	 */
	protected array $terminatingCallbacks = [];

	/**
	 * All the registered service providers.
	 *
	 * @var array<string, ServiceProvider>
	 */
	protected array $serviceProviders = [];

	/**
	 * The names of the loaded service providers.
	 *
	 * @var array<string, bool>
	 */
	protected array $loadedProviders = [];

	/**
	 * The deferred services and their providers.
	 *
	 * @var array<string, string>
	 */
	protected array $deferredServices = [];

	/**
	 * The custom environment path defined by the developer.
	 *
	 * @var string
	 */
	protected string $environmentPath = '';

	/**
	 * The environment file to load during bootstrapping.
	 *
	 * @var string
	 */
	protected string $environmentFile = '.env';

	/**
	 * Indicates if the application has been bootstrapped.
	 *
	 * @var bool
	 */
	protected bool $hasBeenBootstrapped = false;

	/**
	 * Create a new WP Jarvis application instance.
	 *
	 * @param string|null $basePath
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct(?string $basePath = null)
	{
		$this->container = new Container();

		if ($basePath) {
			$this->setBasePath($basePath);
		}

		// Initialize environment path to base path
		$this->environmentPath = $this->basePath;

		$this->registerBaseBindings();
		$this->registerBaseServiceProviders();
		$this->registerCoreContainerAliases();
	}

	/**
	 * Get the version number of the framework.
	 *
	 * @return string
	 */
	public function version(): string
	{
		return static::VERSION;
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function registerBaseBindings(): void
	{
		// static::setInstance($this); // REMOVED

		$this->instance('app', $this);
		$this->instance(Container::class, $this->container);
		$this->instance(self::class, $this);
	}

	/**
	 * Register all the base service providers.
	 *
	 * @return void
	 */
	protected function registerBaseServiceProviders(): void
	{
		// Register events
		$this->registerEventsProvider();

		// Register filesystem
		$this->registerFilesystemProvider();

		// Register config
		$this->registerConfigProvider();

		// Register console kernel
		$this->registerConsoleKernel();

        // Register database provider
        $this->register(\WPJarvis\Framework\Providers\DatabaseServiceProvider::class);
	}

	/**
	 * Register the events' provider.
	 *
	 * @return void
	 */
	protected function registerEventsProvider(): void
	{
		$this->singleton('events', function () {
			return new Dispatcher($this);
		});
	}

	/**
	 * Register the filesystem provider.
	 *
	 * @return void
	 */
	protected function registerFilesystemProvider(): void
	{
		$this->singleton('files', function () {
			return new Filesystem();
		});
	}

	/**
	 * Register the console kernel.
	 *
	 * @return void
	 */
	protected function registerConsoleKernel(): void
	{
		$this->singleton(
			\Illuminate\Contracts\Console\Kernel::class,
			function ($app) {
				return new Kernel($app, $app->make('events'));
			}
		);

		// Alias the WPJarvis-specific Kernel contract to the same implementation
		$this->alias(
			\Illuminate\Contracts\Console\Kernel::class,
			\WPJarvis\Framework\Contracts\Console\Kernel::class
		);
	}

	/**
	 * Register the config provider.
	 *
	 * @return void
	 */
	protected function registerConfigProvider(): void
	{
		$this->singleton('config', function () {
			// Check if config is cached
			$cachedPath = $this->getCachedConfigPath();

			if (file_exists($cachedPath)) {
				return new ConfigRepository(require $cachedPath);
			}

			// Fall back to loading from individual files
			return new ConfigRepository($this->loadConfigFiles());
		});

		$this->alias('config', ConfigContract::class);
		$this->alias('config', ConfigRepository::class);
	}

	/**
	 * Load all configuration files.
	 *
	 * @return array<string, mixed>
	 */
	protected function loadConfigFiles(): array
	{
		$config = [];
		$configPath = $this->configPath();

		if (!is_dir($configPath)) {
			return $config;
		}

		$files = glob($configPath . '/*.php');

		foreach ($files as $file) {
			$key = basename($file, '.php');
			$config[$key] = require $file;
		}

		return $config;
	}

	/**
	 * Set the base path for the application.
	 *
	 * @param string $basePath
	 *
	 * @return $this
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function setBasePath(string $basePath): static
	{
		$this->basePath = rtrim($basePath, '\/');

		$this->bindPathsInContainer();

		return $this;
	}

	/**
	 * Bind all the application paths in the container.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function bindPathsInContainer(): void
	{
		$this->instance('path.base', $this->basePath());
		$this->instance('path.config', $this->configPath());
		$this->instance('path.storage', $this->storagePath());
		$this->instance('path.resources', $this->resourcePath());
		$this->instance('path.public', $this->publicPath());
		$this->instance('path.database', $this->databasePath());
		$this->instance('path.bootstrap', $this->bootstrapPath());
		$this->instance('path.routes', $this->routesPath());
	}

	/**
	 * Get the base URL of the plugin.
	 *
	 * @param string $path Optional relative path inside the plugin.
	 */
	public function baseUrl(string $path = ''): string
	{
		$pluginFile = $this->instanceValue('plugin.file');
		if ($pluginFile === '' || !function_exists('plugin_dir_url')) {
			return '';
		}

		$base = plugin_dir_url($pluginFile); // always trailing slash
		$path = ltrim(str_replace('\\', '/', $path), '/');

		return $path !== '' ? $base . $path : rtrim($base, '/');
	}

	/**
	 * Convert an absolute path (inside the plugin folder) to a public plugin URL.
	 */
	public function urlFromPath(string $absPath): string
	{
		$pluginFile = $this->instanceValue('plugin.file');
		if ($pluginFile === '' || !function_exists('plugin_dir_url')) {
			return '';
		}

		// Canonicalize plugin directory
		$pluginDir = dirname($pluginFile);
		$pluginDirReal = realpath($pluginDir);
		$pluginDir = wp_normalize_path($pluginDirReal !== false ? $pluginDirReal : $pluginDir);

		// Canonicalize target file/dir path (this removes vendor/composer/../ etc.)
		$absReal = realpath($absPath);
		$absPath = wp_normalize_path($absReal !== false ? $absReal : $absPath);

		// Security: only allow paths inside the plugin directory
		$prefix = trailingslashit($pluginDir);
		if (!str_starts_with($absPath, $prefix)) {
			return '';
		}

		// Convert to a relative path within plugin
		$rel = ltrim(substr($absPath, strlen($pluginDir)), '/');

		return trailingslashit(plugin_dir_url($pluginFile)) . $rel;
	}

	/**
	 * Resolve a composer package install directory.
	 *
	 * @param string $package e.g. "wp-jarvis/framework"
	 */
	public function composerPackagePath(string $package): string
	{
		$vendorDir = $this->basePath('vendor');
		$installed = $vendorDir . '/composer/installed.php';

		if (!file_exists($installed)) {
			return '';
		}

		$data = require $installed;
		$sets = [];

		if (isset($data['versions'])) {
			$sets = [$data];
		} elseif (is_array($data)) {
			$sets = $data;
		}

		foreach ($sets as $set) {
			if (!isset($set['versions']) || !is_array($set['versions'])) {
				continue;
			}

			if (isset($set['versions'][$package]['install_path'])) {
				$path = (string) $set['versions'][$package]['install_path'];
				$real = realpath($path);

				return wp_normalize_path($real !== false ? $real : $path);
			}
		}

		return '';
	}

	/**
	 * Find an asset file inside the plugin's Composer vendor directory.
	 *
	 * This searches for a path like:
	 *   <plugin>/vendor/<vendor>/<package>/<relativeFromPackageRoot>
	 *
	 * Example:
	 *   findVendorAsset('resources/assets/js/tinymce/shortcode-button.js')
	 *   =>/vendor/wp-jarvis/framework/resources/assets/js/tinymce/shortcode-button.js
	 *
	 * Notes:
	 * - Replaced slow glob() with manifest lookup.
	 * - Returns empty string if manifest is missing or asset not found.
	 *
	 * @param string $relativeFromPackageRoot Relative path from a Composer package root (e.g. "resources/assets/...").
	 *
	 * @return string Absolute normalized filesystem path to the asset, or empty string if not found.
	 */
	public function findVendorAsset(string $relativeFromPackageRoot): string
	{
        // TODO: Implement proper manifest loading from bootstrap/cache/assets.php
        // For now, return empty to prevent performance issues with glob() in production.
        // Developers should publish assets to public/ directory instead.

		return '';
	}

	/**
	 * Get the base path of the application.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function basePath($path = ''): string
	{
		return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the application configuration files.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function configPath($path = ''): string
	{
		return $this->basePath('config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function storagePath($path = ''): string
	{
		$storagePath = $this->basePath('storage');

		// If local storage doesn't exist, use WordPress uploads with folder name from .env
		if (function_exists('wp_upload_dir') && !is_dir($storagePath)) {
			$uploads = wp_upload_dir();

			// Get the folder name from config (which reads from .env PLUGIN_STORAGE_FOLDER)
			$folderName = 'wp-jarvis'; // Default fallback

			if ($this->bound('config')) {
				/** @var \Illuminate\Config\Repository|null $config */
				$config = $this->make('config');
				$folderName = $config?->get('app.storage_folder') ?? $folderName;
			} elseif ($this->bound('plugin.slug')) {
				$folderName = $this->make('plugin.slug');
			}

			$storagePath = $uploads['basedir'] . '/' . $folderName;
		}

		return $storagePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the resources' directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function resourcePath($path = ''): string
	{
		return $this->basePath('resources') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the public directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function publicPath($path = ''): string
	{
		return $this->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the database directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function databasePath($path = ''): string
	{
		return $this->basePath('database') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the bootstrap directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function bootstrapPath($path = ''): string
	{
		return $this->basePath('bootstrap') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the routes' directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function routesPath(string $path = ''): string
	{
		return $this->basePath('routes') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the views' directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function viewPath(string $path = ''): string
	{
		return $this->resourcePath('views') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}

	/**
	 * Get the path to the language files.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function langPath($path = ''): string
	{
		return $this->resourcePath('lang' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the assets' directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function assetsPath(string $path = ''): string
	{
		return $this->publicPath('assets' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the cache directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function cachePath(string $path = ''): string
	{
		return $this->storagePath('framework/cache' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the logs directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function logPath(string $path = ''): string
	{
		return $this->storagePath('logs' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the app directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function appPath(string $path = ''): string
	{
		return $this->basePath('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the framework directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function frameworkPath(string $path = ''): string
	{
		return $this->basePath('framework' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the vendor directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function vendorPath(string $path = ''): string
	{
		return $this->basePath('vendor' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
	}

	/**
	 * Get the path to the WordPress plugins directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function pluginsPath(string $path = ''): string
	{
		$wpPluginDir = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : ABSPATH . 'wp-content/plugins';

		return $wpPluginDir . ($path ? DIRECTORY_SEPARATOR . $path : '');
	}

	/**
	 * Get the path to the WordPress themes directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function themesPath(string $path = ''): string
	{
		$wpThemeDir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/themes' : ABSPATH . 'wp-content/themes';

		return $wpThemeDir . ($path ? DIRECTORY_SEPARATOR . $path : '');
	}

	/**
	 * Get the path to the WordPress content directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function wpContentPath(string $path = ''): string
	{
		$wpContentDir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';

		return $wpContentDir . ($path ? DIRECTORY_SEPARATOR . $path : '');
	}

	/**
	 * Get the path to the WordPress uploads directory.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function uploadsPath(string $path = ''): string
	{
		if (function_exists('wp_upload_dir')) {
			$uploadDir = wp_upload_dir();
			$baseDir = $uploadDir['basedir'] ?? $this->wpContentPath('uploads');
		} else {
			$baseDir = $this->wpContentPath('uploads');
		}

		return $baseDir . ($path ? DIRECTORY_SEPARATOR . $path : '');
	}

	/**
	 * Get the path to the cached services.php file.
	 *
	 * @return string
	 */
	public function getCachedServicesPath(): string
	{
		return $this->bootstrapPath('cache/services.php');
	}

	/**
	 * Get the path to the cached packages.php file.
	 *
	 * @return string
	 */
	public function getCachedPackagesPath(): string
	{
		return $this->bootstrapPath('cache/packages.php');
	}

	/**
	 * Get the path to the configuration cache file.
	 *
	 * @return string
	 */
	public function getCachedConfigPath(): string
	{
		return $this->bootstrapPath('cache/config.php');
	}

	/**
	 * Get the path to the routes cache file.
	 *
	 * @return string
	 */
	public function getCachedRoutesPath(): string
	{
		return $this->bootstrapPath('cache/routes.php');
	}

	/**
	 * Get the path to the events cache file.
	 *
	 * @return string
	 */
	public function getCachedEventsPath(): string
	{
		return $this->bootstrapPath('cache/events.php');
	}

	/**
	 * Get or check the current application environment.
	 *
	 * @param string|array<string>|null ...$environments
	 *
	 * @return string|bool
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function environment(...$environments): string|bool
	{
		/** @var ConfigRepository|null $config */
		$config = $this->bound('config') ? $this->make('config') : null;
		$env = $config?->get('app.env', 'production') ?? 'production';

		if (empty($environments)) {
			return $env;
		}

		return in_array($env, $environments, true);
	}

	/**
	 * Determine if the application is in the production environment.
	 *
	 * @return bool
	 */
	public function isProduction(): bool
	{
		return $this->environment('production');
	}

	/**
	 * Determine if the application is in debug mode.
	 *
	 * @return bool
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function isDebug(): bool
	{
		/** @var ConfigRepository|null $config */
		$config = $this->bound('config') ? $this->make('config') : null;

		return (bool) ($config?->get('app.debug', false) ?? false);
	}

	/**
	 * Register a service provider with the application.
	 *
	 * @param ServiceProvider|string $provider
	 * @param bool $force
	 *
	 * @return ServiceProvider
	 */
	public function register($provider, $force = false): ServiceProvider
	{
		if (!$force && ($registered = $this->getProvider($provider))) {
			return $registered;
		}

		if (is_string($provider)) {
			$provider = $this->resolveProvider($provider);
		}

		// If the provider is deferred, store its services for lazy loading
		if ($provider->isDeferred() && method_exists($provider, 'provides')) {
			$services = $provider->provides();
			foreach ($services as $service) {
				$this->deferredServices[$service] = get_class($provider);
			}
		} else {
			$provider->register();
		}

		// Mark the provider as registered
		$this->markAsRegistered($provider);

		// If the application has already booted, we will call this boot method
		if ($this->isBooted() && !$provider->isDeferred()) {
			$this->bootProvider($provider);
		}

		return $provider;
	}

	/**
	 * Get the registered service provider instance if it exists.
	 *
	 * @param ServiceProvider|string $provider
	 *
	 * @return ServiceProvider|null
	 */
	public function getProvider(ServiceProvider|string $provider): ?ServiceProvider
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return $this->serviceProviders[$name] ?? null;
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param string $provider
	 *
	 * @return ServiceProvider
	 */
	public function resolveProvider($provider): ServiceProvider
	{
		return new $provider($this);
	}

	/**
	 * Mark the given provider as registered.
	 *
	 * @param ServiceProvider $provider
	 *
	 * @return void
	 */
	protected function markAsRegistered(ServiceProvider $provider): void
	{
		$class = get_class($provider);

		$this->serviceProviders[$class] = $provider;
		$this->loadedProviders[$class] = true;
	}

	/**
	 * Boot the given service provider.
	 *
	 * @param ServiceProvider $provider
	 *
	 * @return void
	 */
	protected function bootProvider(ServiceProvider $provider): void
	{
		if (method_exists($provider, 'boot')) {
			$this->call([$provider, 'boot']);
		}
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		if ($this->isBooted()) {
			return;
		}

		// Register additional core services
		$this->registerViewProvider();
		$this->registerValidationProvider();
		$this->registerTranslationProvider();
		$this->registerLogProvider();

		// Call booting callbacks
		$this->fireAppCallbacks($this->bootingCallbacks);

		// Boot all registered providers (excluding deferred ones)
		foreach ($this->serviceProviders as $provider) {
			if (!$provider->isDeferred()) {
				$this->bootProvider($provider);
			}
		}

		$this->booted = true;

		// Call booted callbacks
		$this->fireAppCallbacks($this->bootedCallbacks);
	}

	/**
	 * Register the view provider.
	 *
	 * @return void
	 */
	protected function registerViewProvider(): void
	{
		$this->singleton('view.finder', function () {
			/** @var Filesystem $files */
			$files = $this->make('files');

			return new FileViewFinder(
				$files,
				[$this->viewPath()]
			);
		});

		$this->singleton('view.engine.resolver', function () {
			$resolver = new EngineResolver();

			// Register PHP engine
			$resolver->register('php', function () {
				$files = $this->make('files');

				return new PhpEngine($files);
			});

			// Register Blade engine
			$resolver->register('blade', function () {
				$files = $this->make('files');

				return new CompilerEngine(
					new BladeCompiler(
						$files,
						$this->storagePath('framework/views')
					),
					$files
				);
			});

			return $resolver;
		});

		$this->singleton('view', function () {
			$resolver = $this->make('view.engine.resolver');
			$finder = $this->make('view.finder');
			$events = $this->make('events');

			$factory = new ViewFactory($resolver, $finder, $events);
			$factory->setContainer($this);

			return $factory;
		});

		$this->alias('view', ViewFactory::class);
		$this->alias('view', \Illuminate\Contracts\View\Factory::class);
	}

	/**
	 * Register the validation provider.
	 *
	 * @return void
	 */
	protected function registerValidationProvider(): void
	{
		$this->singleton('validator', function () {
			$translator = $this->make('translator');

			return new ValidationFactory($translator, $this);
		});

		$this->alias('validator', ValidationFactory::class);
		$this->alias('validator', Factory::class);
	}

	/**
	 * Register the translation provider.
	 *
	 * @return void
	 */
	protected function registerTranslationProvider(): void
	{
		$this->singleton('translator', function () {
			$files = $this->make('files');

			$loader = new FileLoader(
				$files,
				$this->resourcePath('lang')
			);

			/** @var ConfigRepository|null $config */
			$config = $this->bound('config') ? $this->make('config') : null;
			$locale = $config?->get('app.locale', 'en') ?? 'en';

			return new Translator($loader, $locale);
		});

		$this->alias('translator', Translator::class);
		$this->alias('translator', \Illuminate\Contracts\Translation\Translator::class);
	}

	/**
	 * Register the log provider.
	 *
	 * @return void
	 */
	protected function registerLogProvider(): void
	{
		$this->singleton('log', function () {
			/** @var ConfigRepository|null $config */
			$config = $this->bound('config') ? $this->make('config') : null;
			$level = $config?->get('logging.level', 'debug') ?? 'debug';

			return new WP\Logging\Logger(
				$level,
				$this->storagePath('logs/app.log')
			);
		});

		$this->alias('log', LoggerInterface::class);
	}

	/**
	 * Determine if the application has booted.
	 *
	 * @return bool
	 */
	public function isBooted(): bool
	{
		return $this->booted;
	}

	/**
	 * Register a new boot listener.
	 *
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function booting($callback): void
	{
		$this->bootingCallbacks[] = $callback;
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param callable $callback
	 *
	 * @return void
	 */
	public function booted($callback): void
	{
		$this->bootedCallbacks[] = $callback;

		if ($this->isBooted()) {
			$callback($this);
		}
	}

	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param array<callable> $callbacks
	 *
	 * @return void
	 */
	protected function fireAppCallbacks(array &$callbacks): void
	{
		$index = 0;

		while ($index < count($callbacks)) {
			$callbacks[$index]($this);
			$index++;
		}
	}

	/**
	 * Register a terminating callback with the application.
	 *
	 * @param callable $callback
	 *
	 * @return $this
	 */
	public function terminating($callback): static
	{
		$this->terminatingCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Terminate the application.
	 *
	 * @return void
	 */
	public function terminate(): void
	{
		$index = 0;

		while ($index < count($this->terminatingCallbacks)) {
			$this->call($this->terminatingCallbacks[$index]);
			$index++;
		}
	}

	/**
	 * Get the service providers that have been loaded.
	 *
	 * @return array<ServiceProvider>
	 */
	public function getLoadedProviders(): array
	{
		return $this->loadedProviders;
	}

	/**
	 * Get all registered service provider instances.
	 *
	 * @return array<string, ServiceProvider>
	 */
	public function getServiceProviders(): array
	{
		return $this->serviceProviders;
	}

	/**
	 * Determine if the given service provider is loaded.
	 *
	 * @param string $provider
	 *
	 * @return bool
	 */
	public function providerIsLoaded(string $provider): bool
	{
		return isset($this->loadedProviders[$provider]);
	}

	/**
	 * Register the core class aliases in the container.
	 *
	 * @return void
	 */
	public function registerCoreContainerAliases(): void
	{
		$aliases = [
			'app' => [self::class, Container::class, \Illuminate\Contracts\Container\Container::class],
			'config' => [ConfigRepository::class, ConfigContract::class],
			'events' => [Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
			'files' => [Filesystem::class],
		];

		foreach ($aliases as $key => $classes) {
			foreach ($classes as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush(): void
	{
		$this->container->flush();

		$this->loadedProviders = [];
		$this->bootedCallbacks = [];
		$this->bootingCallbacks = [];
		$this->deferredServices = [];
		$this->serviceProviders = [];
		$this->terminatingCallbacks = [];
	}

	/**
	 * Get a raw value from the container's internal instances array.
	 *
	 * This is a low-level helper intended mainly for debugging or for reading values
	 * you explicitly stored via {@see instance()}. It bypasses normal resolution
	 * (bindings, factories, contextual bindings, etc.). Prefer {@see make()} or
	 * {@see bound()} for typical container usage.
	 *
	 * @param string $key The instance key to retrieve (e.g. 'plugin.prefix', 'path.base').
	 * @param mixed $default Value returned when the key does not exist in instances.
	 *
	 * @return mixed             The stored instance value or the provided default.
	 */
    public function instanceValue(string $key, mixed $default = null): mixed
    {
        // Use reflection to access protected instances property of the composed container
        try {
            $reflection = new ReflectionClass($this->container);
            $property = $reflection->getProperty('instances');
            $property->setAccessible(true);
            $instances = $property->getValue($this->container);
            return $instances[$key] ?? $default;
        } catch (\ReflectionException $e) {
            return $default;
        }
    }

	// =========================================================================
	// ENVIRONMENT METHODS
	// =========================================================================

	/**
	 * Get the path to the environment file directory.
	 *
	 * @return string
	 */
	public function environmentPath(): string
	{
		return $this->environmentPath ?: $this->basePath;
	}

	/**
	 * Set the directory for the environment file.
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function useEnvironmentPath(string $path): static
	{
		$this->environmentPath = $path;

		return $this;
	}

	/**
	 * Set the environment file to be loaded during bootstrapping.
	 *
	 * @param string $file
	 *
	 * @return $this
	 */
	public function loadEnvironmentFrom(string $file): static
	{
		$this->environmentFile = $file;

		return $this;
	}

	/**
	 * Get the environment file the application is using.
	 *
	 * @return string
	 */
	public function environmentFile(): string
	{
		return $this->environmentFile ?: '.env';
	}

	/**
	 * Get the fully qualified path to the environment file.
	 *
	 * @return string
	 */
	public function environmentFilePath(): string
	{
		return $this->environmentPath() . DIRECTORY_SEPARATOR . $this->environmentFile();
	}

	/**
	 * Determine if the application is running in the console.
	 *
	 * @return bool
	 */
	public function runningInConsole(): bool
	{
		if (PHP_SAPI === 'cli') {
			return true;
		}

		// Check for WP-CLI
		if (defined('WP_CLI') && constant('WP_CLI')) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 *
	 * @return bool
	 */
	public function hasBeenBootstrapped(): bool
	{
		return $this->hasBeenBootstrapped;
	}

	/**
	 * Determine if the application is down for maintenance.
	 *
	 * @return bool
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function isDownForMaintenance(): bool
	{
		return $this->maintenanceMode()->active();
	}

	/**
	 * Determine if debug mode is enabled.
	 *
	 * Checks both the application config and WordPress debug settings.
	 *
	 * @return bool
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function hasDebugModeEnabled(): bool
	{
		// Check application config first
		$debug = $this->isDebug();

		// Also, check WordPress debug setting if available
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$debug = true;
		}

		return $debug;
	}

	/**
	 * Determine if the application is running unit tests.
	 *
	 * @return bool
	 */
	public function runningUnitTests(): bool
	{
		return defined('PHPUNIT_RUNNING') && constant('PHPUNIT_RUNNING') === true;
	}

	/**
	 * Get the maintenance mode manager instance.
	 *
	 * @return \Illuminate\Contracts\Foundation\MaintenanceMode
	 */
	public function maintenanceMode(): \Illuminate\Contracts\Foundation\MaintenanceMode
	{
		return new \WPJarvis\Framework\Foundation\WordPressMaintenanceMode($this);
	}

	/**
	 * Determine if middleware should be skipped.
	 *
	 * @return bool
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function shouldSkipMiddleware(): bool
	{
		/** @var ConfigRepository|null $config */
		$config = $this->bound('config') ? $this->make('config') : null;

		return (bool) ($config?->get('app.skip_middleware', false) ?? false);
	}

	// =========================================================================
	// UTILITY METHODS
	// =========================================================================

	/**
	 * Get a configuration value.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function config(string $key, mixed $default = null): mixed
	{
		/** @var ConfigRepository|null $config */
		$config = $this->bound('config') ? $this->make('config') : null;

		return $config?->get($key, $default) ?? $default;
	}

	/**
	 * Log a message.
	 *
	 * @param string $level
	 * @param string $message
	 * @param array<string, mixed> $context
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function log(string $level, string $message, array $context = []): void
	{
		if ($this->bound('log')) {
			$logger = $this->make('log');
			$logger->log($level, $message, $context);
		}
	}

	/**
	 * Create a new Validator instance.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $rules
	 * @param array<string, string> $messages
	 * @param array<string, string> $customAttributes
	 *
	 * @return \Illuminate\Validation\Validator
	 * @throws \RuntimeException|\Illuminate\Contracts\Container\BindingResolutionException If the validator is not bound.
	 */
	public function validate(array $data, array $rules, array $messages = [], array $customAttributes = []): \Illuminate\Validation\Validator
	{
		if (!$this->bound('validator')) {
			throw new \RuntimeException('Validator service is not registered.');
		}

		return $this->make('validator')?->make($data, $rules, $messages, $customAttributes);
	}

	/**
	 * Get the application namespace.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function getNamespace(): string
	{
		/** @var ConfigRepository|null $config */
		$config = $this->bound('config') ? $this->make('config') : null;

		return $config?->get('app.namespace', 'App\\') ?? 'App\\';
	}

	/**
	 * Set the application locale.
	 *
	 * @param string $locale
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function setLocale($locale): void
	{
		if (function_exists('switch_to_locale')) {
			switch_to_locale($locale);
		}

		if ($this->bound('translator')) {
			$translator = $this->make('translator');
			$translator->setLocale($locale);
		}

		if ($this->bound('config')) {
			$config = $this->make('config');
			$config->set('app.locale', $locale);
		}
	}

	/**
	 * Get the current application locale.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function getLocale(): string
	{
		if ($this->bound('translator')) {
			return $this->make('translator')?->getLocale();
		}

		return function_exists('get_locale') ? get_locale() : 'en_US';
	}

	/**
	 * Determine if the application locale is the given locale.
	 *
	 * @param string $locale
	 *
	 * @return bool
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function isLocale(string $locale): bool
	{
		return $this->getLocale() === $locale;
	}

	/**
	 * Get the application's deferred services.
	 *
	 * @return array<string, string>
	 */
	public function getDeferredServices(): array
	{
		return $this->deferredServices;
	}

	/**
	 * Set the application's deferred services.
	 *
	 * @param array<string, string> $services
	 *
	 * @return void
	 */
	public function setDeferredServices(array $services): void
	{
		$this->deferredServices = $services;
	}

	/**
	 * Add an array of services to the application's deferred services.
	 *
	 * @param array<string, string> $services
	 *
	 * @return void
	 */
	public function addDeferredServices(array $services): void
	{
		$this->deferredServices = array_merge($this->deferredServices, $services);
	}

	/**
	 * Determine if the given service is a deferred service.
	 *
	 * @param string $service
	 *
	 * @return bool
	 */
	public function isDeferredService(string $service): bool
	{
		return isset($this->deferredServices[$service]);
	}

	/**
	 * Register a deferred provider and service.
	 *
	 * @param string $provider
	 * @param string|null $service
	 *
	 * @return void
	 */
	public function registerDeferredProvider($provider, $service = null): void
	{
		$this->register($provider);
	}

	/**
	 * Resolve a given instance from the container.
	 *
	 * This method overrides the parent make() to support deferred service providers.
	 * When a deferred service is requested, its provider will be loaded and
	 * the service will be resolved.
	 *
	 * @param string $abstract
	 * @param array $parameters
	 *
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function make($abstract, array $parameters = []): mixed
	{
		// Check if this is a deferred service
		if (isset($this->deferredServices[$abstract])) {
			$provider = $this->deferredServices[$abstract];

			// Load the deferred provider
			$this->register($provider);

			// Remove from deferred services
			unset($this->deferredServices[$abstract]);
		}

		return $this->container->make($abstract, $parameters);
	}

	/**
	 * Load the deferred providers.
	 *
	 * @return void
	 */
	public function loadDeferredProviders(): void
	{
		foreach ($this->deferredServices as $service => $provider) {
			$this->registerDeferredProvider($provider, $service);
		}

		$this->deferredServices = [];
	}

	/**
	 * Register all the configured providers.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerConfiguredProviders(): void
	{
		/** @var ConfigRepository|null $config */
		$config = $this->bound('config') ? $this->make('config') : null;
		$providers = $config?->get('app.providers', []) ?? [];

		foreach ($providers as $provider) {
			$this->register($provider);
		}
	}

	/**
	 * Bootstrap the application with the given bootstrappers.
	 *
	 * @param array<int, string> $bootstrappers
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function bootstrapWith(array $bootstrappers): void
	{
		$this->hasBeenBootstrapped = true;

		foreach ($bootstrappers as $bootstrapper) {
			/** @var object $instance */
			$instance = $this->make($bootstrapper);

			if (method_exists($instance, 'bootstrap')) {
				$instance->bootstrap($this);
			}
		}
	}

	/**
	 * Bootstrap the application.
	 *
	 * This method performs the complete bootstrap sequence:
	 * 1. Marks the application as bootstrapped
	 * 2. Registers all configured service providers
	 * 3. Boots all registered providers
	 *
	 * This is the main entry point for bootstrapping the application
	 * and should be called after the Application instance is created.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function bootstrap(): void
	{
		if ($this->hasBeenBootstrapped) {
			return;
		}

		$this->hasBeenBootstrapped = true;
		$this->registerConfiguredProviders();
		$this->boot();
	}

	/**
	 * Get the providers of a specific type.
	 *
	 * @param string|ServiceProvider $provider
	 *
	 * @return array<ServiceProvider>
	 */
	public function getProviders($provider): array
	{
		$name = is_string($provider) ? $provider : get_class($provider);

		return array_filter($this->serviceProviders, static function ($p) use ($name) {
			return $p instanceof $name;
		});
	}

	// =========================================================================
	// WORDPRESS INTEGRATION METHODS
	// =========================================================================

	/**
	 * Get the WordPress instance.
	 *
	 * @return \WP|null
	 */
	public function getWordPress(): ?\WP
	{
		return $GLOBALS['wp'] ?? null;
	}

	/**
	 * Get the wpdb instance.
	 *
	 * @return \wpdb|null
	 */
	public function getWPDB(): ?\wpdb
	{
		return $GLOBALS['wpdb'] ?? null;
	}

	/**
	 * Register a WordPress action through the Hooks system.
	 *
	 * @param string $hook The hook name
	 * @param callable|array $callback The callback to execute
	 * @param int $priority The priority (default: 10)
	 * @param int $acceptedArgs Number of arguments to accept (default: 1)
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	public function action(string $hook, callable|array $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		$this->make('hooks')?->action($hook, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Register a WordPress filter through the Hooks system.
	 *
	 * @param string $hook The hook name
	 * @param callable|array $callback The callback to execute
	 * @param int $priority The priority (default: 10)
	 * @param int $acceptedArgs Number of arguments to accept (default: 1)
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	public function filter(string $hook, callable|array $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		$this->make('hooks')?->filter($hook, $callback, $priority, $acceptedArgs);
	}

	/**
	 * Execute a WordPress action through the Hooks system.
	 *
	 * @param string $hook The hook name
	 * @param mixed ...$args Arguments to pass to the hook
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	public function doAction(string $hook, ...$args): void
	{
		$this->make('hooks')?->doAction($hook, ...$args);
	}

	/**
	 * Apply WordPress filters through the Hooks system.
	 *
	 * @param string $hook The hook name
	 * @param mixed $value The value to filter
	 * @param mixed ...$args Additional arguments
	 *
	 * @return mixed The filtered value
	 * @throws BindingResolutionException
	 */
	public function applyFilters(string $hook, mixed $value, ...$args): mixed
	{
		return $this->make('hooks')?->applyFilters($hook, $value, ...$args);
	}

	/**
	 * Remove a registered WordPress action.
	 *
	 * @param string $hook
	 * @param callable|array $callback
	 * @param int $priority
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function removeAction(string $hook, callable|array $callback, int $priority = 10): void
	{
		$this->make('hooks')?->removeAction($hook, $callback, $priority);
	}

	/**
	 * Remove a registered WordPress filter.
	 *
	 * @param string $hook
	 * @param callable|array $callback
	 * @param int $priority
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function removeFilter(string $hook, callable|array $callback, int $priority = 10): void
	{
		$this->make('hooks')?->removeFilter($hook, $callback, $priority);
	}

	/**
	 * Check if an action has been fired.
	 *
	 * @param string $hook
	 *
	 * @return int
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function didAction(string $hook): int
	{
		return $this->make('hooks')?->didAction($hook);
	}

	/**
	 * Check if a filter has been applied.
	 *
	 * @param string $hook
	 *
	 * @return bool|int Returns the priority of the hook if it exists, false otherwise.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function hasFilter(string $hook): bool|int
	{
		return $this->make('hooks')?->didAction($hook);

	}

	/**
	 * Check if an action has been registered.
	 *
	 * @param string $hook
	 * @param callable|array|string|null $callback
	 *
	 * @return bool|int Returns the priority of the hook if it exists, false otherwise.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function hasAction(string $hook, callable|array|string $callback = null): bool|int
	{
		return $this->make('hooks')?->hasAction($hook, $callback);
	}

	/**
	 * Get all registered hooks in the application.
	 *
	 * @return array All registered hooks
	 * @throws BindingResolutionException
	 */
	public function getRegisteredHooks(): array
	{
		return $this->make('hooks')?->getRegisteredHooks();
	}

	/**
	 * Get all registered actions in the application.
	 *
	 * @return array All registered actions
	 * @throws BindingResolutionException
	 */
	public function getRegisteredActions(): array
	{
		return $this->make('hooks')?->getRegisteredActions();
	}

	/**
	 * Get all registered filters in the application.
	 *
	 * @return array All registered filters
	 * @throws BindingResolutionException
	 */
	public function getRegisteredFilters(): array
	{
		return $this->make('hooks')?->getRegisteredFilters();
	}

	/**
	 * Get all WordPress hooks with our prefix.
	 *
	 * @return array All WordPress hooks with our prefix
	 * @throws BindingResolutionException
	 */
	public function getAllWordPressHooks(): array
	{
		return $this->make('hooks')?->getAllWordPressHooks();
	}

    /**
     * Get the internal container instance.
     *
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    // =========================================================================
    // CONTAINER PROXY METHODS
    // =========================================================================

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->container->bind($abstract, $concrete, $shared);
    }

    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        $this->container->bindIf($abstract, $concrete, $shared);
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->container->singleton($abstract, $concrete);
    }

    public function scoped($abstract, $concrete = null)
    {
        if (method_exists($this->container, 'scoped')) {
             $this->container->scoped($abstract, $concrete);
        } else {
             $this->container->singleton($abstract, $concrete);
        }
    }

    public function extend($abstract, \Closure $closure)
    {
        $this->container->extend($abstract, $closure);
    }

    public function instance($abstract, $instance)
    {
        return $this->container->instance($abstract, $instance);
    }

    public function tag($abstracts, $tags)
    {
        $this->container->tag($abstracts, $tags);
    }

    public function when($concrete)
    {
        return $this->container->when($concrete);
    }

    public function factory($abstract)
    {
        return $this->container->factory($abstract);
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return $this->container->call($callback, $parameters, $defaultMethod);
    }

    public function resolved($abstract)
    {
        return $this->container->resolved($abstract);
    }

    public function resolving($abstract, $callback = null)
    {
        $this->container->resolving($abstract, $callback);
    }

    public function afterResolving($abstract, $callback = null)
    {
        $this->container->afterResolving($abstract, $callback);
    }

    public function bound($abstract)
    {
        return $this->container->bound($abstract);
    }

    public function alias($abstract, $alias)
    {
        $this->container->alias($abstract, $alias);
    }

    public function isShared($abstract)
    {
        return $this->container->isShared($abstract);
    }

    public function isAlias($name)
    {
        return $this->container->isAlias($name);
    }

    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        $this->container->addContextualBinding($concrete, $abstract, $implementation);
    }

    public function getBindings()
    {
        return $this->container->getBindings();
    }

    // ArrayAccess methods
    public function offsetExists($key): bool
    {
        return $this->container->offsetExists($key);
    }

    public function offsetGet($key): mixed
    {
        return $this->container->offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->container->offsetSet($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->container->offsetUnset($key);
    }

    // Magic __call to delegate everything else
    public function __call($method, $parameters)
    {
        return $this->container->$method(...$parameters);
    }
}
