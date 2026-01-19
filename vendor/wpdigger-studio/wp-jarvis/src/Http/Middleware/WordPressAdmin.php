<?php

namespace WPJarvis\Framework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * WordPress Admin Middleware
 *
 * Ensures the user is an administrator.
 *
 * @package WPJarvis\Framework\Http\Middleware
 */
class WordPressAdmin {
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param Closure $next
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function handle( Request $request, Closure $next ): mixed {
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( $request->expectsJson() ) {
				return wpj_response( [
					'error'   => 'Forbidden',
					'message' => __( 'You do not have permission to access this resource.', 'wp-jarvis' ),
				], 403 );
			}

			wpj_abort( 403, __( 'You do not have permission to access this resource.', 'wp-jarvis' ) );
		}

		return $next( $request );
	}
}
