<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Foundation;

use Closure;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\Facades\Log;

/**
 * ServiceProvider - Base class for all WP Jarvis service providers.
 *
 * This class extends Laravel's ServiceProvider with WordPress-specific functionality
 * and convenience methods. Most Laravel methods are inherited directly from the parent.
 */
abstract class ServiceProvider extends BaseServiceProvider
{
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected bool $defer = false;

	/**
	 * Create a new service provider instance.
	 *
	 * @param Application $app
	 */
	public function __construct($app)
	{
		$this->app = $app;

		parent::__construct($app);
	}

	/**
	 * Register any application services.
	 *
	 * Child classes should override this method to register their services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Default implementation
	}

	/**
	 * Bootstrap any application services.
	 *
	 * Child classes should override this method to bootstrap their services.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Default implementation
	}

	/**
	 * Convenience method to register a singleton service.
	 *
	 * @param string $abstract
	 * @param Closure|string|null $concrete
	 *
	 * @return void
	 */
	protected function singleton(string $abstract, Closure|string $concrete = null): void
	{
		$this->app->singleton($abstract, $concrete);
	}

	/**
	 * Convenience method to register a binding in the container.
	 *
	 * @param string $abstract
	 * @param Closure|string|null $concrete
	 *
	 * @return void
	 */
	protected function bind(string $abstract, Closure|string $concrete = null): void
	{
		$this->app->bind($abstract, $concrete);
	}

	/**
	 * Convenience method to register an alias for a binding.
	 *
	 * @param string $abstract
	 * @param string $alias
	 *
	 * @return void
	 */
	protected function alias(string $abstract, string $alias): void
	{
		$this->app->alias($abstract, $alias);
	}

	/**
	 * Merge a config file into the application's config repository.
	 *
	 * WordPress-specific implementation with error handling and i18n support.
	 * This should be called in the boot() method, not register().
	 *
	 * @param string $path Path to the config file (must return array)
	 * @param string $key Root config key (e.g. 'app', 'mail', etc.)
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function mergeConfigFrom($path, $key): void
	{
		if (!file_exists($path)) {
			throw new \RuntimeException(
				sprintf(
					__('Config file not found at: %s', 'wp-jarvis'),
					$path
				)
			);
		}

		$merge = require $path;

		if (!is_array($merge)) {
			throw new \RuntimeException(
				sprintf(
					__('Expected config file to return an array at: %s', 'wp-jarvis'),
					$path
				)
			);
		}

		/**
		 * Safely merge config during the boot phase
		 */
		try {
			$config = $this->app->make('config');
			$existing = $config->get($key, []);
			$config->set($key, array_merge($merge, $existing));
		} catch (\Exception $e) {
			Log::warning(
				__('Failed to merge config - ', 'wp-jarvis') . $e->getMessage(),
				['exception' => $e]
			);
		}
	}

	/**
	 * Publish files to allow customization (alias for consistency).
	 *
	 * @param array $paths
	 * @param string|null $group
	 *
	 * @return void
	 */
	protected function publish(array $paths, ?string $group = null): void
	{
		$this->publishes($paths, $group);
	}

	/**
	 * Determine if the provider is deferred.
	 *
	 * @return bool
	 */
	public function isDeferred(): bool
	{
		return $this->defer;
	}

	/**
	 * Get the base path of the application.
	 *
	 * Convenience method for better readability.
	 *
	 * @param string $path Optional path to append
	 *
	 * @return string
	 */
	protected function basePath(string $path = ''): string
	{
		return $this->app->basePath($path);
	}

	/**
	 * Get the path to the application configuration files.
	 *
	 * Convenience method for better readability.
	 *
	 * @param string $path Optional path to append
	 *
	 * @return string
	 */
	protected function configPath(string $path = ''): string
	{
		return $this->app->configPath($path);
	}

	/**
	 * Get the path to the resources' directory.
	 *
	 * Convenience method for better readability.
	 *
	 * @param string $path Optional path to append
	 *
	 * @return string
	 */
	protected function resourcePath(string $path = ''): string
	{
		return $this->app->resourcePath($path);
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * Convenience method for better readability.
	 *
	 * @param string $path Optional path to append
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function storagePath(string $path = ''): string
	{
		return $this->app->storagePath($path);
	}

	/**
	 * Load a configuration file into the application's config repository.
	 *
	 * WordPress-specific helper to load config files with null-safe operator.
	 *
	 * @param string $path Path to the config file (must return an array)
	 * @param string $key Config key to store the configuration under
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function loadConfig(string $path, string $key): void
	{
		if (file_exists($path)) {
			$this->app->make('config')?->set($key, require $path);
		}
	}

	/**
	 * Register a view file namespace.
	 *
	 * WordPress-specific implementation with bound checking and null-safe operator.
	 * This method adds a namespace to the view factory, allowing views
	 * to be loaded from the specified path using the namespace prefix.
	 *
	 * @param string $path Path to the view directory
	 * @param string $namespace Namespace identifier for the views
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function loadViews(string $path, string $namespace): void
	{
		if ($this->app->bound('view')) {
			$this->app->make('view')?->addNamespace($namespace, $path);
		}
	}
}
