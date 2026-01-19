<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get( string $key, mixed $default = null )
 * @method static void set( array|string $key, mixed $value = null )
 * @method static bool has( string $key )
 * @method static array all()
 *
 * @see \Illuminate\Config\Repository
 */
class Config extends Facade {
	protected static function getFacadeAccessor(): string {
		return 'config';
	}
}
