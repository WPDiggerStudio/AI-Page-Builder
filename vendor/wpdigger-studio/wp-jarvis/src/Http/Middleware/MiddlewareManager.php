<?php

namespace WPJarvis\Framework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use WPJarvis\Framework\Application;

/**
 * Middleware Manager
 *
 * Manages middleware registration and execution for HTTP requests.
 *
 * @package WPJarvis\Framework\Http\Middleware
 */
class MiddlewareManager {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	private Application $app;

	/**
	 * The global middleware stack.
	 *
	 * @var array<int, string|Closure>
	 */
	private array $middleware = [];

	/**
	 * The middleware groups.
	 *
	 * @var array<string, array<int, string|Closure>>
	 */
	private array $middlewareGroups = [
		'web' => [],
		'api' => [],
	];

	/**
	 * The route middleware.
	 *
	 * @var array<string, string|Closure>
	 */
	private array $routeMiddleware = [];

	/**
	 * The middleware aliases.
	 *
	 * @var array<string, string>
	 */
	private array $aliases = [];

	/**
	 * Create a new middleware manager instance.
	 *
	 * @param Application $app
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Register global middleware.
	 *
	 * @param string|Closure $middleware
	 *
	 * @return $this
	 */
	public function middleware( string|Closure $middleware ): self {
		$this->middleware[] = $middleware;

		return $this;
	}

	/**
	 * Register middleware group.
	 *
	 * @param string $group
	 * @param array<int, string|Closure> $middleware
	 *
	 * @return $this
	 */
	public function group( string $group, array $middleware ): self {
		$this->middlewareGroups[ $group ] = array_merge(
			$this->middlewareGroups[ $group ] ?? [],
			$middleware
		);

		return $this;
	}

	/**
	 * Register route middleware.
	 *
	 * @param string $name
	 * @param string|Closure $middleware
	 *
	 * @return $this
	 */
	public function route( string $name, string|Closure $middleware ): self {
		$this->routeMiddleware[ $name ] = $middleware;

		return $this;
	}

	/**
	 * Register middleware alias.
	 *
	 * @param string $alias
	 * @param string $middleware
	 *
	 * @return $this
	 */
	public function alias( string $alias, string $middleware ): self {
		$this->aliases[ $alias ] = $middleware;

		return $this;
	}

	/**
	 * Get all global middleware.
	 *
	 * @return array<int, string|Closure>
	 */
	public function getMiddleware(): array {
		return $this->middleware;
	}

	/**
	 * Get middleware group.
	 *
	 * @param string $group
	 *
	 * @return array<int, string|Closure>
	 */
	public function getGroup( string $group ): array {
		return $this->middlewareGroups[ $group ] ?? [];
	}

	/**
	 * Get route middleware.
	 *
	 * @param string $name
	 *
	 * @return string|Closure|null
	 */
	public function getRouteMiddleware( string $name ): string|Closure|null {
		return $this->routeMiddleware[ $name ] ?? null;
	}

	/**
	 * Get all route middleware.
	 *
	 * @return array<string, string|Closure>
	 */
	public function getRouteMiddlewareList(): array {
		return $this->routeMiddleware;
	}

	/**
	 * Get middleware alias.
	 *
	 * @param string $alias
	 *
	 * @return string|null
	 */
	public function getAlias( string $alias ): ?string {
		return $this->aliases[ $alias ] ?? null;
	}

	/**
	 * Resolve middleware.
	 *
	 * @param string|Closure $middleware
	 *
	 * @return Closure
	 */
	protected function resolveMiddleware( string|Closure $middleware ): Closure {
		if ( $middleware instanceof Closure ) {
			return $middleware;
		}

		// Check if it's an alias
		if ( isset( $this->aliases[ $middleware ] ) ) {
			$middleware = $this->aliases[ $middleware ];
		}

		// Resolve from container
		return function ( $request, $next ) use ( $middleware ) {
			return $this->app->make( $middleware )->handle( $request, $next );
		};
	}

	/**
	 * Send request through a middleware pipeline.
	 *
	 * @param Request $request
	 * @param Closure $destination
	 * @param array<int, string|Closure> $middleware
	 *
	 * @return mixed
	 */
	public function sendThroughPipeline( Request $request, Closure $destination, array $middleware = [] ): mixed {
		$middleware = array_merge( $this->middleware, $middleware );

		$pipeline = new Pipeline( $this->app );

		return $pipeline->send( $request )
		                ->through( array_map( [ $this, 'resolveMiddleware' ], $middleware ) )
		                ->then( $destination );
	}

	/**
	 * Send request through a middleware group.
	 *
	 * @param Request $request
	 * @param Closure $destination
	 * @param string $group
	 *
	 * @return mixed
	 */
	public function sendThroughGroup( Request $request, Closure $destination, string $group ): mixed {
		$middleware = $this->getGroup( $group );

		return $this->sendThroughPipeline( $request, $destination, $middleware );
	}
}
