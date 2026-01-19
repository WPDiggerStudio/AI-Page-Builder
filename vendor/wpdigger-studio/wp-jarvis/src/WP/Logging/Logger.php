<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * WordPress Logger - PSR-3 compatible.
 *
 * Provides logging functionality for WordPress applications with support for
 * different log levels, file logging, and context interpolation.
 */
class Logger extends AbstractLogger {
	/**
	 * Log level priorities.
	 *
	 * @var array<string, int>
	 */
	protected const LEVELS = [
		LogLevel::EMERGENCY => 800,
		LogLevel::ALERT     => 700,
		LogLevel::CRITICAL  => 600,
		LogLevel::ERROR     => 500,
		LogLevel::WARNING   => 400,
		LogLevel::NOTICE    => 300,
		LogLevel::INFO      => 200,
		LogLevel::DEBUG     => 100,
	];

	/**
	 * The minimum log level to record.
	 *
	 * @var string
	 */
	protected string $minLevel;

	/**
	 * The log file path, or null if not logging to file.
	 *
	 * @var string|null
	 */
	protected ?string $logFile;

	/**
	 * Create a new Logger instance.
	 *
	 * @param string $minLevel The minimum log level to record. Defaults to LogLevel::DEBUG.
	 * @param string|null $logFile Optional path to a log file. If null, logs are only sent to error_log().
	 */
	public function __construct( string $minLevel = LogLevel::DEBUG, ?string $logFile = null ) {
		$this->minLevel = $minLevel;
		$this->logFile  = $logFile;
	}

	/**
	 * Log a message.
	 *
	 * @param string $level The log level.
	 * @param string|\Stringable $message The message to log.
	 * @param array<string, mixed> $context Additional context data.
	 *
	 * @return void
	 */
	public function log( $level, string|\Stringable $message, array $context = [] ): void {
		if ( ! $this->shouldLog( $level ) ) {
			return;
		}

		$formatted = $this->interpolate( (string) $message, $context );
		$entry     = sprintf( '[%s] %s: %s', date( 'Y-m-d H:i:s' ), strtoupper( $level ), $formatted );

		if ( isset( $context['exception'] ) && $context['exception'] instanceof \Throwable ) {
			$e     = $context['exception'];
			$entry .= PHP_EOL . get_class( $e ) . ': ' . $e->getMessage();
			$entry .= PHP_EOL . $e->getTraceAsString();
		}

		if ( $this->logFile ) {
			$this->writeToFile( $entry );
		}

		Hooks::doAction( 'log', $level, $formatted, $context );
	}

	/**
	 * Check if a message should be logged based on the minimum level.
	 *
	 * @param string $level The log level to check.
	 *
	 * @return bool True if the message should be logged, false otherwise.
	 */
	private function shouldLog( string $level ): bool {
		return ( self::LEVELS[ $level ] ?? 0 ) >= ( self::LEVELS[ $this->minLevel ] ?? 0 );
	}

	/**
	 * Interpolate context values into the message.
	 *
	 * Replaces placeholders like {key} with corresponding values from context.
	 *
	 * @param string $message The message with placeholders.
	 * @param array<string, mixed> $context The context data.
	 *
	 * @return string The interpolated message.
	 */
	private function interpolate( string $message, array $context ): string {
		$replace = [];
		foreach ( $context as $key => $value ) {
			if ( ! is_array( $value ) && ( ! is_object( $value ) || method_exists( $value, '__toString' ) ) ) {
				$replace[ '{' . $key . '}' ] = (string) $value;
			}
		}

		return strtr( $message, $replace );
	}

	/**
	 * Write a log entry to the log file.
	 *
	 * Creates the log directory if it doesn't exist.
	 *
	 * @param string $entry The log entry to write.
	 *
	 * @return void
	 */
	private function writeToFile( string $entry ): void {
		$dir = dirname( $this->logFile );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		file_put_contents( $this->logFile, $entry . PHP_EOL, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Set the log file path.
	 *
	 * @param string $path The path to the log file.
	 *
	 * @return static The current instance for chaining.
	 */
	public function setLogFile( string $path ): static {
		$this->logFile = $path;

		return $this;
	}

	/**
	 * Set the minimum log level.
	 *
	 * @param string $level The minimum log level to record.
	 *
	 * @return static The current instance for chaining.
	 */
	public function setMinLevel( string $level ): static {
		$this->minLevel = $level;

		return $this;
	}
}
