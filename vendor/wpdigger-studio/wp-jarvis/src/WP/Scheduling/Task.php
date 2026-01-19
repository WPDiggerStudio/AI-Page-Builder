<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Scheduling;

use WPJarvis\Framework\Contracts\Scheduling\ScheduledTask as ScheduledTaskContract;
use WPJarvis\Framework\Support\Facades\Config;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Task - Base class for scheduled tasks.
 */
abstract class Task implements ScheduledTaskContract {
	/**
	 * The task name.
	 */
	protected string $name = '';

	/**
	 * The task description.
	 */
	protected string $description = '';

	/**
	 * The cron expression.
	 */
	protected string $expression = '* * * * *';

	/**
	 * Whether to run in the background.
	 */
	protected bool $runInBackground = false;

	/**
	 * Whether to prevent overlapping.
	 */
	protected bool $preventOverlapping = false;

	/**
	 * Mutex expiration in seconds.
	 */
	protected int $mutexExpiration = 1440;

	/**
	 * Callbacks to run before the task.
	 */
	protected array $beforeCallbacks = [];

	/**
	 * Callbacks to run after the task.
	 */
	protected array $afterCallbacks = [];

	/**
	 * The environments the task should run in.
	 */
	protected array $environments = [];

	/**
	 * Execute the task.
	 */
	abstract public function handle(): mixed;

	/**
	 * Get the task name.
	 */
	public function getName(): string {
		return $this->name ?: static::class;
	}

	/**
	 * Get the task description.
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Determine if the task should run.
	 */
	public function shouldRun(): bool {
		if ( ! empty( $this->environments ) ) {
			$currentEnv = wp_get_environment_type();
			if ( ! in_array( $currentEnv, $this->environments, true ) ) {
				return false;
			}
		}

		return ! ( $this->preventOverlapping && $this->isRunning() );
	}

	/**
	 * Check if a task is currently running.
	 */
	private function isRunning(): bool {
		return get_transient( $this->getMutexName() ) !== false;
	}

	/**
	 * Get the cron expression.
	 */
	public function getExpression(): string {
		return $this->expression;
	}

	/**
	 * Get the mutex name.
	 */
	public function getMutexName(): ?string {
		$slug = Config::get( 'app.slug', 'wp-jarvis' );

		return $slug . '_task_mutex_' . md5( $this->getName() );
	}

	/**
	 * Get mutex expiration in seconds.
	 */
	public function getMutexExpiresAt(): int {
		return $this->mutexExpiration;
	}

	/**
	 * Whether a task runs in background.
	 */
	public function runsInBackground(): bool {
		return $this->runInBackground;
	}

	/**
	 * Whether to prevent overlapping.
	 */
	public function withoutOverlapping(): bool {
		return $this->preventOverlapping;
	}

	/**
	 * Run before callbacks.
	 */
	public function callBeforeCallbacks(): void {
		foreach ( $this->beforeCallbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				$callback( $this );
			}
		}
	}

	/**
	 * Run after callbacks.
	 */
	public function callAfterCallbacks(): void {
		foreach ( $this->afterCallbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				$callback( $this );
			}
		}
	}

	/**
	 * Handle task failure.
	 */
	public function handleFailure( \Throwable $e ): void {
		Hooks::doAction( 'task_failed', $this, $e );
	}

	// Fluent Scheduling Methods

	/**
	 * Run every minute.
	 */
	public function everyMinute(): static {
		$this->expression = '* * * * *';

		return $this;
	}

	/**
	 * Run every five minutes.
	 */
	public function everyFiveMinutes(): static {
		$this->expression = '*/5 * * * *';

		return $this;
	}

	/**
	 * Run every ten minutes.
	 */
	public function everyTenMinutes(): static {
		$this->expression = '*/10 * * * *';

		return $this;
	}

	/**
	 * Run every fifteen minutes.
	 */
	public function everyFifteenMinutes(): static {
		$this->expression = '*/15 * * * *';

		return $this;
	}

	/**
	 * Run every thirty minutes.
	 */
	public function everyThirtyMinutes(): static {
		$this->expression = '*/30 * * * *';

		return $this;
	}

	/**
	 * Run hourly.
	 */
	public function hourly(): static {
		$this->expression = '0 * * * *';

		return $this;
	}

	/**
	 * Run hourly at a specific minute.
	 */
	public function hourlyAt( int $minute ): static {
		$this->expression = "{$minute} * * * *";

		return $this;
	}

	/**
	 * Run daily.
	 */
	public function daily(): static {
		$this->expression = '0 0 * * *';

		return $this;
	}

	/**
	 * Run daily at a specific time.
	 */
	public function dailyAt( string $time ): static {
		$parts            = explode( ':', $time );
		$hour             = $parts[0] ?? '0';
		$minute           = $parts[1] ?? '0';
		$this->expression = "{$minute} {$hour} * * *";

		return $this;
	}

	/**
	 * Run twice daily.
	 */
	public function twiceDaily( int $first = 1, int $second = 13 ): static {
		$this->expression = "0 {$first},{$second} * * *";

		return $this;
	}

	/**
	 * Run weekly.
	 */
	public function weekly(): static {
		$this->expression = '0 0 * * 0';

		return $this;
	}

	/**
	 * Run weekly on a specific day.
	 */
	public function weeklyOn( int $day, string $time = '0:0' ): static {
		$parts            = explode( ':', $time );
		$hour             = $parts[0] ?? '0';
		$minute           = $parts[1] ?? '0';
		$this->expression = "{$minute} {$hour} * * {$day}";

		return $this;
	}

	/**
	 * Run monthly.
	 */
	public function monthly(): static {
		$this->expression = '0 0 1 * *';

		return $this;
	}

	/**
	 * Run monthly on a specific day.
	 */
	public function monthlyOn( int $day, string $time = '0:0' ): static {
		$parts            = explode( ':', $time );
		$hour             = $parts[0] ?? '0';
		$minute           = $parts[1] ?? '0';
		$this->expression = "{$minute} {$hour} {$day} * *";

		return $this;
	}

	/**
	 * Run yearly.
	 */
	public function yearly(): static {
		$this->expression = '0 0 1 1 *';

		return $this;
	}

	/**
	 * Only run on weekdays.
	 */
	public function weekdays(): static {
		$this->expression = preg_replace( '/\*$/', '1-5', $this->expression );

		return $this;
	}

	/**
	 * Only run on weekends.
	 */
	public function weekends(): static {
		$this->expression = preg_replace( '/\*$/', '0,6', $this->expression );

		return $this;
	}

	/**
	 * Set environments.
	 */
	public function environments( string ...$environments ): static {
		$this->environments = $environments;

		return $this;
	}

	/**
	 * Only run in production.
	 */
	public function production(): static {
		return $this->environments( 'production' );
	}

	/**
	 * Prevent overlapping.
	 */
	public function withoutOverlappingUsing( int $expiresAt = 1440 ): static {
		$this->preventOverlapping = true;
		$this->mutexExpiration    = $expiresAt;

		return $this;
	}

	/**
	 * Run in the background.
	 */
	public function runInBackground(): static {
		$this->runInBackground = true;

		return $this;
	}

	/**
	 * Add before callback.
	 */
	public function before( callable $callback ): static {
		$this->beforeCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Add after callback.
	 */
	public function after( callable $callback ): static {
		$this->afterCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Set the task name.
	 */
	public function name( string $name ): static {
		$this->name = $name;

		return $this;
	}

	/**
	 * Set task description.
	 */
	public function description( string $description ): static {
		$this->description = $description;

		return $this;
	}
}
