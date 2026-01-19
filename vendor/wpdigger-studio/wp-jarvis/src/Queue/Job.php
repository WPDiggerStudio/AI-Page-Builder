<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Queue;

use WPJarvis\Framework\Contracts\Queue\Job as JobContract;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\Support\Facades\Log;

/**
 * Job - Base class for queue jobs.
 */
abstract class Job implements JobContract {
	/**
	 * Job unique identifier.
	 */
	protected string $jobId;

	/**
	 * The queue the job should be sent to.
	 */
	protected ?string $queue = null;

	/**
	 * Delay before processing in seconds.
	 */
	protected int $delay = 0;

	/**
	 * Number of attempts.
	 */
	protected int $tries = 3;

	/**
	 * Timeout in seconds.
	 */
	protected int $timeout = 60;

	/**
	 * Create a new job instance.
	 */
	public function __construct() {
		$this->jobId = uniqid( 'job_', true );
	}

	/**
	 * Execute the job.
	 */
	abstract public function handle(): void;

	/**
	 * Get the job identifier.
	 */
	public function getJobId(): string {
		return $this->jobId;
	}

	/**
	 * Get the queue name.
	 */
	public function getQueue(): ?string {
		return $this->queue;
	}

	/**
	 * Set the queue name.
	 */
	public function onQueue( string $queue ): static {
		$this->queue = $queue;

		return $this;
	}

	/**
	 * Get the delay.
	 */
	public function getDelay(): int {
		return $this->delay;
	}

	/**
	 * Set the delay.
	 */
	public function delay( int $seconds ): static {
		$this->delay = $seconds;

		return $this;
	}

	/**
	 * Get the number of tries.
	 */
	public function getTries(): int {
		return $this->tries;
	}

	/**
	 * Get the timeout.
	 */
	public function getTimeout(): int {
		return $this->timeout;
	}

	/**
	 * Determine if the job should be retried.
	 */
	public function shouldRetry( \Throwable $e, int $attempts ): bool {
		return $attempts < $this->tries;
	}

	/**
	 * Handle a job failure.
	 */
	public function failed( \Throwable $e ): void {
		Log::error(
			sprintf( '[Job Failed] %s: %s', static::class, $e->getMessage() ),
			[ 'exception' => $e ]
		);
		Hooks::doAction( 'job_failed', $this, $e );
	}

	/**
	 * Get the tags for the job.
	 */
	public function tags(): array {
		return [];
	}

	/**
	 * Dispatch the job.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function dispatch( ...$args ): string {
		$job = new static( ...$args );

		if ( function_exists( 'wpj_app' ) && wpj_app()->bound( 'queue' ) ) {
			if ( $job->delay > 0 ) {
				return wpj_app( 'queue' )->later( $job->delay, $job );
			}

			return wpj_app( 'queue' )->push( $job );
		}

		// Fallback to sync
		$job->handle();

		return 'sync-' . $job->getJobId();
	}

	/**
	 * Dispatch the job after a delay.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function dispatchAfter( int $delay, ...$args ): string {
		$job = new static( ...$args );
		$job->delay( $delay );

		if ( function_exists( 'wpj_app' ) && wpj_app()->bound( 'queue' ) ) {
			return wpj_app( 'queue' )->later( $delay, $job );
		}

		// Fallback: schedule via WP-Cron for delayed execution
		$hookName = 'delayed_job_' . $job->getJobId();
		Hooks::action( $hookName, static function () use ( $job ) {
			$job->handle();
		} );
		wp_schedule_single_event( time() + $delay, $hookName );

		return 'delayed-' . $job->getJobId();
	}

	/**
	 * Dispatch the job synchronously (immediately, without queuing).
	 * @throws \Throwable
	 */
	public static function dispatchSync( ...$args ): mixed {
		$job = new static( ...$args );

		try {
			return $job->handle();
		} catch ( \Throwable $e ) {
			$job->failed( $e );
			throw $e;
		}
	}

	/**
	 * Dispatch the job immediately (alias for dispatchSync).
	 * @throws \Throwable
	 */
	public static function dispatchNow( ...$args ): mixed {
		return static::dispatchSync( ...$args );
	}
}
