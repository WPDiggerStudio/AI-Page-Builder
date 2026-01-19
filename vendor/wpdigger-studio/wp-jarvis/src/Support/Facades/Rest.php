<?php

namespace WPJarvis\Framework\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Rest Facade
 *
 * Facade for registering WordPress REST API routes with a fluent interface.
 * Provides convenient static methods for defining REST endpoints in your application.
 *
 * Usage:
 * Rest::get('example', 'ExampleController@index');
 * Rest::post('items', 'ItemController@store', 'create_items');
 * Rest::put('items/{id}', 'ItemController@update', 'edit_items');
 * Rest::delete('items/{id}', 'ItemController@destroy', 'delete_items');
 *
 * @method static void get( string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void post( string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void put( string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void patch( string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void delete( string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void options( string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void add( string $method, string $uri, callable|string|array $callback, callable|string|array $permission = '__return_true', array $args = [] )
 * @method static void register()
 *
 * @see \WPJarvis\Framework\Core\Routing\RestRouteBridge
 */
class Rest extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor(): string {
		return 'rest.route.bridge';
	}
}
