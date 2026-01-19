<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * @method static mixed get( string $key, mixed $default = null )
 * @method static bool put( string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null )
 * @method static bool forget( string $key )
 * @method static bool has( string $key )
 * @method static bool flush()
 * @method static mixed remember( string $key, \DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback )
 * @method static mixed rememberForever( string $key, \Closure $callback )
 * @method static \Illuminate\Cache\Repository store( string|null $name = null )
 *
 * @see \Illuminate\Cache\CacheManager
 */
class Cache extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'cache';
	}
}
