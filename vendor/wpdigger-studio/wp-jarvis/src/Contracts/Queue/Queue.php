<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Queue;

/**
 * Queue Interface
 *
 * Defines the contract for queue implementations.
 */
interface Queue {
	/**
	 * Push a new job onto the queue.
	 */
	public function push( Job $job ): string;

	/**
	 * Push a job onto the queue after a delay.
	 */
	public function later( int $delay, Job $job ): string;

	/**
	 * Pop the next job from the queue.
	 */
	public function pop( ?string $queue = null ): ?Job;

	/**
	 * Delete a job from the queue.
	 */
	public function delete( string $jobId ): bool;

	/**
	 * Release a job back onto the queue.
	 */
	public function release( Job $job, int $delay = 0 ): void;

	/**
	 * Get the size of the queue.
	 */
	public function size( ?string $queue = null ): int;

	/**
	 * Clear all jobs from the queue.
	 */
	public function clear( ?string $queue = null ): int;

	/**
	 * Get the connection name.
	 */
	public function getConnectionName(): string;
}
