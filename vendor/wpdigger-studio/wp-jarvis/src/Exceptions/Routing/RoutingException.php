<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Routing;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * RoutingException - Exception for routing errors.
 */
class RoutingException extends FrameworkException {
	/**
	 * Create exception for route not found.
	 *
	 * @param string $uri The request URI.
	 * @param string $method The HTTP method.
	 *
	 * @return static The exception instance.
	 */
	public static function routeNotFound( string $uri, string $method = 'GET' ): static {
		return ( new static(
			sprintf(
			/* translators: 1: HTTP method, 2: URI path */
				__( "Route [%1\$s] '%2\$s' not found.", 'wp-jarvis' ),
				$method,
				$uri
			)
		) )
			->withContext( [ 'uri' => $uri, 'method' => $method ] );
	}

	/**
	 * Create exception for method not allowed.
	 *
	 * @param string $uri The request URI.
	 * @param string $method The HTTP method used.
	 * @param array<string> $allowed The allowed methods.
	 *
	 * @return static The exception instance.
	 */
	public static function methodNotAllowed( string $uri, string $method, array $allowed = [] ): static {
		$allowedStr = implode( ', ', $allowed );

		return ( new static(
			sprintf(
			/* translators: 1: HTTP method, 2: URI path, 3: allowed methods */
				__( "Method [%1\$s] not allowed for '%2\$s'. Allowed: [%3\$s].", 'wp-jarvis' ),
				$method,
				$uri,
				$allowedStr
			)
		) )
			->withContext( [ 'uri' => $uri, 'method' => $method, 'allowed' => $allowed ] );
	}

	/**
	 * Create exception for missing controller.
	 *
	 * @param string $controller The controller class.
	 *
	 * @return static The exception instance.
	 */
	public static function controllerNotFound( string $controller ): static {
		return ( new static(
			sprintf(
			/* translators: %s: controller class name */
				__( 'Controller class [%s] not found.', 'wp-jarvis' ),
				$controller
			)
		) )
			->withContext( [ 'controller' => $controller ] );
	}

	/**
	 * Create an exception for missing action.
	 *
	 * @param string $controller The controller class.
	 * @param string $action The action method.
	 *
	 * @return static The exception instance.
	 */
	public static function actionNotFound( string $controller, string $action ): static {
		return ( new static(
			sprintf(
			/* translators: 1: action method name, 2: controller class name */
				__( 'Action [%1$s] not found in controller [%2$s].', 'wp-jarvis' ),
				$action,
				$controller
			)
		) )
			->withContext( [ 'controller' => $controller, 'action' => $action ] );
	}

	/**
	 * Create an exception for middleware not found.
	 *
	 * @param string $middleware The middleware name.
	 *
	 * @return static The exception instance.
	 */
	public static function middlewareNotFound( string $middleware ): static {
		return ( new static(
			sprintf(
			/* translators: %s: middleware name */
				__( 'Middleware [%s] not found.', 'wp-jarvis' ),
				$middleware
			)
		) )
			->withContext( [ 'middleware' => $middleware ] );
	}

	/**
	 * Create exception for route parameter missing.
	 *
	 * @param string $parameter The parameter name.
	 * @param string $route The route pattern.
	 *
	 * @return static The exception instance.
	 */
	public static function parameterMissing( string $parameter, string $route ): static {
		return ( new static(
			sprintf(
			/* translators: 1: parameter name, 2: route pattern */
				__( 'Route parameter [%1$s] is required for route [%2$s].', 'wp-jarvis' ),
				$parameter,
				$route
			)
		) )
			->withContext( [ 'parameter' => $parameter, 'route' => $route ] );
	}

	/**
	 * Create exception for an invalid route pattern.
	 *
	 * @param string $pattern The route pattern.
	 * @param string $reason The reason.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidPattern( string $pattern, string $reason = '' ): static {
		$message = sprintf(
		/* translators: %s: route pattern */
			__( "Invalid route pattern: '%s'.", 'wp-jarvis' ),
			$pattern
		);
		if ( $reason ) {
			$message .= ' ' . sprintf(
				/* translators: %s: failure reason */
					__( 'Reason: %s', 'wp-jarvis' ),
					$reason
				);
		}

		return ( new static( $message ) )
			->withContext( [ 'pattern' => $pattern, 'reason' => $reason ] );
	}
}
