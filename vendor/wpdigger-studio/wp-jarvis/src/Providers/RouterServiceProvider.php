<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * Router Service Provider
 *
 * Registers REST API routes and admin routes.
 *
 * @package WPJarvis\Framework\Providers
 */
class RouterServiceProvider extends ServiceProvider {
	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected string $namespace = 'WpJarvis/v1';

	/**
	 * Register the service provider.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function register(): void {
		// Load namespace from config
		$this->namespace = $this->app->make( 'config' )->get( 'api.namespace', 'WpJarvis/v1' );
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register REST API routes
		add_action( 'rest_api_init', [ $this, 'registerRestRoutes' ] );

		// Register admin AJAX handlers
		add_action( 'admin_init', [ $this, 'registerAdminRoutes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function registerRestRoutes(): void {
		$routesFile = $this->app->routesPath( 'api.php' );

		if ( file_exists( $routesFile ) ) {
			$router = $this;
			require $routesFile;
		}
	}

	/**
	 * Register admin routes.
	 *
	 * @return void
	 */
	public function registerAdminRoutes(): void {
		$routesFile = $this->app->routesPath( 'admin.php' );

		if ( file_exists( $routesFile ) ) {
			require $routesFile;
		}
	}

	/**
	 * Register a REST route.
	 *
	 * @param string $route
	 * @param array<string, mixed> $args
	 * @param string|null $namespace
	 *
	 * @return void
	 */
	public function route( string $route, array $args, ?string $namespace = null ): void {
		register_rest_route( $namespace ?? $this->namespace, $route, $args );
	}

	/**
	 * Register a GET route.
	 *
	 * @param string $route
	 * @param callable|array $callback
	 * @param array<string, mixed> $args
	 *
	 * @return void
	 */
	public function get( string $route, callable|array $callback, array $args = [] ): void {
		$this->route( $route, array_merge( [
			'methods'             => 'GET',
			'callback'            => $this->resolveCallback( $callback ),
			'permission_callback' => $args['permission_callback'] ?? '__return_true',
		], $args ) );
	}

	/**
	 * Register a POST route.
	 *
	 * @param string $route
	 * @param callable|array $callback
	 * @param array<string, mixed> $args
	 *
	 * @return void
	 */
	public function post( string $route, callable|array $callback, array $args = [] ): void {
		$this->route( $route, array_merge( [
			'methods'             => 'POST',
			'callback'            => $this->resolveCallback( $callback ),
			'permission_callback' => $args['permission_callback'] ?? '__return_true',
		], $args ) );
	}

	/**
	 * Register a PUT route.
	 *
	 * @param string $route
	 * @param callable|array $callback
	 * @param array<string, mixed> $args
	 *
	 * @return void
	 */
	public function put( string $route, callable|array $callback, array $args = [] ): void {
		$this->route( $route, array_merge( [
			'methods'             => 'PUT',
			'callback'            => $this->resolveCallback( $callback ),
			'permission_callback' => $args['permission_callback'] ?? '__return_true',
		], $args ) );
	}

	/**
	 * Register a DELETE route.
	 *
	 * @param string $route
	 * @param callable|array $callback
	 * @param array<string, mixed> $args
	 *
	 * @return void
	 */
	public function delete( string $route, callable|array $callback, array $args = [] ): void {
		$this->route( $route, array_merge( [
			'methods'             => 'DELETE',
			'callback'            => $this->resolveCallback( $callback ),
			'permission_callback' => $args['permission_callback'] ?? '__return_true',
		], $args ) );
	}

	/**
	 * Resolve callback to a callable.
	 *
	 * @param callable|array $callback
	 *
	 * @return callable
	 */
	private function resolveCallback( callable|array $callback ): callable {
		if ( is_callable( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			[ $class, $method ] = $callback;

			if ( is_string( $class ) && class_exists( $class ) ) {
				return function ( $request ) use ( $class, $method ) {
					return $this->app->make( $class )->$method( $request );
				};
			}
		}

		return $callback;
	}

	/**
	 * Get the REST namespace.
	 *
	 * @return string
	 */
	public function getNamespace(): string {
		return $this->namespace;
	}
}
