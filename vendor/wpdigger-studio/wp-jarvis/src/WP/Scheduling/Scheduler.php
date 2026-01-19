<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Scheduling;

use Illuminate\Support\Collection;
use WPJarvis\Framework\Contracts\Scheduling\ScheduledTask;
use WPJarvis\Framework\Contracts\Scheduling\Scheduler as SchedulerContract;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Scheduler - Manages scheduled tasks with WordPress cron.
 *
 * This class provides a fluent interface for scheduling and managing
 * background tasks using WordPress's built-in cron system. It supports
 * custom schedules, task overlapping prevention, and callback-based
 * task execution.
 *
 * @package WPJarvis\Framework\WP\Scheduling
 */
class Scheduler implements SchedulerContract {
	/**
	 * Registered tasks.
	 *
	 * @var Collection<ScheduledTask>
	 */
	private Collection $tasks;

	/**
	 * Custom cron schedules.
	 *
	 * @var array<string, array{interval: int, display: string}>
	 */
	private array $schedules = [];

	/**
	 * Hook prefix for WordPress actions.
	 *
	 * @var string
	 */
	private string $hookPrefix;

	/**
	 * Create a new scheduler instance.
	 *
	 * Initializes the scheduler with a hook prefix, empty task collection,
	 * and default cron schedules.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct() {
		$this->hookPrefix = wpj_config( 'app.slug', 'wp-jarvis' ) . '_task_';
		$this->tasks      = new Collection();
		$this->registerDefaultSchedules();
	}

	/**
	 * Register default WP-Cron schedules.
	 *
	 * Adds predefined schedules for common intervals like every minute,
	 * every five minutes, etc. These schedules are registered with
	 * WordPress cron and can be used when defining task frequencies.
	 *
	 * @return void
	 */
	private function registerDefaultSchedules(): void {
		$this->schedules = [
			'every_minute'          => [
				'interval' => 60,
				'display'  => __( 'Every Minute', 'wp-jarvis' ),
			],
			'every_five_minutes'    => [
				'interval' => 300,
				'display'  => __( 'Every 5 Minutes', 'wp-jarvis' ),
			],
			'every_ten_minutes'     => [
				'interval' => 600,
				'display'  => __( 'Every 10 Minutes', 'wp-jarvis' ),
			],
			'every_fifteen_minutes' => [
				'interval' => 900,
				'display'  => __( 'Every 15 Minutes', 'wp-jarvis' ),
			],
			'every_thirty_minutes'  => [
				'interval' => 1800,
				'display'  => __( 'Every 30 Minutes', 'wp-jarvis' ),
			],
		];
	}

	/**
	 * Add a task to the schedule.
	 *
	 * Registers a task with the scheduler. The task will be included
	 * when the scheduler registers with WordPress cron.
	 *
	 * @param ScheduledTask $task The task to add
	 *
	 * @return static
	 */
	public function add( ScheduledTask $task ): static {
		$this->tasks->put( $task->getName(), $task );

		return $this;
	}

	/**
	 * Schedule a callback.
	 *
	 * Creates a new TaskBuilder for scheduling a callable function.
	 * The builder provides a fluent interface for configuring the task.
	 *
	 * @param callable $callback The callback to execute
	 * @param string $name Optional task name
	 *
	 * @return TaskBuilder
	 */
	public function call( callable $callback, string $name = '' ): TaskBuilder {
		return new TaskBuilder( $this, $callback, $name );
	}

	/**
	 * Schedule a command.
	 *
	 * Creates a new TaskBuilder for scheduling an artisan command.
	 * The command will be executed via the artisan facade.
	 *
	 * @param string $command The command name
	 * @param array $parameters Optional command parameters
	 *
	 * @return TaskBuilder
	 */
	public function command( string $command, array $parameters = [] ): TaskBuilder {
		return new TaskBuilder( $this, function () use ( $command, $parameters ) {
			return wpj_app( 'artisan' )->call( $command, $parameters );
		}, $command );
	}

	/**
	 * Schedule a job.
	 *
	 * Creates a new TaskBuilder for scheduling a queued job class.
	 * Like Laravel, the job will be dispatched when the schedule is due.
	 *
	 * @param object|string $job The job instance or class name
	 * @param string|null $queue The queue to dispatch to (optional)
	 * @param string|null $connection The queue connection (optional)
	 *
	 * @return TaskBuilder
	 */
	public function job( object|string $job, ?string $queue = null, ?string $connection = null ): TaskBuilder {
		$jobName = is_string( $job ) ? $job : get_class( $job );

		return new TaskBuilder( $this, function () use ( $job, $queue, $connection ) {
			// Resolve job if class name was passed
			$jobInstance = is_string( $job ) ? wpj_app( $job ) : $job;

			// Dispatch the job
			if ( method_exists( $jobInstance, 'dispatch' ) ) {
				$dispatch = $jobInstance::dispatch();

				if ( $queue ) {
					$dispatch = $dispatch->onQueue( $queue );
				}

				if ( $connection ) {
					$dispatch = $dispatch->onConnection( $connection );
				}

				return $dispatch;
			}

			// Fallback: call handle() directly if no dispatch method
			return $jobInstance->handle();
		}, 'job:' . class_basename( $jobName ) );
	}

	/**
	 * Get all scheduled tasks.
	 *
	 * Returns a collection of all registered tasks.
	 *
	 * @return Collection<ScheduledTask>
	 */
	public function all(): Collection {
		return $this->tasks;
	}

	/**
	 * Get tasks that are due to run.
	 *
	 * Filters the registered tasks and returns only those that
	 * should run based on their schedule, environment, and overlap settings.
	 *
	 * @return Collection<ScheduledTask>
	 */
	public function dueToRun(): Collection {
		return $this->tasks->filter( fn( ScheduledTask $task ) => $task->shouldRun() );
	}

	/**
	 * Run all due tasks.
	 *
	 * Executes all tasks that are due to run and returns an array
	 * of results containing execution status, output, and errors.
	 *
	 * @return array<string, array{name: string, started_at: mixed, status: string, output: mixed, error: string|null, finished_at: mixed}>
	 */
	public function runDueTasks(): array {
		$results = [];

		foreach ( $this->dueToRun() as $task ) {
			$results[ $task->getName() ] = $this->runTask( $task );
		}

		return $results;
	}

	/**
	 * Run a single task.
	 *
	 * Executes a single scheduled task, handling before/after callbacks,
	 * mutex locking for overlap prevention, and error handling.
	 *
	 * @param ScheduledTask $task The task to run
	 *
	 * @return array{name: string, started_at: mixed, status: string, output: mixed, error: string|null, finished_at: mixed}
	 */
	public function runTask( ScheduledTask $task ): array {
		$result = [
			'name'       => $task->getName(),
			'started_at' => wpj_now(),
			'status'     => 'success',
			'output'     => null,
			'error'      => null,
		];

		// Set mutex if preventing overlap
		if ( $task->withoutOverlapping() ) {
			set_transient( $task->getMutexName(), true, $task->getMutexExpiresAt() );
		}

		try {
			$task->callBeforeCallbacks();
			$result['output'] = $task->handle();
			$task->callAfterCallbacks();
		} catch ( \Throwable $e ) {
			$result['status'] = 'failed';
			$result['error']  = $e->getMessage();
			$task->handleFailure( $e );
		} finally {
			// Clear mutex
			if ( $task->withoutOverlapping() ) {
				delete_transient( $task->getMutexName() );
			}
			$result['finished_at'] = wpj_now();
		}

		// Log result
		Hooks::doAction( 'task_completed', $task, $result );

		return $result;
	}

	/**
	 * Find a task by name.
	 *
	 * Searches for a registered task by its name.
	 *
	 * @param string $name The task name
	 *
	 * @return ScheduledTask|null The task if found, null otherwise
	 */
	public function find( string $name ): ?ScheduledTask {
		$task = $this->tasks->get( $name );

		return $task instanceof ScheduledTask ? $task : null;
	}

	/**
	 * Remove a task.
	 *
	 * Removes a task from the scheduler and clears its WordPress
	 * cron hook. Returns true if the task was found and removed.
	 *
	 * @param string $name The task name
	 *
	 * @return bool True if removed, false if not found
	 */
	public function remove( string $name ): bool {
		if ( ! $this->tasks->has( $name ) ) {
			return false;
		}

		$this->tasks->forget( $name );
		wp_clear_scheduled_hook( $this->hookPrefix . $name );

		return true;
	}

	/**
	 * Clear all tasks.
	 *
	 * Removes all tasks from the scheduler and clears their
	 * WordPress cron hooks.
	 *
	 * @return void
	 */
	public function clear(): void {
		foreach ( $this->tasks as $task ) {
			wp_clear_scheduled_hook( $this->hookPrefix . $task->getName() );
		}
		$this->tasks = new Collection();
	}

	/**
	 * Register schedules with WordPress cron.
	 *
	 * Registers custom cron schedules and adds all tasks to
	 * the WordPress cron system.
	 *
	 * @return void
	 */
	public function registerWithCron(): void {
		// Add custom intervals
		Hooks::filter( 'cron_schedules', [ $this, 'addCronSchedules' ] );

		// Register each task
		foreach ( $this->tasks as $task ) {
			$this->registerTaskWithCron( $task );
		}
	}

	/**
	 * Add custom cron schedules.
	 *
	 * Merges the scheduler's custom schedules with WordPress's
	 * existing cron schedules.
	 *
	 * @param array $schedules Existing WordPress cron schedules
	 *
	 * @return array Merged schedules
	 */
	public function addCronSchedules( array $schedules ): array {
		return array_merge( $schedules, $this->schedules );
	}

	/**
	 * Register a single task with WP-Cron.
	 *
	 * Registers a task's hook with WordPress cron and schedules
	 * it to run at the specified interval.
	 *
	 * @param ScheduledTask $task The task to register
	 *
	 * @return void
	 */
	private function registerTaskWithCron( ScheduledTask $task ): void {
		$hookName   = $this->hookPrefix . $task->getName();
		$recurrence = $this->expressionToRecurrence( $task->getExpression() );

		// Create a TaskRunner instance for this task
		// This shows a friendly class name in WP Control instead of "Closure"
		$runner = new TaskRunner( $task, $this );

		// Add an action handler using the TaskRunner's run method
		// This displays as "TaskRunner->run()" in WP Control
		add_action( $hookName, [ $runner, 'run' ] );

		// Schedule if not already scheduled
		if ( ! wp_next_scheduled( $hookName ) ) {
			wp_schedule_event( time(), $recurrence, $hookName );
		}
	}

	/**
	 * Convert cron expression to WP recurrence.
	 *
	 * Maps a standard cron expression to a WordPress cron schedule name.
	 * Falls back to 'hourly' if the expression is not recognized.
	 *
	 * @param string $expression The cron expression (e.g., '* * * * *')
	 *
	 * @return string The WordPress recurrence name
	 */
	private function expressionToRecurrence( string $expression ): string {
		$map = [
			'* * * * *'    => 'every_minute',
			'*/5 * * * *'  => 'every_five_minutes',
			'*/10 * * * *' => 'every_ten_minutes',
			'*/15 * * * *' => 'every_fifteen_minutes',
			'*/30 * * * *' => 'every_thirty_minutes',
			'0 * * * *'    => 'hourly',
			'0 */12 * * *' => 'twicedaily',
			'0 0 * * *'    => 'daily',
		];

		return $map[ $expression ] ?? 'hourly';
	}

	/**
	 * Get custom schedules.
	 *
	 * Returns all custom cron schedules registered with the scheduler.
	 *
	 * @return array<string, array{interval: int, display: string}>
	 */
	public function getSchedules(): array {
		return $this->schedules;
	}

	/**
	 * Add a custom schedule.
	 *
	 * Registers a new custom cron schedule that can be used
	 * when defining task frequencies.
	 *
	 * @param string $name The schedule name
	 * @param int $interval The interval in seconds
	 * @param string $display The display name
	 *
	 * @return static
	 */
	public function addSchedule( string $name, int $interval, string $display ): static {
		$this->schedules[ $name ] = [
			'interval' => $interval,
			'display'  => $display,
		];

		return $this;
	}
}
