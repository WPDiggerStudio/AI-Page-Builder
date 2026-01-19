<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Queue;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * QueueException - Exception for queue operations.
 */
class QueueException extends FrameworkException {
	/**
	 * The job class name.
	 */
	protected ?string $jobClass = null;

	/**
	 * Create exception for job failure.
	 *
	 * @param string $job The job class name.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function jobFailed( string $job, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: job class name, 2: error message */
				__( 'Job [%1$s] failed: %2$s', 'wp-jarvis' ),
				$job,
				$error
			)
		) )
			->withContext( [ 'job' => $job, 'error' => $error ] )
			->setJobClass( $job );
	}

	/**
	 * Create exception for a job not found.
	 *
	 * @param string $job The job class name.
	 *
	 * @return static The exception instance.
	 */
	public static function jobNotFound( string $job ): static {
		return ( new static(
			sprintf(
			/* translators: %s: job class name */
				__( 'Job class [%s] not found.', 'wp-jarvis' ),
				$job
			)
		) )
			->withContext( [ 'job' => $job ] );
	}

	/**
	 * Create an exception for max attempts exceeded.
	 *
	 * @param string $job The job class name.
	 * @param int $attempts The number of attempts.
	 *
	 * @return static The exception instance.
	 */
	public static function maxAttemptsExceeded( string $job, int $attempts ): static {
		return ( new static(
			sprintf(
			/* translators: 1: job class name, 2: number of attempts */
				__( 'Job [%1$s] exceeded max attempts (%2$d).', 'wp-jarvis' ),
				$job,
				$attempts
			)
		) )
			->withContext( [ 'job' => $job, 'attempts' => $attempts ] )
			->setJobClass( $job );
	}

	/**
	 * Create an exception for queue connection failure.
	 *
	 * @param string $connection The connection name.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function connectionFailed( string $connection, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: connection name, 2: error message */
				__( 'Queue connection [%1$s] failed: %2$s', 'wp-jarvis' ),
				$connection,
				$error
			)
		) )
			->withContext( [ 'connection' => $connection, 'error' => $error ] );
	}

	/**
	 * Create an exception for invalid payload.
	 *
	 * @param string $reason The reason.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidPayload( string $reason = '' ): static {
		$message = __( 'Invalid job payload.', 'wp-jarvis' );
		if ( $reason ) {
			$message .= ' ' . sprintf(
				/* translators: %s: failure reason */
					__( 'Reason: %s', 'wp-jarvis' ),
					$reason
				);
		}

		return new static( $message );
	}

	/**
	 * Create an exception for timeout.
	 *
	 * @param string $job The job class name.
	 * @param int $timeout The timeout in seconds.
	 *
	 * @return static The exception instance.
	 */
	public static function timeout( string $job, int $timeout ): static {
		return ( new static(
			sprintf(
			/* translators: 1: job class name, 2: timeout in seconds */
				__( 'Job [%1$s] timed out after %2$d seconds.', 'wp-jarvis' ),
				$job,
				$timeout
			)
		) )
			->withContext( [ 'job' => $job, 'timeout' => $timeout ] )
			->setJobClass( $job );
	}

	/**
	 * Set the job class.
	 *
	 * @param string $jobClass The job class name.
	 *
	 * @return static The exception instance for method chaining.
	 */
	public function setJobClass( string $jobClass ): static {
		$this->jobClass = $jobClass;

		return $this;
	}

	/**
	 * Get the job class.
	 *
	 * @return string|null The job class name.
	 */
	public function getJobClass(): ?string {
		return $this->jobClass;
	}
}
