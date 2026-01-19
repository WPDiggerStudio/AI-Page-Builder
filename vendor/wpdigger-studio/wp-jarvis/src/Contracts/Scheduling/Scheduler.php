<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Scheduling;

use Illuminate\Support\Collection;

/**
 * Scheduler Interface
 *
 * Defines the contract for the task scheduler.
 */
interface Scheduler {
	/**
	 * Add a task to the schedule.
	 */
	public function add( ScheduledTask $task ): static;

	/**
	 * Get all scheduled tasks.
	 */
	public function all(): Collection;

	/**
	 * Get tasks that are due to run.
	 */
	public function dueToRun(): Collection;

	/**
	 * Run all due tasks.
	 */
	public function runDueTasks(): array;

	/**
	 * Find a task by name.
	 */
	public function find( string $name ): ?ScheduledTask;

	/**
	 * Remove a task from the schedule.
	 */
	public function remove( string $name ): bool;

	/**
	 * Clear all scheduled tasks.
	 */
	public function clear(): void;

	/**
	 * Register schedules with WordPress cron.
	 */
	public function registerWithCron(): void;
}
