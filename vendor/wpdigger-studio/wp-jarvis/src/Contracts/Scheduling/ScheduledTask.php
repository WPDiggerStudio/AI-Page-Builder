<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Scheduling;

/**
 * ScheduledTask Interface
 *
 * Defines the contract for scheduled tasks.
 */
interface ScheduledTask {
	/**
	 * Execute the scheduled task.
	 */
	public function handle(): mixed;

	/**
	 * Get the task name/identifier.
	 */
	public function getName(): string;

	/**
	 * Get the task description.
	 */
	public function getDescription(): string;

	/**
	 * Determine if the task should run.
	 */
	public function shouldRun(): bool;

	/**
	 * Get the cron expression.
	 */
	public function getExpression(): string;

	/**
	 * Get the mutex name for preventing overlaps.
	 */
	public function getMutexName(): ?string;

	/**
	 * Get the number of seconds the mutex should be valid.
	 */
	public function getMutexExpiresAt(): int;

	/**
	 * Determine if the task should run in the background.
	 */
	public function runsInBackground(): bool;

	/**
	 * Determine if the task should not overlap.
	 */
	public function withoutOverlapping(): bool;

	/**
	 * Run the task's before callbacks.
	 */
	public function callBeforeCallbacks(): void;

	/**
	 * Run the task's after callbacks.
	 */
	public function callAfterCallbacks(): void;

	/**
	 * Handle task failure.
	 */
	public function handleFailure( \Throwable $e ): void;
}
