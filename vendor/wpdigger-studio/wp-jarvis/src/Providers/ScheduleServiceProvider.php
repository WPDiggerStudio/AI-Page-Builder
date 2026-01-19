<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Contracts\Scheduling\Scheduler as SchedulerContract;
use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Scheduling\Scheduler;

/**
 * ScheduleServiceProvider
 *
 * Registers scheduling services for the framework.
 */
class ScheduleServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * Note: Scheduling must NOT be deferred because we need to register
	 * WP-Cron hooks on every request, not just when the scheduler is requested.
	 */
	protected bool $defer = false;

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->app->singleton( 'schedule', function ( $app ) {
			return new Scheduler();
		} );

		$this->app->alias( 'schedule', Scheduler::class );
		$this->app->alias( 'schedule', SchedulerContract::class );
	}

	/**
	 * Bootstrap the service provider.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void {
		// Load schedule definitions
		$this->loadSchedule();

		// Register with WordPress cron
		Hooks::action( 'init', [ $this, 'registerWithCron' ] );

		// Register custom cron schedules
		Hooks::filter( 'cron_schedules', [ $this, 'addCronSchedules' ] );

		// Register the schedule runner callback
		// Use add_action directly because the hook name is already prefixed
		$prefix          = wpj_config( 'app.slug', 'WpJarvis' );
		$scheduleRunHook = $prefix . '_schedule_run';
		add_action( $scheduleRunHook, [ $this, 'runSchedule' ] );

		// Ensure schedule runner is scheduled with WP-Cron
		if ( ! wp_next_scheduled( $scheduleRunHook ) ) {
			wp_schedule_event( time(), 'every_minute', $scheduleRunHook );
		}
	}

	/**
	 * Load the schedule from the application.
	 */
	private function loadSchedule(): void {
		error_log( '[WP Jarvis] ScheduleServiceProvider::loadSchedule() called' );

		$schedulePath = $this->app->basePath( 'app/Console/Kernel.php' );

		if ( file_exists( $schedulePath ) ) {
			error_log( '[WP Jarvis] Kernel.php file exists at: ' . $schedulePath );
		}

		// Defer schedule definition to 'init' hook to ensure all service providers
		// have had a chance to register their schedule listeners
		$app = $this->app;
		add_action( 'init', function () use ( $app ) {
			error_log( '[WP Jarvis] init hook fired - now firing define_schedule action' );
			// Allow schedule definition through action
			Hooks::doAction( 'define_schedule', $app->make( 'schedule' ) );
			error_log( '[WP Jarvis] define_schedule action completed' );
		}, 5 ); // Priority 5 to run early but after service providers have booted

		error_log( '[WP Jarvis] init hook registered at priority 5' );
	}

	/**
	 * Register schedules with WordPress cron.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerWithCron(): void {
		$scheduler = $this->app->make( 'schedule' );
		$scheduler->registerWithCron();
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array<string, array<string, mixed>> $schedules
	 *
	 * @return array<string, array<string, mixed>>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function addCronSchedules( array $schedules ): array {
		$scheduler = $this->app->make( 'schedule' );

		return array_merge( $schedules, $scheduler->getSchedules() );
	}

	/**
	 * Run the schedule.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function runSchedule(): void {
		$scheduler = $this->app->make( 'schedule' );
		$results   = $scheduler->runDueTasks();

		// Log results
		foreach ( $results as $name => $result ) {
			if ( $result['status'] === 'failed' ) {
				wpj_logger()?->error(
					sprintf( '[WP Jarvis Schedule] Task "%s" failed: %s', $name, $result['error'] ),
					[ 'task' => $name, 'error' => $result['error'] ]
				);
			}
		}
	}

	/**
	 * Ensure the schedule runner is scheduled.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function ensureScheduleRunner(): void {
		$prefix = wpj_config( 'app.slug', 'WpJarvis' );
		if ( ! wp_next_scheduled( $prefix . '_schedule_run' ) ) {
			wp_schedule_event( time(), 'every_minute', $prefix . '_schedule_run' );
		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array<string>
	 */
	public function provides(): array {
		return [
			'schedule',
			Scheduler::class,
			SchedulerContract::class,
		];
	}
}
