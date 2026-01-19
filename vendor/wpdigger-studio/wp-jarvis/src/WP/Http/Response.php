<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Http;

/**
 * Response - HTTP response helper for REST and AJAX.
 *
 * Provides standardized response formatting for WordPress REST API
 * and AJAX requests.
 */
class Response {
	/**
	 * The response data.
	 *
	 * @var mixed
	 */
	private mixed $data;

	/**
	 * The HTTP status code.
	 */
	private int $status;

	/**
	 * The response headers.
	 *
	 * @var array<string, string>
	 */
	private array $headers;

	/**
	 * Create a new response instance.
	 *
	 * @param mixed $data
	 * @param int $status
	 * @param array<string, string> $headers
	 */
	public function __construct( mixed $data = null, int $status = 200, array $headers = [] ) {
		$this->data    = $data;
		$this->status  = $status;
		$this->headers = $headers;
	}

	/**
	 * Create a success response.
	 *
	 * @param mixed $data
	 * @param string $message
	 * @param int $status
	 *
	 * @return static
	 */
	public static function success( mixed $data = null, string $message = '', int $status = 200 ): static {
		$payload = [
			'success' => true,
			'data'    => $data,
		];

		if ( $message ) {
			$payload['message'] = $message;
		}

		return new static( $payload, $status );
	}

	/**
	 * Create an error response.
	 *
	 * @param string $message
	 * @param int $status
	 * @param mixed $errors
	 *
	 * @return static
	 */
	public static function error( string $message, int $status = 400, mixed $errors = null ): static {
		$payload = [
			'success' => false,
			'message' => $message,
		];

		if ( $errors !== null ) {
			$payload['errors'] = $errors;
		}

		return new static( $payload, $status );
	}

	/**
	 * Create a created response (201).
	 *
	 * @param mixed $data
	 * @param string|null $message
	 *
	 * @return static
	 */
	public static function created( mixed $data = null, ?string $message = null ): static {
		// defaults to null, then we translate it here
		$message = $message ?? __( 'Created successfully', 'wp-jarvis' );

		return static::success( $data, $message, 201 );
	}

	/**
	 * Create a no-content response (204).
	 *
	 * @return static
	 */
	public static function noContent(): static {
		return new static( null, 204 );
	}

	/**
	 * Create a not found response (404).
	 *
	 * @param string|null $message
	 *
	 * @return static
	 */
	public static function notFound( ?string $message = null ): static {
		$message = $message ?? __( 'Not found', 'wp-jarvis' );

		return static::error( $message, 404 );
	}

	/**
	 * Create an unauthorized response (401).
	 *
	 * @param string|null $message
	 *
	 * @return static
	 */
	public static function unauthorized( ?string $message = null ): static {
		$message = $message ?? __( 'Unauthorized', 'wp-jarvis' );

		return static::error( $message, 401 );
	}

	/**
	 * Create a forbidden response (403).
	 *
	 * @param string|null $message
	 *
	 * @return static
	 */
	public static function forbidden( ?string $message = null ): static {
		$message = $message ?? __( 'Forbidden', 'wp-jarvis' );

		return static::error( $message, 403 );
	}

	/**
	 * Create a validation error response (422).
	 *
	 * @param array<string, array<string>> $errors
	 * @param string|null $message
	 *
	 * @return static
	 */
	public static function validationError( array $errors, ?string $message = null ): static {
		$message = $message ?? __( 'Validation failed', 'wp-jarvis' );

		return static::error( $message, 422, $errors );
	}

	/**
	 * Create a server error response (500).
	 *
	 * @param string|null $message
	 *
	 * @return static
	 */
	public static function serverError( ?string $message = null ): static {
		$message = $message ?? __( 'Internal server error', 'wp-jarvis' );

		return static::error( $message, 500 );
	}

	/**
	 * Create a paginated response.
	 *
	 * @param array $items
	 * @param int $total
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return static
	 */
	public static function paginated( array $items, int $total, int $page = 1, int $perPage = 10 ): static {
		$totalPages = (int) ceil( $total / $perPage );

		return new static( [
			'success' => true,
			'data'    => $items,
			'meta'    => [
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $perPage,
				'total_pages' => $totalPages,
				'has_more'    => $page < $totalPages,
			],
		], 200, [
			'X-WP-Total'      => (string) $total,
			'X-WP-TotalPages' => (string) $totalPages,
		] );
	}

	/**
	 * Set a header.
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return static
	 */
	public function header( string $key, string $value ): static {
		$this->headers[ $key ] = $value;

		return $this;
	}

	/**
	 * Set the status code.
	 *
	 * @param int $status
	 *
	 * @return static
	 */
	public function status( int $status ): static {
		$this->status = $status;

		return $this;
	}

	/**
	 * Get the status code.
	 */
	public function getStatus(): int {
		return $this->status;
	}

	/**
	 * Get the data.
	 */
	public function getData(): mixed {
		return $this->data;
	}

	/**
	 * Get the headers.
	 *
	 * @return array<string, string>
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * Convert to WP_REST_Response.
	 *
	 * @return \WP_REST_Response
	 */
	public function toRestResponse(): \WP_REST_Response {
		$response = new \WP_REST_Response( $this->data, $this->status );

		foreach ( $this->headers as $key => $value ) {
			$response->header( $key, $value );
		}

		return $response;
	}

	/**
	 * Convert to WP_Error for REST API errors.
	 *
	 * @param string $code
	 *
	 * @return \WP_Error
	 */
	public function toWpError( string $code = 'error' ): \WP_Error {
		$message = is_array( $this->data ) && isset( $this->data['message'] )
			? $this->data['message']
			: __( 'An error occurred', 'wp-jarvis' ); // Wrapped here

		return new \WP_Error( $code, $message, [ 'status' => $this->status ] );
	}

	/**
	 * Send it as JSON (for AJAX).
	 */
	public function send(): void {
		if ( ! headers_sent() ) {
			http_response_code( $this->status );

			foreach ( $this->headers as $key => $value ) {
				header( "{$key}: {$value}" );
			}

			header( 'Content-Type: application/json; charset=utf-8' );
		}

		echo wp_json_encode( $this->data );

		if ( wp_doing_ajax() ) {
			exit();
		}
	}

	/**
	 * Send success and die (for AJAX).
	 */
	public function sendSuccess(): void {
		wp_send_json_success( $this->data, $this->status );
	}

	/**
	 * Send error and die (for AJAX).
	 */
	public function sendError(): void {
		wp_send_json_error( $this->data, $this->status );
	}

	/**
	 * Convert to array.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [
			'data'    => $this->data,
			'status'  => $this->status,
			'headers' => $this->headers,
		];
	}

	/**
	 * Convert to JSON string.
	 */
	public function toJson(): string {
		return wp_json_encode( $this->data ) ?: '{}';
	}
}
