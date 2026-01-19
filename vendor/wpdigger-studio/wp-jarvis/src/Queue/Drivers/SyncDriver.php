<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Queue\Drivers;

use WPJarvis\Framework\Contracts\Queue\Job;
use WPJarvis\Framework\Contracts\Queue\Queue;
use WPJarvis\Framework\Support\Facades\Config;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * SyncDriver - Synchronous queue driver for immediate execution.
 */
class SyncDriver implements Queue {
	/**
	 * Connection name.
	 */
	private string $connection = 'sync';

	/**
	 * Hook name for delayed jobs.
	 */
	private string $delayedJobHookName;

	/**
	 * Create a new SyncDriver instance.
	 */
	public function __construct() {
		$slug                     = Config::get( 'app.slug', 'wp-jarvis' );
		$this->delayedJobHookName = $slug . '_run_delayed_job';
	}

	/**
	 * Push a job onto the queue.
	 */
	public function push( Job $job ): string {
		try {
			$job->handle();
			Hooks::doAction( 'job_processed', $job );
		} catch ( \Throwable $e ) {
			$job->failed( $e );
		}

		return $job->getJobId();
	}

	/**
	 * Push a job after delay.
	 */
	public function later( int $delay, Job $job ): string {
		// For a sync driver, use WP-Cron for delay
		if ( $delay > 0 ) {
			wp_schedule_single_event(
				time() + $delay,
				$this->delayedJobHookName,
				[ serialize( $job ) ]
			);

			return 'scheduled-' . $job->getJobId();
		}

		return $this->push( $job );
	}

	/**
	 * Pop the next job.
	 */
	public function pop( ?string $queue = null ): ?Job {
		// Sync driver doesn't queue
		return null;
	}

	/**
	 * Delete a job.
	 */
	public function delete( string $jobId ): bool {
		return true;
	}

	/**
	 * Release a job back.
	 */
	public function release( Job $job, int $delay = 0 ): void {
		if ( $delay > 0 ) {
			$this->later( $delay, $job );
		} else {
			$this->push( $job );
		}
	}

	/**
	 * Get queue size.
	 */
	public function size( ?string $queue = null ): int {
		return 0;
	}

	/**
	 * Clear the queue.
	 */
	public function clear( ?string $queue = null ): int {
		return 0;
	}

	/**
	 * Get a connection name.
	 */
	public function getConnectionName(): string {
		return $this->connection;
	}
}
