<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Queue\Drivers;

use WPJarvis\Framework\Contracts\Queue\Job;
use WPJarvis\Framework\Contracts\Queue\Queue;
use WPJarvis\Framework\Support\Facades\Config;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * CronDriver - WordPress Cron-based queue driver.
 */
class CronDriver implements Queue {
	/**
	 * Connection name.
	 */
	private string $connection = 'cron';

	/**
	 * Option name for storing jobs.
	 */
	private string $optionName;

	/**
	 * Hook name for cron schedule.
	 */
	private string $cronHookName;

	/**
	 * Create a new CronDriver instance.
	 */
	public function __construct() {
		$slug               = Config::get( 'app.slug', 'wp-jarvis' );
		$this->optionName   = $slug . '_queued_jobs';
		$this->cronHookName = $slug . '_process_queue';
	}

	/**
	 * Push a job onto the queue.
	 */
	public function push( Job $job ): string {
		$jobs                     = $this->getJobs( $job->getQueue() );
		$jobs[ $job->getJobId() ] = [
			'job'          => serialize( $job ),
			'queue'        => $job->getQueue(),
			'available_at' => time(),
			'created_at'   => time(),
		];

		$this->saveJobs( $jobs, $job->getQueue() );
		$this->ensureCronScheduled();

		return $job->getJobId();
	}

	/**
	 * Push a job after delay.
	 */
	public function later( int $delay, Job $job ): string {
		$jobs                     = $this->getJobs( $job->getQueue() );
		$jobs[ $job->getJobId() ] = [
			'job'          => serialize( $job ),
			'queue'        => $job->getQueue(),
			'available_at' => time() + $delay,
			'created_at'   => time(),
		];

		$this->saveJobs( $jobs, $job->getQueue() );
		$this->ensureCronScheduled();

		return $job->getJobId();
	}

	/**
	 * Pop the next available job.
	 */
	public function pop( ?string $queue = null ): ?Job {
		$jobs = $this->getJobs( $queue );
		$now  = time();

		foreach ( $jobs as $jobId => $data ) {
			if ( $data['available_at'] <= $now ) {
				unset( $jobs[ $jobId ] );
				$this->saveJobs( $jobs, $queue );

				return unserialize( $data['job'], [ 'allowed_classes' => [ Job::class ] ] );
			}
		}

		return null;
	}

	/**
	 * Delete a job.
	 */
	public function delete( string $jobId ): bool {
		$jobs = $this->getJobs();
		if ( isset( $jobs[ $jobId ] ) ) {
			unset( $jobs[ $jobId ] );
			$this->saveJobs( $jobs );

			return true;
		}

		return false;
	}

	/**
	 * Release a job back.
	 */
	public function release( Job $job, int $delay = 0 ): void {
		$this->later( $delay, $job );
	}

	/**
	 * Get queue size.
	 */
	public function size( ?string $queue = null ): int {
		return count( $this->getJobs( $queue ) );
	}

	/**
	 * Clear the queue.
	 */
	public function clear( ?string $queue = null ): int {
		$count = $this->size( $queue );
		delete_option( $this->getOptionName( $queue ) );

		return $count;
	}

	/**
	 * Get a connection name.
	 */
	public function getConnectionName(): string {
		return $this->connection;
	}

	/**
	 * Get jobs from storage.
	 */
	protected function getJobs( ?string $queue = null ): array {
		return get_option( $this->getOptionName( $queue ), [] );
	}

	/**
	 * Save jobs to storage.
	 */
	protected function saveJobs( array $jobs, ?string $queue = null ): void {
		update_option( $this->getOptionName( $queue ), $jobs );
	}

	/**
	 * Get an option name for queue.
	 */
	protected function getOptionName( ?string $queue ): string {
		if ( $queue ) {
			return $this->optionName . '_' . $queue;
		}

		return $this->optionName;
	}

	/**
	 * Ensure cron is scheduled.
	 */
	protected function ensureCronScheduled(): void {
		if ( ! wp_next_scheduled( $this->cronHookName ) ) {
			wp_schedule_event( time(), 'every_minute', $this->cronHookName );
		}
	}

	/**
	 * Process the queue (called by cron).
	 */
	public function processQueue( int $maxJobs = 10 ): int {
		$processed = 0;

		while ( $processed < $maxJobs && ( $job = $this->pop() ) ) {
			try {
				$job->handle();
				Hooks::doAction( 'job_processed', $job );
				$processed ++;
			} catch ( \Throwable $e ) {
				$job->failed( $e );
			}
		}

		return $processed;
	}
}
