<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Contracts\Queue\Queue;
use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Queue\Drivers\CronDriver;
use WPJarvis\Framework\Queue\Drivers\SyncDriver;
use WPJarvis\Framework\Queue\Job;
use WPJarvis\Framework\WP\Hooks\Hooks;

/**
 * QueueServiceProvider
 *
 * Registers queue services for background job processing.
 */
class QueueServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 */
	protected bool $defer = true;

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->app->singleton( 'queue', function ( $app ) {
			$driver = $app->make( 'config' )->get( 'queue.driver', 'sync' );

			return match ( $driver ) {
				'cron' => new CronDriver(),
				default => new SyncDriver(),
			};
		} );

		$this->app->alias( 'queue', Queue::class );
	}

	/**
	 * Bootstrap the service provider.
	 */
	public function boot(): void {
		// Register cron hook for processing queue
		Hooks::action( 'process_queue', [ $this, 'processQueue' ] );

		// Register hook for delayed jobs
		Hooks::action( 'run_delayed_job', [ $this, 'runDelayedJob' ] );
	}

	/**
	 * Process the queue.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function processQueue(): void {
		$queue = $this->app->make( 'queue' );

		if ( $queue instanceof CronDriver ) {
			$processed = $queue->processQueue();

			if ( $processed > 0 ) {
				Hooks::doAction( 'queue_processed', $processed );
			}
		}
	}

	/**
	 * Run a delayed job.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function runDelayedJob( string $serializedJob ): void {
		try {
			$job = unserialize( $serializedJob, [ 'allowed_classes' => [ Job::class ] ] );
			if ( $job ) {
				$job->handle();
				Hooks::doAction( 'job_processed', $job );
			}
		} catch ( \Throwable $e ) {
			wpj_logger()?->error(
				'[WP Jarvis Queue] Failed to run delayed job: ' . $e->getMessage(),
				[ 'exception' => $e ]
			);
		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array<string>
	 */
	public function provides(): array {
		return [
			'queue',
			Queue::class,
		];
	}
}
