<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * @method static void emergency( string $message, array $context = [] )
 * @method static void alert( string $message, array $context = [] )
 * @method static void critical( string $message, array $context = [] )
 * @method static void error( string $message, array $context = [] )
 * @method static void warning( string $message, array $context = [] )
 * @method static void notice( string $message, array $context = [] )
 * @method static void info( string $message, array $context = [] )
 * @method static void debug( string $message, array $context = [] )
 * @method static void log( string $level, string $message, array $context = [] )
 *
 * @see \Illuminate\Log\LogManager
 */
class Log extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'log';
	}
}
