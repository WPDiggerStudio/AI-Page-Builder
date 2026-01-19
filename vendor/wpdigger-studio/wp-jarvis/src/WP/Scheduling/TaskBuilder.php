<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Scheduling;

use WPJarvis\Framework\Contracts\Scheduling\ScheduledTask;

/**
 * TaskBuilder - Fluent builder for creating scheduled tasks.
 *
 * This class provides a fluent interface for building and configuring
 * scheduled tasks with various scheduling options like frequency,
 * environment restrictions, and callbacks.
 *
 * @package WPJarvis\Framework\WP\Scheduling
 */
class TaskBuilder {
	/**
	 * The scheduler instance.
	 *
	 * @var Scheduler
	 */
	private Scheduler $scheduler;

	/**
	 * The task callback.
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * The task name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The cron expression.
	 *
	 * @var string
	 */
	private string $expression = '* * * * *';

	/**
	 * Whether to prevent overlapping.
	 *
	 * @var bool
	 */
	private bool $preventOverlapping = false;

	/**
	 * Mutex expiration in seconds.
	 *
	 * @var int
	 */
	private int $mutexExpiration = 1440;

	/**
	 * Whether to run in the background.
	 *
	 * @var bool
	 */
	private bool $runInBackground = false;

	/**
	 * Callbacks to run before the task.
	 *
	 * @var array<callable>
	 */
	private array $beforeCallbacks = [];

	/**
	 * Callbacks to run after the task.
	 *
	 * @var array<callable>
	 */
	private array $afterCallbacks = [];

	/**
	 * The environments the task should run in.
	 *
	 * @var array<string>
	 */
	private array $environments = [];

	/**
	 * The task description.
	 *
	 * @var string
	 */
	private string $description = '';

	/**
	 * Create a new TaskBuilder instance.
	 *
	 * @param Scheduler $scheduler The scheduler instance
	 * @param callable $callback The task callback
	 * @param string $name The task name
	 */
	public function __construct( Scheduler $scheduler, callable $callback, string $name ) {
		$this->scheduler = $scheduler;
		$this->callback  = $callback;
		$this->name      = $name ?: spl_object_hash( (object) $callback );
	}

	/**
	 * Register the task with the scheduler when the builder is destroyed.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->scheduler->add( $this->build() );
	}

	/**
	 * Build and return the scheduled task.
	 *
	 * Creates an anonymous Task class that wraps the callback
	 * and applies all configured settings using fluent methods.
	 *
	 * @return ScheduledTask The built task
	 */
	protected function build(): ScheduledTask {
		$task = new class( $this->callback, $this->name ) extends Task {
			private $callback;
			protected string $name;

			public function __construct( callable $callback, string $name ) {
				$this->callback = $callback;
				$this->name     = $name;
			}

			public function handle(): mixed {
				return call_user_func( $this->callback );
			}

			public function getName(): string {
				return $this->name;
			}
		};

		// Configure the task using fluent methods
		$task->description( $this->description );
		$task->name( $this->name );

		// Set cron expression using the appropriate fluent method
		$expression = $this->expression;
		if ( $expression === '* * * * *' ) {
			$task->everyMinute();
		} elseif ( $expression === '*/5 * * * *' ) {
			$task->everyFiveMinutes();
		} elseif ( $expression === '*/10 * * * *' ) {
			$task->everyTenMinutes();
		} elseif ( $expression === '*/15 * * * *' ) {
			$task->everyFifteenMinutes();
		} elseif ( $expression === '*/30 * * * *' ) {
			$task->everyThirtyMinutes();
		} elseif ( $expression === '0 * * * *' ) {
			$task->hourly();
		} elseif ( $expression === '0 0 * * *' ) {
			$task->daily();
		} elseif ( $expression === '0 0 * * 0' ) {
			$task->weekly();
		} elseif ( $expression === '0 0 1 * *' ) {
			$task->monthly();
		} elseif ( $expression === '0 0 1 1 *' ) {
			$task->yearly();
		}

		// Set other properties using fluent methods
		if ( $this->preventOverlapping ) {
			$task->withoutOverlappingUsing( $this->mutexExpiration );
		}

		if ( $this->runInBackground ) {
			$task->runInBackground();
		}

		if ( ! empty( $this->environments ) ) {
			$task->environments( ...$this->environments );
		}

		foreach ( $this->beforeCallbacks as $callback ) {
			$task->before( $callback );
		}

		foreach ( $this->afterCallbacks as $callback ) {
			$task->after( $callback );
		}

		return $task;
	}

	/**
	 * Run every minute.
	 *
	 * @return static
	 */
	public function everyMinute(): static {
		$this->expression = '* * * * *';

		return $this;
	}

	/**
	 * Run every five minutes.
	 *
	 * @return static
	 */
	public function everyFiveMinutes(): static {
		$this->expression = '*/5 * * * *';

		return $this;
	}

	/**
	 * Run every ten minutes.
	 *
	 * @return static
	 */
	public function everyTenMinutes(): static {
		$this->expression = '*/10 * * * *';

		return $this;
	}

	/**
	 * Run every fifteen minutes.
	 *
	 * @return static
	 */
	public function everyFifteenMinutes(): static {
		$this->expression = '*/15 * * * *';

		return $this;
	}

	/**
	 * Run every thirty minutes.
	 *
	 * @return static
	 */
	public function everyThirtyMinutes(): static {
		$this->expression = '*/30 * * * *';

		return $this;
	}

	/**
	 * Run hourly.
	 *
	 * @return static
	 */
	public function hourly(): static {
		$this->expression = '0 * * * *';

		return $this;
	}

	/**
	 * Run hourly at a specific minute.
	 *
	 * @param int $minute The minute (0-59)
	 *
	 * @return static
	 */
	public function hourlyAt( int $minute ): static {
		$this->expression = "{$minute} * * * *";

		return $this;
	}

	/**
	 * Run daily.
	 *
	 * @return static
	 */
	public function daily(): static {
		$this->expression = '0 0 * * *';

		return $this;
	}

	/**
	 * Run daily at a specific time.
	 *
	 * @param string $time The time in HH:MM format
	 *
	 * @return static
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
	 *
	 * @param int $first The first hour (0-23)
	 * @param int $second The second hour (0-23)
	 *
	 * @return static
	 */
	public function twiceDaily( int $first = 1, int $second = 13 ): static {
		$this->expression = "0 {$first},{$second} * * *";

		return $this;
	}

	/**
	 * Run weekly.
	 *
	 * @return static
	 */
	public function weekly(): static {
		$this->expression = '0 0 * * 0';

		return $this;
	}

	/**
	 * Run weekly on a specific day.
	 *
	 * @param int $day The day of the week (0-6, 0 = Sunday)
	 * @param string $time The time in HH:MM format
	 *
	 * @return static
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
	 *
	 * @return static
	 */
	public function monthly(): static {
		$this->expression = '0 0 1 * *';

		return $this;
	}

	/**
	 * Run monthly on a specific day.
	 *
	 * @param int $day The day of the month (1-31)
	 * @param string $time The time in HH:MM format
	 *
	 * @return static
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
	 *
	 * @return static
	 */
	public function yearly(): static {
		$this->expression = '0 0 1 1 *';

		return $this;
	}

	/**
	 * Only run on weekdays.
	 *
	 * @return static
	 */
	public function weekdays(): static {
		$this->expression = preg_replace( '/\*$/', '1-5', $this->expression );

		return $this;
	}

	/**
	 * Only run on weekends.
	 *
	 * @return static
	 */
	public function weekends(): static {
		$this->expression = preg_replace( '/\*$/', '0,6', $this->expression );

		return $this;
	}

	/**
	 * Set environments.
	 *
	 * @param string ...$environments The environments to run in
	 *
	 * @return static
	 */
	public function environments( string ...$environments ): static {
		$this->environments = $environments;

		return $this;
	}

	/**
	 * Only run in production.
	 *
	 * @return static
	 */
	public function production(): static {
		return $this->environments( 'production' );
	}

	/**
	 * Prevent overlapping.
	 *
	 * @param int $expiresAt The mutex expiration in seconds
	 *
	 * @return static
	 */
	public function withoutOverlappingUsing( int $expiresAt = 1440 ): static {
		$this->preventOverlapping = true;
		$this->mutexExpiration    = $expiresAt;

		return $this;
	}

	/**
	 * Run in the background.
	 *
	 * @return static
	 */
	public function runInBackground(): static {
		$this->runInBackground = true;

		return $this;
	}

	/**
	 * Add before callback.
	 *
	 * @param callable $callback The callback to run before the task
	 *
	 * @return static
	 */
	public function before( callable $callback ): static {
		$this->beforeCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Add after callback.
	 *
	 * @param callable $callback The callback to run after the task
	 *
	 * @return static
	 */
	public function after( callable $callback ): static {
		$this->afterCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Set the task name.
	 *
	 * @param string $name The task name
	 *
	 * @return static
	 */
	public function name( string $name ): static {
		$this->name = $name;

		return $this;
	}

	/**
	 * Set task description.
	 *
	 * @param string $description The task description
	 *
	 * @return static
	 */
	public function description( string $description ): static {
		$this->description = $description;

		return $this;
	}
}
