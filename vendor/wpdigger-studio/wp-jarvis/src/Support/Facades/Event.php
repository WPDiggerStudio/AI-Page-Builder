<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * @method static void listen( string|array $events, $listener = null )
 * @method static void subscribe( $subscriber )
 * @method static array|null dispatch( object|string $event, mixed $payload = [], bool $halt = false )
 * @method static void forget( string $event )
 *
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'events';
	}
}
