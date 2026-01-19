<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Content;

use Illuminate\Filesystem\Filesystem;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\Facades\Log;

/**
 * AutoDiscovery - Automatically discovers and registers content types.
 *
 * Scans app/PostTypes, app/Taxonomies, and app/Metaboxes directories for classes
 * and automatically registers them with WordPress.
 *
 * @package WPJarvis\Framework\WP\Content
 */
class AutoDiscovery {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected Application $app;

	/**
	 * The filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected Filesystem $files;

	/**
	 * The root namespace.
	 *
	 * @var string
	 */
	protected string $rootNamespace;

	/**
	 * Create a new AutoDiscovery instance.
	 *
	 * @param Application $app The application instance.
	 * @param Filesystem $files The filesystem instance.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct( Application $app, Filesystem $files ) {
		$this->app           = $app;
		$this->files         = $files;
		$this->rootNamespace = $app->getNamespace();

	}

	/**
	 * Discover and register all content types.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverAll(): void {
		$this->discoverPostTypes();
		$this->discoverTaxonomies();
		$this->discoverMetaboxes();
		$this->discoverWidgets();
		$this->discoverSettings();
		$this->discoverBlocks();
		$this->discoverColumns();
		$this->discoverMenus();
	}

	/**
	 * Discover and register all post-types.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverPostTypes(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\PostTypes';
		$directory = $this->app->appPath( 'WordPress\PostTypes' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );

		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all taxonomies.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverTaxonomies(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Taxonomies';
		$directory = $this->app->appPath( 'WordPress\Taxonomies' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );

		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all metaboxes.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverMetaboxes(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Metaboxes';
		$directory = $this->app->appPath( 'WordPress\Metaboxes' );


		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );
		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all widgets.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverWidgets(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Widgets';
		$directory = $this->app->appPath( 'WordPress\Widgets' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );
		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all settings pages.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverSettings(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Admin\\Settings';
		$directory = $this->app->appPath( 'WordPress\Admin\Settings' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );

		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all blocks.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverBlocks(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Blocks';
		$directory = $this->app->appPath( 'WordPress\Blocks' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );
		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all admin columns.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverColumns(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Admin\\Columns';
		$directory = $this->app->appPath( 'WordPress\Admin\Columns' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );
		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all admin menus.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverMenus(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Admin\\Menus';
		$directory = $this->app->appPath( 'WordPress\Admin\Menus' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );
		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover and register all shortcodes.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function discoverShortcodes(): void {
		$namespace = $this->rootNamespace . '\\WordPress\\Shortcodes';
		$directory = $this->app->appPath( 'WordPress\Shortcodes' );

		if ( ! $this->files->isDirectory( $directory ) ) {
			return;
		}

		$classes = $this->discoverClasses( $directory, $namespace );

		foreach ( $classes as $className ) {
			$this->registerClass( $className );
		}
	}

	/**
	 * Discover all classes in a directory.
	 *
	 * @param string $directory The directory to scan.
	 * @param string $namespace The base namespace for the directory.
	 *
	 * @return array<string> The discovered class names.
	 */
	protected function discoverClasses( string $directory, string $namespace ): array {
		$classes = [];
		$files   = $this->files->files( $directory );

		foreach ( $files as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}

			$className = $namespace . '\\' . $file->getBasename( '.php' );

			// Skip abstract classes and interfaces
			if ( $this->isAbstractOrInterface( $className ) ) {
				continue;
			}

			$classes[] = $className;
		}

		return $classes;
	}

	/**
	 * Check if a class is abstract or an interface.
	 *
	 * @param string $className The class name to check.
	 *
	 * @return bool True if abstract or interface.
	 */
	protected function isAbstractOrInterface( string $className ): bool {
		if ( ! class_exists( $className ) && ! interface_exists( $className ) ) {
			return false;
		}

		$reflection = new \ReflectionClass( $className );

		return $reflection->isAbstract() || $reflection->isInterface();
	}

	/**
	 * Instantiate and register a class.
	 *
	 * @param string $className The class name to register.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function registerClass( string $className ): void {
		try {
			if ( ! class_exists( $className ) ) {
				return;
			}

			$instance = new $className();

			if ( method_exists( $instance, 'register' ) ) {
				$instance->register();
			}
		} catch ( \Throwable $e ) {
			// Log error but don't break the application
			Log::error(
				__( 'Failed to register content type: ', 'wp-jarvis' ) . $className,
				[ 'exception' => $e->getMessage() ]
			);
		}
	}
}
