<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Http;

use WPJarvis\Framework\Validation\Validator;
use WPJarvis\Framework\WP\Http\Response;

/**
 * Controller - Base controller class.
 *
 * Provides common functionality for HTTP controllers.
 */
abstract class Controller {
	/**
	 * The middleware registered on the controller.
	 *
	 * @var array<array>
	 */
	protected array $middleware = [];

	/**
	 * Register middleware on the controller.
	 *
	 * @param string|array $middleware
	 * @param array $options
	 *
	 * @return static
	 */
	public function middleware( string|array $middleware, array $options = [] ): static {
		// Cast to array directly to avoid the ternary check
		foreach ( (array) $middleware as $m ) {
			$this->middleware[] = [
				'middleware' => $m,
				'options'    => $options,
			];
		}

		return $this;
	}

	/**
	 * Get the middleware for the controller.
	 *
	 * @return array<array>
	 */
	public function getMiddleware(): array {
		return $this->middleware;
	}

	/**
	 * Validate the given request data.
	 *
	 * @param array $data
	 * @param array $rules
	 * @param array $messages
	 *
	 * @return array
	 * @throws \WPJarvis\Framework\Exceptions\Validation\ValidationException
	 */
	protected function validate( array $data, array $rules, array $messages = [] ): array {
		return Validator::make( $data, $rules, $messages )->validate();
	}

	/**
	 * Validate REST request data.
	 *
	 * @param \WP_REST_Request $request
	 * @param array $rules
	 * @param array $messages
	 *
	 * @return array|\WP_Error
	 */
	protected function validateRequest( \WP_REST_Request $request, array $rules, array $messages = [] ): array|\WP_Error {
		try {
			return $this->validate( $request->get_params(), $rules, $messages );
		} catch ( \WPJarvis\Framework\Exceptions\Validation\ValidationException $e ) {
			return new \WP_Error(
				'validation_error',
				$e->getMessage(),
				[ 'status' => 422, 'errors' => $e->errors() ]
			);
		}
	}

	/**
	 * Return a success JSON response.
	 *
	 * @param mixed $data
	 * @param string $message
	 * @param int $status
	 *
	 * @return Response
	 */
	protected function success( mixed $data = null, string $message = '', int $status = 200 ): Response {
		return Response::success( $data, $message, $status );
	}

	/**
	 * Return an error JSON response.
	 *
	 * @param string $message
	 * @param int $status
	 * @param mixed $errors
	 *
	 * @return Response
	 */
	protected function error( string $message, int $status = 400, mixed $errors = null ): Response {
		return Response::error( $message, $status, $errors );
	}

	/**
	 * Return a created response.
	 *
	 * @param mixed $data
	 * @param string|null $message Defaults to null so Response class can translate the default.
	 *
	 * @return Response
	 */
	protected function created( mixed $data = null, ?string $message = null ): Response {
		return Response::created( $data, $message );
	}

	/**
	 * Return a no-content response.
	 *
	 * @return Response
	 */
	protected function noContent(): Response {
		return Response::noContent();
	}

	/**
	 * Return a not found response.
	 *
	 * @param string|null $message Defaults to null so Response class can translate the default.
	 *
	 * @return Response
	 */
	protected function notFound( ?string $message = null ): Response {
		return Response::notFound( $message );
	}

	/**
	 * Return an unauthorized response.
	 *
	 * @param string|null $message Defaults to null so Response class can translate the default.
	 *
	 * @return Response
	 */
	protected function unauthorized( ?string $message = null ): Response {
		return Response::unauthorized( $message );
	}

	/**
	 * Return a forbidden response.
	 *
	 * @param string|null $message Defaults to null so Response class can translate the default.
	 *
	 * @return Response
	 */
	protected function forbidden( ?string $message = null ): Response {
		return Response::forbidden( $message );
	}

	/**
	 * Return a validation error response.
	 *
	 * @param array $errors
	 * @param string|null $message Defaults to null so Response class can translate the default.
	 *
	 * @return Response
	 */
	protected function validationError( array $errors, ?string $message = null ): Response {
		return Response::validationError( $errors, $message );
	}

	/**
	 * Return a paginated response.
	 *
	 * @param array $items
	 * @param int $total
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return Response
	 */
	protected function paginated( array $items, int $total, int $page = 1, int $perPage = 10 ): Response {
		return Response::paginated( $items, $total, $page, $perPage );
	}

	/**
	 * Convert response to WP_REST_Response.
	 *
	 * @param Response $response
	 *
	 * @return \WP_REST_Response
	 */
	protected function toRestResponse( Response $response ): \WP_REST_Response {
		return $response->toRestResponse();
	}

	/**
	 * Get the current user.
	 *
	 * @return \WP_User|null
	 */
	protected function user(): ?\WP_User {
		$user = wp_get_current_user();

		if ( ! ( $user instanceof \WP_User ) ) {
			return null;
		}

		return $user->exists() ? $user : null;
	}

	/**
	 * Get the current user ID.
	 *
	 * @return int
	 */
	protected function userId(): int {
		return get_current_user_id();
	}

	/**
	 * Check if a user has capability.
	 *
	 * @param string $capability
	 * @param mixed ...$args
	 *
	 * @return bool
	 */
	protected function can( string $capability, mixed ...$args ): bool {
		return current_user_can( $capability, ...$args );
	}

	/**
	 * Abort if the user doesn't have a capability.
	 *
	 * @param string $capability
	 * @param string $message
	 *
	 * @return \WP_Error|null
	 */
	protected function authorize( string $capability, string $message = 'Unauthorized' ): ?\WP_Error {
		if ( ! $this->can( $capability ) ) {
			return new \WP_Error( 'unauthorized', $message, [ 'status' => 403 ] );
		}

		return null;
	}
}