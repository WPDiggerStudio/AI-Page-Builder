<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\REST;

use WP_REST_Controller;
use WPJarvis\Framework\Support\Facades\Config;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * REST Controller - Base class for REST API controllers.
 *
 * Provides a foundation for building REST API endpoints with
 * common response methods and validation helpers.
 */
abstract class Controller extends WP_REST_Controller {
	/**
	 * The REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * The REST base route.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Create a new controller instance.
	 */
	public function __construct() {
		$this->namespace = Config::get( 'app.rest_namespace', 'wpjarvis/v1' );
		Hooks::action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Create a new controller instance.
	 *
	 * @return static The new controller instance.
	 */
	public static function register(): static {
		return new static();
	}

	/**
	 * Return a success response.
	 *
	 * @param mixed $data Optional data to include in the response.
	 * @param int $status HTTP status code. Defaults to 200.
	 *
	 * @return \WP_REST_Response The REST response object.
	 */
	protected function success( mixed $data = null, int $status = 200 ): \WP_REST_Response {
		return new \WP_REST_Response( [ 'success' => true, 'data' => $data ], $status );
	}

	/**
	 * Return an error response.
	 *
	 * @param string $message The error message.
	 * @param int $status HTTP status code. Defaults to 400.
	 * @param string $code Error code. Defaults to 'error'.
	 *
	 * @return \WP_Error The WordPress error object.
	 */
	protected function error( string $message, int $status = 400, string $code = 'error' ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}

	/**
	 * Return a created response.
	 *
	 * @param mixed $data Optional data to include in the response.
	 *
	 * @return \WP_REST_Response The REST response object with status 201.
	 */
	protected function created( mixed $data = null ): \WP_REST_Response {
		return $this->success( $data, 201 );
	}

	/**
	 * Return a no-content response.
	 *
	 * @return \WP_REST_Response The REST response object with status 204.
	 */
	protected function noContent(): \WP_REST_Response {
		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * Return a not found error response.
	 *
	 * @param string $message The error message. Defaults to 'Not found'.
	 *
	 * @return \WP_Error The WordPress error object with 404 status.
	 */
	protected function notFound( string $message = 'Not found' ): \WP_Error {
		return $this->error( $message, 404, 'not_found' );
	}

	/**
	 * Return a forbidden error response.
	 *
	 * @param string $message The error message. Defaults to 'Forbidden'.
	 *
	 * @return \WP_Error The WordPress error object with 403 status.
	 */
	protected function forbidden( string $message = 'Forbidden' ): \WP_Error {
		return $this->error( $message, 403, 'forbidden' );
	}

	/**
	 * Return an unauthorized error response.
	 *
	 * @param string $message The error message. Defaults to 'Unauthorized'.
	 *
	 * @return \WP_Error The WordPress error object with 401 status.
	 */
	protected function unauthorized( string $message = 'Unauthorized' ): \WP_Error {
		return $this->error( $message, 401, 'unauthorized' );
	}

	/**
	 * Validate request parameters.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @param array<string, string> $rules Validation rules for each field.
	 *
	 * @return array<string, mixed>|\WP_Error The validated data or error.
	 */
	protected function validate( \WP_REST_Request $request, array $rules ): array|\WP_Error {
		$data   = [];
		$errors = [];

		foreach ( $rules as $field => $rule ) {
			$value    = $request->get_param( $field );
			$ruleList = is_string( $rule ) ? explode( '|', $rule ) : $rule;

			foreach ( $ruleList as $r ) {
				if ( $r === 'required' && empty( $value ) ) {
					$errors[ $field ] = "$field is required";
					break;
				}
			}

			$data[ $field ] = $value;
		}

		if ( $errors ) {
			return $this->error( 'Validation failed', 422, 'validation_error' );
		}

		return $data;
	}

	/**
	 * Check if the current user can read.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return bool True if the user can read.
	 */
	public function canRead( \WP_REST_Request $request ): bool {
		return true;
	}

	/**
	 * Check if the current user can create.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return bool True if the user can create.
	 */
	public function canCreate( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check if the current user can update.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return bool True if the user can update.
	 */
	public function canUpdate( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check if the current user can delete.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return bool True if the user can delete.
	 */
	public function canDelete( \WP_REST_Request $request ): bool {
		return current_user_can( 'delete_posts' );
	}

	/**
	 * Check if the current user can manage.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return bool True if the user can manage.
	 */
	public function canManage( \WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}
}
