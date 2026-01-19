<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Queue;

/**
 * Job Interface
 *
 * Defines the contract for queueable jobs.
 */
interface Job {
	/**
	 * Execute the job.
	 */
	public function handle(): void;

	/**
	 * Get the job identifier.
	 */
	public function getJobId(): string;

	/**
	 * Get the queue the job should be sent to.
	 */
	public function getQueue(): ?string;

	/**
	 * Get the number of seconds before the job should be processed.
	 */
	public function getDelay(): int;

	/**
	 * Get the number of times the job may be attempted.
	 */
	public function getTries(): int;

	/**
	 * Get the maximum number of seconds the job can run.
	 */
	public function getTimeout(): int;

	/**
	 * Determine if the job should be retried.
	 */
	public function shouldRetry( \Throwable $e, int $attempts ): bool;

	/**
	 * Handle a job failure.
	 */
	public function failed( \Throwable $e ): void;

	/**
	 * Get the tags for the job.
	 */
	public function tags(): array;
}
