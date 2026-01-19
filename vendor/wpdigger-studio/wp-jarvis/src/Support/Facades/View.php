<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * @method static \Illuminate\Contracts\View\View make( string $view, array $data = [], array $mergeData = [] )
 * @method static bool exists( string $view )
 * @method static \Illuminate\Contracts\View\Factory share( array|string $key, $value = null )
 * @method static array composer( array|string $views, \Closure|string $callback )
 * @method static array creator( array|string $views, \Closure|string $callback )
 * @method static \Illuminate\View\Factory addNamespace( string $namespace, array|string $hints )
 *
 * @see \Illuminate\View\Factory
 */
class View extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'view';
	}
}
