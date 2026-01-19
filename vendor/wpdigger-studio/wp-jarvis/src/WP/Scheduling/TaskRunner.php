<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Scheduling;

use WPJarvis\Framework\Contracts\Scheduling\ScheduledTask;

/**
 * TaskRunner - Invokable callback wrapper for scheduled tasks.
 *
 * This class wraps a scheduled task and provides an __invoke() method
 * that can be used as a WordPress action callback. This allows WP Cron tool
 * and similar plugins to display a friendly class name instead of "Closure".
 *
 * @package WPJarvis\Framework\WP\Scheduling
 */
class TaskRunner {
	/**
	 * The scheduled task instance.
	 */
	private ScheduledTask $task;

	/**
	 * The scheduler instance.
	 */
	private Scheduler $scheduler;

	/**
	 * Create a new task runner instance.
	 *
	 * @param ScheduledTask $task The task to run.
	 * @param Scheduler $scheduler The scheduler instance.
	 */
	public function __construct( ScheduledTask $task, Scheduler $scheduler ) {
		$this->task      = $task;
		$this->scheduler = $scheduler;
	}

	/**
	 * Execute the task when invoked.
	 *
	 * This method is called when WordPress triggers the scheduled hook.
	 * It checks if the task should run and delegates to the scheduler.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		$this->run();
	}

	/**
	 * Run the scheduled task.
	 *
	 * Named method for better introspection in WP Cron tool.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( $this->task->shouldRun() ) {
			$this->scheduler->runTask( $this->task );
		}
	}

	/**
	 * Get the task name.
	 *
	 * @return string
	 */
	public function getTaskName(): string {
		return $this->task->getName();
	}

	/**
	 * Get the task instance.
	 *
	 * @return ScheduledTask
	 */
	public function getTask(): ScheduledTask {
		return $this->task;
	}

	/**
	 * String representation for debugging.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return sprintf( __( 'TaskRunner(%s)', 'wp-jarvis' ), $this->task->getName() );
	}
}
