<?php

namespace WPJarvis\Framework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * WordPress Authentication Middleware
 *
 * Ensures the user is logged into WordPress.
 *
 * @package WPJarvis\Framework\Http\Middleware
 */
class WordPressAuth {
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param Closure $next
	 *
	 * @return mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function handle( Request $request, Closure $next ): mixed {
		if ( ! is_user_logged_in() ) {
			if ( $request->expectsJson() ) {
				return wpj_response( [
					'error'   => 'Unauthorized',
					'message' => __( 'You must be logged in to access this resource.', 'wp-jarvis' ),
				], 401 );
			}

			return wpj_redirect( wp_login_url( $request->fullUrl() ) );
		}

		return $next( $request );
	}
}
