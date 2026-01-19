<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Http;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * HttpException - Base exception for HTTP errors.
 */
class HttpException extends FrameworkException {
	/**
	 * HTTP status code.
	 */
	protected int $statusCode;

	/**
	 * HTTP headers.
	 *
	 * @var array<string, string>
	 */
	protected array $headers;

	/**
	 * Create a new HTTP exception.
	 *
	 * @param int $statusCode The HTTP status code.
	 * @param string $message The exception message.
	 * @param \Throwable|null $previous The previous throwable used for exception chaining.
	 * @param array<string, string> $headers The HTTP headers.
	 * @param int $code The exception code.
	 */
	public function __construct(
		int $statusCode,
		string $message = '',
		?\Throwable $previous = null,
		array $headers = [],
		int $code = 0
	) {
		$this->statusCode = $statusCode;
		$this->headers    = $headers;

		parent::__construct( $message ?: $this->getDefaultMessage( $statusCode ), $code, $previous );
	}

	/**
	 * Get the HTTP status code.
	 *
	 * @return int The status code.
	 */
	public function getStatusCode(): int {
		return $this->statusCode;
	}

	/**
	 * Get the HTTP headers.
	 *
	 * @return array<string, string> The headers.
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * Get the default message for a status code.
	 *
	 * @param int $statusCode The status code.
	 *
	 * @return string The default message.
	 */
	protected function getDefaultMessage( int $statusCode ): string {
		$messages = [
			400 => __( 'Bad Request', 'wp-jarvis' ),
			401 => __( 'Unauthorized', 'wp-jarvis' ),
			403 => __( 'Forbidden', 'wp-jarvis' ),
			404 => __( 'Not Found', 'wp-jarvis' ),
			405 => __( 'Method Not Allowed', 'wp-jarvis' ),
			422 => __( 'Unprocessable Entity', 'wp-jarvis' ),
			429 => __( 'Too Many Requests', 'wp-jarvis' ),
			500 => __( 'Internal Server Error', 'wp-jarvis' ),
			503 => __( 'Service Unavailable', 'wp-jarvis' ),
		];

		return $messages[ $statusCode ] ?? __( 'Unknown Error', 'wp-jarvis' );
	}

	/**
	 * Create a 404 Not Found exception.
	 *
	 * @param string $message The exception message.
	 *
	 * @return static The exception instance.
	 */
	public static function notFound( string $message = '' ): static {
		return new static( 404, $message ?: __( 'Not Found', 'wp-jarvis' ) );
	}

	/**
	 * Create a 401 Unauthorized exception.
	 *
	 * @param string $message The exception message.
	 *
	 * @return static The exception instance.
	 */
	public static function unauthorized( string $message = '' ): static {
		return new static( 401, $message ?: __( 'Unauthorized', 'wp-jarvis' ) );
	}

	/**
	 * Create a 403 Forbidden exception.
	 *
	 * @param string $message The exception message.
	 *
	 * @return static The exception instance.
	 */
	public static function forbidden( string $message = '' ): static {
		return new static( 403, $message ?: __( 'Forbidden', 'wp-jarvis' ) );
	}

	/**
	 * Create a 400 Bad Request exception.
	 *
	 * @param string $message The exception message.
	 *
	 * @return static The exception instance.
	 */
	public static function badRequest( string $message = '' ): static {
		return new static( 400, $message ?: __( 'Bad Request', 'wp-jarvis' ) );
	}

	/**
	 * Create a 500 Internal Server Error exception.
	 *
	 * @param string $message The exception message.
	 *
	 * @return static The exception instance.
	 */
	public static function serverError( string $message = '' ): static {
		return new static( 500, $message ?: __( 'Internal Server Error', 'wp-jarvis' ) );
	}
}
