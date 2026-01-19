<?php

declare( strict_types=1 );

namespace BraCalculator\App\Console;

use BraCalculator\App\Tasks\CleanupExpiredTransients;
use BraCalculator\App\Tasks\SendAdminNotifications;
use BraCalculator\App\Tasks\SyncProductInventory;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Scheduling\Scheduler;

/**
 * Console Kernel
 *
 * Define your scheduled tasks here, just like Laravel's Console Kernel.
 * The schedule() method is called when the scheduler boots.
 *
 * @package BraCalculator\App\Console
 */
class Kernel {
	/**
	 * Define the application's command schedule.
	 *
	 * This method is called by the framework's ScheduleServiceProvider.
	 * Use the $schedule variable to define your recurring tasks.
	 *
	 * @param Scheduler $schedule
	 *
	 * @return void
	 */
	public function schedule( Scheduler $schedule ): void {
		// ===================================================================
		// TASK CLASSES (Generated via make:task)
		// ===================================================================

		// Cleanup expired transients every hour
		$schedule->add( new CleanupExpiredTransients() );

		// Sync product inventory every 5 minutes
		$schedule->add( new SyncProductInventory() );

		// Send admin notifications daily at 9 AM
		$schedule->add( new SendAdminNotifications() );

		// ===================================================================
		// SCHEDULING CALLBACKS (Inline tasks)
		// ===================================================================

		// Example: Run a quick cleanup callback daily
		// $schedule->call(function () {
		//     delete_expired_transients();
		// })->daily()->name('quick-cleanup');

		// ===================================================================
		// SCHEDULING ARTISAN COMMANDS
		// ===================================================================

		// Example: Clear cache hourly
		// $schedule->command('cache:clear')->hourly();

		// ===================================================================
		// SCHEDULING JOBS (Queue-based)
		// ===================================================================

		// Example: Process payments every 5 minutes
		// $schedule->job(new ProcessPayments)->everyFiveMinutes();

		// Example: Send emails on a dedicated queue
		// $schedule->job(new SendEmails, 'emails')->daily();

		// ===================================================================
		// COMMON SCHEDULE FREQUENCIES
		// ===================================================================
		// ->everyMinute();           Every minute
		// ->everyFiveMinutes();      Every 5 minutes
		// ->everyTenMinutes();       Every 10 minutes
		// ->everyFifteenMinutes();   Every 15 minutes
		// ->everyThirtyMinutes();    Every 30 minutes
		// ->hourly();                Every hour
		// ->hourlyAt(15);            Every hour at :15
		// ->daily();                 Daily at midnight
		// ->dailyAt('13:00');        Daily at 1 PM
		// ->twiceDaily(1, 13);       Twice daily at 1 AM and 1 PM
		// ->weekly();                Weekly on Sunday at midnight
		// ->weeklyOn(1, '8:00');     Weekly on Monday at 8 AM
		// ->monthly();               Monthly on the 1st at midnight
		// ->monthlyOn(15, '9:00');   Monthly on the 15th at 9 AM
		// ->yearly();                Yearly on January 1st

		// ===================================================================
		// SCHEDULE MODIFIERS
		// ===================================================================
		// ->weekdays();                        Only on weekdays (Mon-Fri)
		// ->weekends();                        Only on weekends (Sat-Sun)
		// ->withoutOverlappingUsing(600);      Prevent overlapping (mutex expires in 600s)
		// ->runInBackground();                 Run in background
		// ->environments('production');        Only in production
		// ->production();                      Shorthand for production only
		// ->before(function () { ... });       Run before task
		// ->after(function () { ... });        Run after task
	}

	/**
	 * Register the kernel with the scheduler.
	 *
	 * Call this method in your plugin's boot or service provider:
	 *   \BraCalculator\App\Console\Kernel::register();
	 *
	 * @return void
	 */
	public static function register(): void {
		Hooks::action( 'define_schedule', static function ( Scheduler $schedule ) {
			$kernel = new static();
			$kernel->schedule( $schedule );
		} );
	}
}
