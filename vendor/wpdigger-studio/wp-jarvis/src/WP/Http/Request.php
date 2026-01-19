<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Http;

/**
 * Request - WordPress request wrapper with input sanitization.
 *
 * Provides a Laravel-like interface for accessing request data
 * with built-in WordPress sanitization.
 */
class Request {
	/**
	 * The request method.
	 */
	private string $method;

	/**
	 * The request URI.
	 */
	private string $uri;

	/**
	 * All input data (merged GET, POST, JSON).
	 *
	 * @var array<string, mixed>
	 */
	private array $input = [];

	/**
	 * The request headers.
	 *
	 * @var array<string, string>
	 */
	private array $headers = [];

	/**
	 * The uploaded files.
	 *
	 * @var array<string, array>
	 */
	private array $files;

	/**
	 * Create a new request instance.
	 */
	public function __construct() {
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$this->uri    = $_SERVER['REQUEST_URI'] ?? '/';
		$this->captureInput();
		$this->captureHeaders();
		$this->files = $_FILES;
	}

	/**
	 * Create a request from globals.
	 */
	public static function capture(): static {
		return new static();
	}

	/**
	 * Capture all input data.
	 * @throws \JsonException
	 */
	protected function captureInput(): void {
		// GET parameters
		$this->input = $_GET;

		// POST parameters
		$this->input = array_merge( $this->input, $_POST );

		// JSON body
		$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
		if ( str_contains( $contentType, 'application/json' ) ) {
			$json = file_get_contents( 'php://input' );
			if ( $json ) {
				$decoded = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
				if ( is_array( $decoded ) ) {
					$this->input = array_merge( $this->input, $decoded );
				}
			}
		}
	}

	/**
	 * Capture request headers.
	 */
	protected function captureHeaders(): void {
		foreach ( $_SERVER as $key => $value ) {
			if ( str_starts_with( $key, 'HTTP_' ) ) {
				$header                                 = str_replace( '_', '-', substr( $key, 5 ) );
				$this->headers[ strtolower( $header ) ] = $value;
			}
		}
	}

	/**
	 * Get the request method.
	 */
	public function method(): string {
		return strtoupper( $this->method );
	}

	/**
	 * Check if request method matches.
	 */
	public function isMethod( string $method ): bool {
		return $this->method() === strtoupper( $method );
	}

	/**
	 * Check if this is a GET request.
	 */
	public function isGet(): bool {
		return $this->isMethod( 'GET' );
	}

	/**
	 * Check if this is a POST request.
	 */
	public function isPost(): bool {
		return $this->isMethod( 'POST' );
	}

	/**
	 * Check if this is an AJAX request.
	 */
	public function isAjax(): bool {
		return $this->header( 'x-requested-with' ) === 'XMLHttpRequest'
		       || wp_doing_ajax();
	}

	/**
	 * Check if this is a REST API request.
	 */
	public function isRest(): bool {
		$restPrefix = rest_get_url_prefix();

		return str_contains( $this->uri, "/{$restPrefix}/" );
	}

	/**
	 * Get the request URI.
	 */
	public function uri(): string {
		return $this->uri;
	}

	/**
	 * Get the request path.
	 */
	public function path(): string {
		$path = parse_url( $this->uri, PHP_URL_PATH );

		return $path ?: '/';
	}

	/**
	 * Get all input data.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		return $this->input;
	}

	/**
	 * Get an input value.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function input( string $key, mixed $default = null ): mixed {
		return $this->input[ $key ] ?? $default;
	}

	/**
	 * Get an input value (alias for input).
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( string $key, mixed $default = null ): mixed {
		return $this->input( $key, $default );
	}

	/**
	 * Check if an input key exists.
	 */
	public function has( string $key ): bool {
		return array_key_exists( $key, $this->input );
	}

	/**
	 * Check if an input key exists and is not empty.
	 */
	public function filled( string $key ): bool {
		return $this->has( $key ) && ! empty( $this->input[ $key ] );
	}

	/**
	 * Get only specified keys.
	 *
	 * @param array<string> $keys
	 *
	 * @return array<string, mixed>
	 */
	public function only( array $keys ): array {
		return array_intersect_key( $this->input, array_flip( $keys ) );
	}

	/**
	 * Get all except specified keys.
	 *
	 * @param array<string> $keys
	 *
	 * @return array<string, mixed>
	 */
	public function except( array $keys ): array {
		return array_diff_key( $this->input, array_flip( $keys ) );
	}

	/**
	 * Get a sanitized string input.
	 */
	public function string( string $key, string $default = '' ): string {
		$value = $this->input( $key, $default );

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Get a sanitized integer input.
	 */
	public function integer( string $key, int $default = 0 ): int {
		$value = $this->input( $key, $default );

		return (int) $value;
	}

	/**
	 * Get a sanitized float input.
	 */
	public function float( string $key, float $default = 0.0 ): float {
		$value = $this->input( $key, $default );

		return (float) $value;
	}

	/**
	 * Get a boolean input.
	 */
	public function boolean( string $key, bool $default = false ): bool {
		$value = $this->input( $key, $default );

		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get a sanitized email input.
	 */
	public function email( string $key, string $default = '' ): string {
		$value = $this->input( $key, $default );

		return sanitize_email( (string) $value );
	}

	/**
	 * Get a sanitized URL input.
	 */
	public function url( string $key, string $default = '' ): string {
		$value = $this->input( $key, $default );

		return esc_url_raw( (string) $value );
	}

	/**
	 * Get sanitized textarea content.
	 */
	public function textarea( string $key, string $default = '' ): string {
		$value = $this->input( $key, $default );

		return sanitize_textarea_field( (string) $value );
	}

	/**
	 * Get a header value.
	 */
	public function header( string $key, ?string $default = null ): ?string {
		return $this->headers[ strtolower( $key ) ] ?? $default;
	}

	/**
	 * Get all headers.
	 *
	 * @return array<string, string>
	 */
	public function headers(): array {
		return $this->headers;
	}

	/**
	 * Get the bearer token.
	 */
	public function bearerToken(): ?string {
		$header = $this->header( 'authorization' );
		if ( $header && str_starts_with( $header, 'Bearer ' ) ) {
			return substr( $header, 7 );
		}

		return null;
	}

	/**
	 * Get an uploaded file.
	 *
	 * @param string $key
	 *
	 * @return array|null
	 */
	public function file( string $key ): ?array {
		return $this->files[ $key ] ?? null;
	}

	/**
	 * Check if a file was uploaded.
	 */
	public function hasFile( string $key ): bool {
		$file = $this->file( $key );

		return $file && isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_NO_FILE;
	}

	/**
	 * Get the current user.
	 */
	public function user(): ?\WP_User {
		$user = wp_get_current_user();

		if ( ! ( $user instanceof \WP_User ) ) {
			return null;
		}

		return $user->exists() ? $user : null;
	}

	/**
	 * Get the current user ID.
	 */
	public function userId(): int {
		return get_current_user_id();
	}

	/**
	 * Check if the user is authenticated.
	 */
	public function isAuthenticated(): bool {
		return is_user_logged_in();
	}

	/**
	 * Validate the request.
	 *
	 * @param array<string, string|array> $rules
	 * @param array<string, string> $messages
	 *
	 * @return array<string, mixed>
	 * @throws \WPJarvis\Framework\Exceptions\Validation\ValidationException
	 */
	public function validate( array $rules, array $messages = [] ): array {
		$validator = new \WPJarvis\Framework\Validation\Validator(
			$this->all(),
			$rules,
			$messages
		);

		return $validator->validate();
	}

	/**
	 * Verify a nonce.
	 */
	public function verifyNonce( string $action, string $key = '_wpnonce' ): bool {
		$nonce = $this->input( $key );

		return $nonce && wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Check user capability.
	 */
	public function can( string $capability, mixed ...$args ): bool {
		return current_user_can( $capability, ...$args );
	}

	/**
	 * Get the IP address.
	 */
	public function ip(): string {
		$keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				if ( str_contains( $ip, ',' ) ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get the user agent.
	 */
	public function userAgent(): string {
		return $_SERVER['HTTP_USER_AGENT'] ?? '';
	}
}
