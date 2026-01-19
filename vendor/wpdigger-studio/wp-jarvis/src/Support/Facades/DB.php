<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * @method static \Illuminate\Database\Connection connection( string|null $name = null )
 * @method static \Illuminate\Database\Query\Builder table( string $table, string|null $as = null )
 * @method static mixed select( string $query, array $bindings = [], bool $useReadPdo = true )
 * @method static bool insert( string $query, array $bindings = [] )
 * @method static int update( string $query, array $bindings = [] )
 * @method static int delete( string $query, array $bindings = [] )
 * @method static bool statement( string $query, array $bindings = [] )
 * @method static mixed transaction( \Closure $callback, int $attempts = 1 )
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void rollBack( int|null $toLevel = null )
 *
 * @see \Illuminate\Database\DatabaseManager
 */
class DB extends ScopedFacade {
	protected static function getFacadeAccessor(): string {
		return 'db';
	}
}
