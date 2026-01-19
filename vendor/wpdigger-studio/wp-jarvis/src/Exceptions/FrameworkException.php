<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions;

use Exception;
use Throwable;

/**
 * FrameworkException - Base exception for all framework exceptions.
 */
class FrameworkException extends Exception {
	/**
	 * Additional context data.
	 */
	protected array $context = [];

	/**
	 * Create a new framework exception.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous throwable used for exception chaining.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function __construct(
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
		array $context = []
	) {
		parent::__construct( $message, $code, $previous );
		$this->context = $context;
	}

	/**
	 * Get the exception context.
	 *
	 * @return array<string, mixed> The context data.
	 */
	public function getContext(): array {
		return $this->context;
	}

	/**
	 * Add context data.
	 *
	 * @param array<string, mixed> $context The context data to add.
	 *
	 * @return static The exception instance for method chaining.
	 */
	public function withContext( array $context ): static {
		$this->context = array_merge( $this->context, $context );

		return $this;
	}

	/**
	 * Create an exception with context.
	 *
	 * @param string $message The exception message.
	 * @param array<string, mixed> $context The context data.
	 *
	 * @return static The exception instance.
	 */
	public static function make( string $message, array $context = [] ): static {
		return new static( $message, 0, null, $context );
	}

	/**
	 * Get a detailed error array for logging.
	 *
	 * @return array<string, mixed> The error details.
	 */
	public function toArray(): array {
		return [
			'exception' => static::class,
			'message'   => $this->getMessage(),
			'code'      => $this->getCode(),
			'file'      => $this->getFile(),
			'line'      => $this->getLine(),
			'context'   => $this->context,
			'trace'     => $this->getTraceAsString(),
		];
	}
}
