<?php

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * Route Facade
 *
 * Facade for registering web routes with a fluent interface.
 * Provides convenient static methods for defining routes in your application.
 *
 * Usage:
 * Route::get('/example', 'ExampleController@index');
 * Route::post('/items', 'ItemController@store');
 * Route::put('/items/{id}', 'ItemController@update');
 * Route::delete('/items/{id}', 'ItemController@destroy');
 * Route::middleware(['auth'])->group(function() { ... });
 *
 * @method static void get( string $uri, callable|array|string $action )
 * @method static void post( string $uri, callable|array|string $action )
 * @method static void put( string $uri, callable|array|string $action )
 * @method static void patch( string $uri, callable|array|string $action )
 * @method static void delete( string $uri, callable|array|string $action )
 * @method static void options( string $uri, callable|array|string $action )
 * @method static void any( string $uri, callable|array|string $action )
 * @method static void match( array|string $methods, string $uri, callable|array|string $action )
 * @method static void prefix( string $prefix )
 * @method static \Illuminate\Routing\PendingResourceRegistration resource( string $name, callable|array|string $controller = null, array $options = [] )
 * @method static \Illuminate\Routing\PendingResourceRegistration apiResource( string $name, callable|array|string $controller = null, array $options = [] )
 * @method static void group( array $attributes, callable|array|string $routes )
 * @method static void middleware( array|string $middleware )
 * @method static \Illuminate\Routing\Route current()
 * @method static \Illuminate\Routing\Route|null getCurrentRoute()
 * @method static bool has( string $name )
 *
 * @see \Illuminate\Routing\Router
 */
class Route extends ScopedFacade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor(): string {
		return 'router';
	}
}
