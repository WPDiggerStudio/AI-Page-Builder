<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;
use WPJarvis\Framework\WP\Hooks\Hooks as HooksManager;

/**
 * WordPress Hooks facade.
 *
 * @method static void action( string $hook, callable|array $callback, ?int $priority = null, int $acceptedArgs = 1 )
 * @method static void filter( string $hook, callable|array $callback, int $priority = 10, int $acceptedArgs = 1 )
 * @method static void removeAction( string $hook, callable|array $callback, int $priority = 10 )
 * @method static void removeFilter( string $hook, callable|array $callback, int $priority = 10 )
 * @method static void doAction( string $hook, mixed ...$args )
 * @method static mixed applyFilters( string $hook, mixed $value, mixed ...$args )
 * @method static bool|int hasAction( string $hook, callable|array|string|null $callback = null )
 * @method static bool|int hasFilter( string $hook, callable|array|string|null $callback = null )
 * @method static array getRegisteredHooks()
 * @method static array getRegisteredActions()
 * @method static array getRegisteredFilters()
 * @method static array getAllWordPressHooks()
 *
 * @see HooksManager
 * @mixin HooksManager
 */
class Hooks extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'hooks';
	}
}
