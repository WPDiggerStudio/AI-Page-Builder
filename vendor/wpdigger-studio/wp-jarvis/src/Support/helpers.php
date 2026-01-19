<?php

declare( strict_types=1 );

/**
 * WP Jarvis Framework Helpers
 *
 * Global helper functions for the WP Jarvis framework.
 *
 * @package WPJarvis\Framework\Support
 */

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Support\AppRegistry;

if ( ! function_exists( 'wpj_app' ) ) {
	/**
	 * Get the available container instance.
	 *
	 * @param string|null $abstract
	 * @param array<string, mixed> $parameters
     * @param string|null $appKey
	 *
	 * @return mixed|Application
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_app( ?string $abstract = null, array $parameters = [], ?string $appKey = null ): mixed {
        // If appKey is provided, get that specific app
        if ($appKey !== null) {
            $app = AppRegistry::get($appKey);
        } elseif (defined('WPJARVIS_CURRENT_APP_KEY')) {
            // If inside a plugin context that defined the current app key
            $app = AppRegistry::get(WPJARVIS_CURRENT_APP_KEY);
        } else {
            // Fallback: If only one app exists, return it
            $apps = AppRegistry::all();
            if (count($apps) === 1) {
                $app = reset($apps);
            } else {
                // If abstract is provided, try to resolve it from the first app (legacy/fallback)
                // or throw exception if multiple apps exist and context is ambiguous
                 if (count($apps) > 1) {
                     throw new \RuntimeException('Ambiguous wpj_app() call. Multiple WP Jarvis apps registered. Please provide $appKey.');
                 }
                 // If no apps registered yet, we can't do anything
                 throw new \RuntimeException('No WP Jarvis application registered.');
            }
        }

		if ( $abstract === null ) {
			return $app;
		}

		return $app->make( $abstract, $parameters );
	}
}

if ( ! function_exists( 'wpj_config' ) ) {
	/**
	 * Get / set the specified configuration value.
	 *
	 * @param array<string, mixed>|string|null $key
	 * @param mixed $default
	 *
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_config( array|string|null $key = null, mixed $default = null ): mixed {
		if ( $key === null ) {
			return wpj_app( 'config' );
		}

		if ( is_array( $key ) ) {
			return wpj_app( 'config' )->set( $key );
		}

		return wpj_app( 'config' )->get( $key, $default );
	}
}

if ( ! function_exists( 'wpj_view' ) ) {
	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param string|null $view
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $mergeData
	 *
	 * @return View|ViewFactory
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_view( ?string $view = null, array $data = [], array $mergeData = [] ): View|ViewFactory {
		$factory = wpj_app( ViewFactory::class );

		if ( $view === null ) {
			return $factory;
		}

		return $factory->make( $view, $data, $mergeData );
	}
}

if ( ! function_exists( 'wpj_env' ) ) {
	/**
	 * Gets the value of an environment variable.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	function wpj_env( string $key, mixed $default = null ): mixed {
		$value = getenv( $key );

		if ( $value === false ) {
			return $default;
		}

		switch ( strtolower( $value ) ) {
			case 'true':
			case '(true)':
				return true;
			case 'false':
			case '(false)':
				return false;
			case 'empty':
			case '(empty)':
				return '';
			case 'null':
			case '(null)':
				return null;
		}

		if ( strlen( $value ) > 1 && str_starts_with( $value, '"' ) && str_ends_with( $value, '"' ) ) {
			return substr( $value, 1, - 1 );
		}

		return $value;
	}
}

if ( ! function_exists( 'wpj_base_path' ) ) {
	/**
	 * Get the path to the base of the installation.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_base_path( string $path = '' ): string {
		return wpj_app()->basePath( $path );
	}
}

if ( ! function_exists( 'wpj_config_path' ) ) {
	/**
	 * Get the configuration path.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_config_path( string $path = '' ): string {
		return wpj_app()->configPath( $path );
	}
}

if ( ! function_exists( 'storage_path' ) ) {
	/**
	 * Get the path to the storage folder.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function storage_path( string $path = '' ): string {
		return wpj_app()->storagePath( $path );
	}
}

if ( ! function_exists( 'wpj_resource_path' ) ) {
	/**
	 * Get the path to the resources' folder.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_resource_path( string $path = '' ): string {
		return wpj_app()->resourcePath( $path );
	}
}

if ( ! function_exists( 'wpj_public_path' ) ) {
	/**
	 * Get the path to the public folder.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_public_path( string $path = '' ): string {
		return wpj_app()->publicPath( $path );
	}
}

if ( ! function_exists( 'wpj_database_path' ) ) {
	/**
	 * Get the database path.
	 *
	 * @param string $path
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_database_path( string $path = '' ): string {
		return wpj_app()->databasePath( $path );
	}
}

if ( ! function_exists( 'wpj_event' ) ) {
	/**
	 * Dispatch an event and call the listeners.
	 *
	 * @param object|string $event
	 * @param mixed $payload
	 * @param bool $halt
	 *
	 * @return array|null
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_event( object|string $event, mixed $payload = [], bool $halt = false ): ?array {
		return wpj_app( 'events' )->dispatch( $event, $payload, $halt );
	}
}

if ( ! function_exists( 'wpj_cache' ) ) {
	/**
	 * Get / set the specified cache value.
	 *
	 * @param mixed ...$arguments
	 *
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	function wpj_cache( mixed ...$arguments ): mixed {
		if ( empty( $arguments ) ) {
			return wpj_app( 'cache' );
		}

		if ( is_string( $arguments[0] ) ) {
			return wpj_app( 'cache' )->get( ...$arguments );
		}

		if ( is_array( $arguments[0] ) ) {
			return wpj_app( 'cache' )->put(
				key( $arguments[0] ),
				reset( $arguments[0] ),
				$arguments[1] ?? null
			);
		}

		return wpj_app( 'cache' );
	}
}

if ( ! function_exists( 'wpj_logger' ) ) {
	/**
	 * Log a debug message to the logs.
	 *
	 * @param string|null $message
	 * @param array<string, mixed> $context
	 *
	 * @return \Psr\Log\LoggerInterface|null
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_logger( ?string $message = null, array $context = [] ): ?\Psr\Log\LoggerInterface {
		if ( $message === null ) {
			return wpj_app( 'log' );
		}

		return wpj_app( 'log' )->debug( $message, $context );
	}
}

if ( ! function_exists( 'wpj_info' ) ) {
	/**
	 * Write some information to the log.
	 *
	 * @param string $message
	 * @param array<string, mixed> $context
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_info( string $message, array $context = [] ): void {
		wpj_app( 'log' )->info( $message, $context );
	}
}

if ( ! function_exists( '__wpj' ) ) {
	/**
	 * Translate the given message.
	 *
	 * @param string|null $key
	 * @param array<string, mixed> $replace
	 * @param string|null $locale
	 *
	 * @return string|array|null
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	function __wpj( string $key = null, array $replace = [], string $locale = null ): string|array|null {
		if ( $key === null ) {
			return $key;
		}

		// Use WordPress translation if available
		if ( function_exists( '__' ) ) {
			$textdomain = wpj_config( 'app.textdomain', 'wp-jarvis' );

			return \__( $key, $textdomain );
		}

		return wpj_app( 'translator' )->get( $key, $replace, $locale );
	}
}

if ( ! function_exists( 'wpj_trans' ) ) {
	/**
	 * Translate the given message.
	 *
	 * @param string $key
	 * @param array<string, mixed> $replace
	 * @param string|null $locale
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	function wpj_trans( string $key, array $replace = [], string $locale = null ): string {
		return wpj_app( 'translator' )->get( $key, $replace, $locale );
	}
}

if ( ! function_exists( 'wpj_validator' ) ) {
	/**
	 * Create a new Validator instance.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $rules
	 * @param array<string, string> $messages
	 * @param array<string, string> $customAttributes
	 *
	 * @return \Illuminate\Validation\Validator|\Illuminate\Validation\Factory
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_validator(
		array $data = [],
		array $rules = [],
		array $messages = [],
		array $customAttributes = []
	): \Illuminate\Validation\Validator|\Illuminate\Validation\Factory {
		$factory = wpj_app( 'validator' );

		if ( func_num_args() === 0 ) {
			return $factory;
		}

		return $factory->make( $data, $rules, $messages, $customAttributes );
	}
}

if ( ! function_exists( 'wpj_abort' ) ) {
	/**
	 * Throw an HttpException with the given data.
	 *
	 * @param int $code
	 * @param string $message
	 * @param array<string, mixed> $headers
	 *
	 * @return never
	 */
	function wpj_abort( int $code, string $message = '', array $headers = [] ): never {
		if ( function_exists( 'wp_die' ) ) {
			wp_die( $message, $code );
		}

		throw new \RuntimeException( $message, $code );
	}
}

if ( ! function_exists( 'wpj_abort_if' ) ) {
	/**
	 * Throw an HttpException with the given data if the given condition is true.
	 *
	 * @param bool $condition
	 * @param int $code
	 * @param string $message
	 * @param array<string, mixed> $headers
	 *
	 * @return void
	 */
	function wpj_abort_if( bool $condition, int $code, string $message = '', array $headers = [] ): void {
		if ( $condition ) {
			wpj_abort( $code, $message, $headers );
		}
	}
}

if ( ! function_exists( 'wpj_abort_unless' ) ) {
	/**
	 * Throw an HttpException with the given data unless the given condition is true.
	 *
	 * @param bool $condition
	 * @param int $code
	 * @param string $message
	 * @param array<string, mixed> $headers
	 *
	 * @return void
	 */
	function wpj_abort_unless( bool $condition, int $code, string $message = '', array $headers = [] ): void {
		if ( ! $condition ) {
			wpj_abort( $code, $message, $headers );
		}
	}
}

if ( ! function_exists( 'wpj_now' ) ) {
	/**
	 * Create a new Carbon instance for the current time.
	 *
	 * @param \DateTimeZone|string|null $tz
	 *
	 * @return \Illuminate\Support\Carbon
	 */
	function wpj_now( \DateTimeZone|string $tz = null ): \Illuminate\Support\Carbon {
		return \Illuminate\Support\Carbon::now( $tz );
	}
}

if ( ! function_exists( 'wpj_today' ) ) {
	/**
	 * Create a new Carbon instance for the current date.
	 *
	 * @param \DateTimeZone|string|null $tz
	 *
	 * @return \Illuminate\Support\Carbon
	 */
	function wpj_today( \DateTimeZone|string $tz = null ): \Illuminate\Support\Carbon {
		return \Illuminate\Support\Carbon::today( $tz );
	}
}

if ( ! function_exists( 'wpj_data_get' ) ) {
	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param mixed $target
	 * @param string|array<string>|int|null $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	function wpj_data_get( mixed $target, string|array|int|null $key, mixed $default = null ): mixed {
		if ( $key === null ) {
			return $target;
		}

		$key = is_array( $key ) ? $key : explode( '.', (string) $key );

		foreach ( $key as $segment ) {
			if ( is_array( $target ) && array_key_exists( $segment, $target ) ) {
				$target = $target[ $segment ];
			} elseif ( is_object( $target ) && isset( $target->{$segment} ) ) {
				$target = $target->{$segment};
			} else {
				return $default;
			}
		}

		return $target;
	}
}

if ( ! function_exists( 'wpj_data_set' ) ) {
	/**
	 * Set an item on an array or object using dot notation.
	 *
	 * @param mixed $target
	 * @param string|array<string> $key
	 * @param mixed $value
	 * @param bool $overwrite
	 *
	 * @return mixed
	 */
	function wpj_data_set( mixed &$target, string|array $key, mixed $value, bool $overwrite = true ): mixed {
		$segments = is_array( $key ) ? $key : explode( '.', $key );
		$segment  = array_shift( $segments );

		if ( is_array( $target ) ) {
			if ( $segments ) {
				if ( ! array_key_exists( $segment, $target ) ) {
					$target[ $segment ] = [];
				}

				data_set( $target[ $segment ], $segments, $value, $overwrite );
			} elseif ( $overwrite || ! array_key_exists( $segment, $target ) ) {
				$target[ $segment ] = $value;
			}
		} elseif ( is_object( $target ) ) {
			if ( $segments ) {
				if ( ! isset( $target->{$segment} ) ) {
					$target->{$segment} = [];
				}

				data_set( $target->{$segment}, $segments, $value, $overwrite );
			} elseif ( $overwrite || ! isset( $target->{$segment} ) ) {
				$target->{$segment} = $value;
			}
		}

		return $target;
	}
}

if ( ! function_exists( 'wpj_collect' ) ) {
	/**
	 * Create a collection from the given value.
	 *
	 * @param mixed $value
	 *
	 * @return \Illuminate\Support\Collection
	 */
	function wpj_collect( mixed $value = [] ): \Illuminate\Support\Collection {
		return new \Illuminate\Support\Collection( $value );
	}
}

if ( ! function_exists( 'wpj_value' ) ) {
	/**
	 * Return the default value of the given value.
	 *
	 * @param mixed $value
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	function wpj_value( mixed $value, mixed ...$args ): mixed {
		return $value instanceof Closure ? $value( ...$args ) : $value;
	}
}

if ( ! function_exists( 'wpj_filled' ) ) {
	/**
	 * Determine if a value is "filled".
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	function wpj_filled( mixed $value ): bool {
		return ! blank( $value );
	}
}

if ( ! function_exists( 'wpj_blank' ) ) {
	/**
	 * Determine if the given value is "blank".
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	function wpj_blank( mixed $value ): bool {
		if ( $value === null ) {
			return true;
		}

		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		if ( is_numeric( $value ) || is_bool( $value ) ) {
			return false;
		}

		if ( $value instanceof Countable ) {
			return count( $value ) === 0;
		}

		return empty( $value );
	}
}

if ( ! function_exists( 'wpj_optional' ) ) {
	/**
	 * Provide access to optional objects.
	 *
	 * @param mixed $value
	 * @param callable|null $callback
	 *
	 * @return mixed
	 */
	function wpj_optional( mixed $value = null, callable $callback = null ): mixed {
		if ( $callback === null ) {
			return new class( $value ) {
				private mixed $value;

				public function __construct( mixed $value ) {
					$this->value = $value;
				}

				public function __get( string $name ): mixed {
					if ( $this->value === null ) {
						return null;
					}

					if ( is_array( $this->value ) ) {
						return $this->value[ $name ] ?? null;
					}

					if ( is_object( $this->value ) ) {
						return $this->value->{$name} ?? null;
					}

					return null;
				}

				/**
				 * Optional proxy is intentionally read-only.
				 *
				 * @param string $name
				 * @param mixed $value
				 */
				public function __set( string $name, mixed $value ): void {
					// Intentionally no-op (or throw LogicException if you want strict behavior).
				}

				/**
				 * Support `isset(wpj_optional($x)->prop)` safely.
				 *
				 * @param string $name
				 *
				 * @return bool
				 */
				public function __isset( string $name ): bool {
					if ( $this->value === null ) {
						return false;
					}

					if ( is_array( $this->value ) ) {
						return array_key_exists( $name, $this->value ) && $this->value[ $name ] !== null;
					}

					if ( is_object( $this->value ) ) {
						return isset( $this->value->{$name} );
					}

					return false;
				}

				/**
				 * Support `unset()` without mutating underlying value.
				 *
				 * @param string $name
				 */
				public function __unset( string $name ): void {
					// Intentionally no-op to keep proxy read-only.
				}

				public function __call( string $name, array $arguments ): mixed {
					if ( $this->value === null ) {
						return null;
					}

					if ( ! is_object( $this->value ) || ! is_callable( [ $this->value, $name ] ) ) {
						return null;
					}

					return $this->value->{$name}( ...$arguments );
				}
			};
		}

		if ( $value !== null ) {
			return $callback( $value );
		}

		return null;
	}
}

if ( ! function_exists( 'wpj_tap' ) ) {
	/**
	 * Call the given Closure with the given value then return the value.
	 *
	 * @param mixed $value
	 * @param callable|null $callback
	 *
	 * @return object
	 */
	function wpj_tap( mixed $value, callable $callback = null ): object {
		if ( $callback === null ) {
			return new class( $value ) {
				public mixed $target;

				public function __construct( mixed $target ) {
					$this->target = $target;
				}

				public function __call( string $method, array $parameters ): mixed {
					$this->target->{$method}( ...$parameters );

					return $this->target;
				}
			};
		}

		$callback( $value );

		return $value;
	}
}

if ( ! function_exists( 'wpj_throw_if' ) ) {
	/**
	 * Throw the given exception if the given condition is true.
	 *
	 * @param mixed $condition
	 * @param \Throwable|string $exception
	 * @param mixed ...$parameters
	 *
	 * @return mixed
	 * @throws \Throwable
	 */
	function wpj_throw_if( mixed $condition, \Throwable|string $exception = 'RuntimeException', mixed ...$parameters ): mixed {
		if ( $condition ) {
			if ( is_string( $exception ) && class_exists( $exception ) ) {
				$exception = new $exception( ...$parameters );
			}

			throw is_string( $exception ) ? new \RuntimeException( $exception ) : $exception;
		}

		return $condition;
	}
}

if ( ! function_exists( 'wpj_throw_unless' ) ) {
	/**
	 * Throw the given exception unless the given condition is true.
	 *
	 * @param mixed $condition
	 * @param \Throwable|string $exception
	 * @param mixed ...$parameters
	 *
	 * @return mixed
	 * @throws \Throwable
	 */
	function wpj_throw_unless( mixed $condition, \Throwable|string $exception = 'RuntimeException', mixed ...$parameters ): mixed {
		throw_if( ! $condition, $exception, ...$parameters );

		return $condition;
	}
}

if ( ! function_exists( 'wpj_rescue' ) ) {
	/**
	 * Catch a potential exception and return a default value.
	 *
	 * @param callable $callback
	 * @param mixed $rescue
	 * @param bool $report
	 *
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_rescue( callable $callback, mixed $rescue = null, bool $report = true ): mixed {
		try {
			return $callback();
		} catch ( \Throwable $e ) {
			if ( $report ) {
				wpj_logger()?->warning( 'WP Jarvis Rescue: ' . $e->getMessage(), [ 'exception' => $e ] );
			}

			return value( $rescue, $e );
		}
	}
}

if ( ! function_exists( 'wpj_retry' ) ) {
	/**
	 * Retry an operation a given number of times.
	 *
	 * @param int $times
	 * @param callable $callback
	 * @param int $sleepMilliseconds
	 * @param callable|null $when
	 *
	 * @return mixed
	 * @throws \Throwable
	 */
	function wpj_retry( int $times, callable $callback, int $sleepMilliseconds = 0, callable $when = null ): mixed {
		$attempts = 0;

		beginning:
		$attempts ++;
		$times --;

		try {
			return $callback( $attempts );
		} catch ( \Throwable $e ) {
			if ( $times < 1 || ( $when && ! $when( $e ) ) ) {
				throw $e;
			}

			if ( $sleepMilliseconds > 0 ) {
				usleep( $sleepMilliseconds * 1000 );
			}

			goto beginning;
		}
	}
}

if ( ! function_exists( 'wpj_redirect' ) ) {
	/**
	 * Create a new redirect response to the given path.
	 *
	 * @param string $path
	 * @param int $status
	 * @param array<string, string> $headers
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_redirect( string $path, int $status = 302, array $headers = [] ): \Illuminate\Http\RedirectResponse {
		return wpj_app( 'redirect' )->to( $path, $status, $headers );
	}
}

if ( ! function_exists( 'wpj_response' ) ) {
	/**
	 * Return a new response from the application.
	 *
	 * @param string|array<string, mixed>|null $content
	 * @param int $status
	 * @param array<string, string> $headers
	 *
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	function wpj_response( string|array|null $content = '', int $status = 200, array $headers = [] ): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse {
		$factory = wpj_app( 'response' );

		if ( is_array( $content ) ) {
			return $factory->json( $content, $status, $headers );
		}

		return $factory->make( $content ?? '', (array) $status, $headers );
	}
}
