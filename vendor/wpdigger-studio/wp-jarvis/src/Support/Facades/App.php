<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * @method static mixed make( string $abstract, array $parameters = [] )
 * @method static mixed get( string $id )
 * @method static bool has( string $id )
 * @method static void bind( string $abstract, $concrete = null, bool $shared = false )
 * @method static void singleton( string $abstract, $concrete = null )
 * @method static void instance( string $abstract, $instance )
 * @method static string basePath( string $path = '' )
 * @method static string configPath( string $path = '' )
 * @method static string resourcePath( string $path = '' )
 * @method static string storagePath( string $path = '' )
 * @method static string databasePath( string $path = '' )
 *
 * @see \WPJarvis\Framework\Application
 */
class App extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'app';
	}
}
