<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions;

use Psr\Log\LoggerInterface;
use Throwable;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Handler - Main exception handler for the framework.
 */
class Handler {
	/**
	 * Exception types that should not be reported.
	 */
	private array $dontReport = [];

	/**
	 * Logger instance.
	 */
	private ?LoggerInterface $logger;

	/**
	 * Create a new handler instance.
	 *
	 * @param LoggerInterface|null $logger The logger instance.
	 */
	public function __construct( ?LoggerInterface $logger = null ) {
		$this->logger = $logger;
	}

	/**
	 * Report an exception.
	 *
	 * @param Throwable $e The exception to report.
	 *
	 * @return void
	 */
	public function report( Throwable $e ): void {
		if ( $this->shouldntReport( $e ) ) {
			return;
		}

		if ( $this->logger ) {
			$this->logger->error( $e->getMessage(), [
				'exception' => $e,
				'file'      => $e->getFile(),
				'line'      => $e->getLine(),
				'trace'     => $e->getTraceAsString(),
			] );
		}

		Hooks::doAction( 'exception', $e );
	}

	/**
	 * Determine if the exception should not be reported.
	 *
	 * @param Throwable $e The exception to check.
	 *
	 * @return bool True if the exception should not be reported.
	 */
	protected function shouldntReport( Throwable $e ): bool {
		foreach ( $this->dontReport as $type ) {
			if ( $e instanceof $type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render an exception for display.
	 *
	 * @param Throwable $e The exception to render.
	 *
	 * @return string The rendered exception output.
	 */
	public function render( Throwable $e ): string {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return $this->renderDebug( $e );
		}

		return $this->renderProduction( $e );
	}

	/**
	 * Render debug output.
	 *
	 * @param Throwable $e The exception to render.
	 *
	 * @return string The rendered debug output.
	 */
	protected function renderDebug( Throwable $e ): string {
		return sprintf(
			'<div class="WpJarvis-exception" style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px 0;border-radius:4px;">
                <h3 style="margin:0 0 10px;">%s</h3>
                <p style="margin:0 0 10px;"><strong>%s</strong></p>
                <p style="margin:0 0 10px;font-size:12px;color:#666;">%s:%d</p>
                <pre style="background:#fff;padding:10px;overflow:auto;font-size:11px;max-height:200px;">%s</pre>
            </div>',
			esc_html( get_class( $e ) ),
			esc_html( $e->getMessage() ),
			esc_html( $e->getFile() ),
			$e->getLine(),
			esc_html( $e->getTraceAsString() )
		);
	}

	/**
	 * Render production output.
	 *
	 * @param Throwable $e The exception to render.
	 *
	 * @return string The rendered production output.
	 */
	protected function renderProduction( Throwable $e ): string {
		return sprintf(
			'<div class="WpJarvis-error" style="background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;margin:10px 0;border-radius:4px;">
                <p style="margin:0;">%s</p>
            </div>',
			esc_html__( 'An error occurred. Please try again later.', 'wp-jarvis' )
		);
	}

	/**
	 * Display an admin notice for the exception.
	 *
	 * @param Throwable $e The exception to display.
	 *
	 * @return void
	 */
	public function adminNotice( Throwable $e ): void {
		Hooks::action( 'admin_notices', function () use ( $e ) {
			$class   = 'notice notice-error';
			$message = defined( 'WP_DEBUG' ) && WP_DEBUG
				? sprintf( '%s: %s', get_class( $e ), $e->getMessage() )
				: __( 'An error occurred in WP Jarvis. Check the error log for details.', 'wp-jarvis' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		} );
	}

	/**
	 * Set exceptions that should not be reported.
	 *
	 * @param array<class-string> $types The exception types to not report.
	 *
	 * @return static The handler instance for method chaining.
	 */
	public function dontReport( array $types ): static {
		$this->dontReport = array_merge( $this->dontReport, $types );

		return $this;
	}

	/**
	 * Convert exception to WP_Error.
	 *
	 * Useful for WordPress REST API responses and other WordPress functions
	 * that expect WP_Error objects.
	 *
	 * @param Throwable $e The exception to convert.
	 * @param string $code The error code (default: exception class name).
	 *
	 * @return \WP_Error The WP_Error instance.
	 */
	public function toWpError( Throwable $e, string $code = '' ): \WP_Error {
		$errorCode  = $code ?: $this->getErrorCode( $e );
		$statusCode = $this->getStatusCode( $e );

		$data = [
			'status' => $statusCode,
		];

		// Add debug data in WP_DEBUG mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$data['exception'] = get_class( $e );
			$data['file']      = $e->getFile();
			$data['line']      = $e->getLine();
		}

		// Include context from FrameworkException
		if ( $e instanceof FrameworkException ) {
			$data['context'] = $e->getContext();
		}

		return new \WP_Error( $errorCode, $e->getMessage(), $data );
	}

	/**
	 * Render exception as JSON response.
	 *
	 * Useful for REST API error responses.
	 *
	 * @param Throwable $e The exception to render.
	 *
	 * @return array<string, mixed> The JSON-serializable array.
	 */
	public function renderJson( Throwable $e ): array {
		$response = [
			'success' => false,
			'message' => $e->getMessage(),
			'code'    => $this->getErrorCode( $e ),
		];

		// Add debug data in WP_DEBUG mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$response['debug'] = [
				'exception' => get_class( $e ),
				'file'      => $e->getFile(),
				'line'      => $e->getLine(),
				'trace'     => explode( "\n", $e->getTraceAsString() ),
			];
		}

		// Include context and errors for specific exception types
		if ( $e instanceof FrameworkException ) {
			$response['context'] = $e->getContext();
		}

		if ( $e instanceof \WPJarvis\Framework\Exceptions\Validation\ValidationException ) {
			$response['errors'] = $e->errors();
		}

		if ( $e instanceof \WPJarvis\Framework\Exceptions\Http\HttpException ) {
			$response['status_code'] = $e->getStatusCode();
		}

		return $response;
	}

	/**
	 * Render exception for CLI output.
	 *
	 * Formats the exception for console display with colors.
	 *
	 * @param Throwable $e The exception to render.
	 *
	 * @return string The formatted CLI output.
	 */
	public function renderForCli( Throwable $e ): string {
		$output = [];

		$output[] = sprintf( "\n<error> %s </error>\n", get_class( $e ) );
		$output[] = sprintf( "<comment>%s</comment>\n", $e->getMessage() );
		$output[] = sprintf( "<info>at %s:%d</info>\n", $e->getFile(), $e->getLine() );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$output[] = "\n<comment>Stack trace:</comment>";
			$output[] = $e->getTraceAsString();
		}

		return implode( "\n", $output );
	}

	/**
	 * Get error code from exception.
	 *
	 * @param Throwable $e The exception.
	 *
	 * @return string The error code.
	 */
	protected function getErrorCode( Throwable $e ): string {
		// Use exception code if set
		if ( $e->getCode() ) {
			return (string) $e->getCode();
		}

		// Generate code from a class name
		$className = ( new \ReflectionClass( $e ) )->getShortName();

		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $className ) );
	}

	/**
	 * Get HTTP status code from exception.
	 *
	 * @param Throwable $e The exception.
	 *
	 * @return int The status code.
	 */
	protected function getStatusCode( Throwable $e ): int {
		if ( $e instanceof \WPJarvis\Framework\Exceptions\Http\HttpException ) {
			return $e->getStatusCode();
		}

		if ( $e instanceof \WPJarvis\Framework\Exceptions\Validation\ValidationException ) {
			return 422;
		}

		return 500;
	}
}
