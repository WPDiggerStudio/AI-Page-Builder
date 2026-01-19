<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Validation\Validator make( array $data, array $rules, array $messages = [], array $customAttributes = [] )
 * @method static void extend( string $rule, \Closure|string $extension, string|null $message = null )
 * @method static void extendImplicit( string $rule, \Closure|string $extension, string|null $message = null )
 * @method static void replacer( string $rule, \Closure|string $replacer )
 *
 * @see \Illuminate\Validation\Factory
 */
class Validator extends Facade {
	protected static function getFacadeAccessor(): string {
		return 'validator';
	}
}
