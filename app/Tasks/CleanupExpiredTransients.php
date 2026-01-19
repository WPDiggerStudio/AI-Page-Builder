<?php

declare( strict_types=1 );

namespace BraCalculator\App\Tasks;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\Support\Facades\Log;
use WPJarvis\Framework\WP\Scheduling\Task;

/**
 * CleanupExpiredTransients
 *
 * A scheduled task that cleans up expired WordPress transients hourly.
 * This helps keep the options table clean and improves database performance.
 *
 * @package BraCalculator\App\Tasks
 */
class CleanupExpiredTransients extends Task {
	/**
	 * The task name.
	 */
	protected string $name = 'cleanup-expired-transients';

	/**
	 * The task description.
	 */
	protected string $description = 'Cleanup Expired Transients Task';

	/**
	 * Create a new task instance.
	 */
	public function __construct() {
		// Run every hour
		$this->hourly();

		// Prevent overlapping - mutex expires in 10 minutes
		$this->withoutOverlappingUsing( 600 );
	}

	/**
	 * Execute the scheduled task.
	 *
	 * @return mixed
	 * @throws \Throwable
	 */
	public function handle(): mixed {
		Log::error( '[WP Jarvis] CleanupExpiredTransients::handle() executing!' );

		// Store proof of execution in WordPress options
		update_option( 'bra_calculator_last_cleanup_run', [
			'time'      => current_time( 'mysql' ),
			'timestamp' => time(),
		] );

		Log::info( 'Starting transient cleanup...' );

		try {
			$result = $this->execute();
			Log::info( "Transient cleanup completed. Removed {$result} expired transients." );

			// Update with a result
			update_option( 'bra_calculator_last_cleanup_result', [
				'time'    => current_time( 'mysql' ),
				'cleaned' => $result,
			] );

			return $result;
		} catch ( \Throwable $e ) {
			Log::error( 'Transient cleanup failed: ' . $e->getMessage(), 'error' );
			throw $e;
		}
	}

	/**
	 * Execute the main task logic.
	 *
	 * @return int Number of deleted transients.
	 */
	protected function execute(): int {
		global $wpdb;

		// Delete expired transient timeouts
		$timeouts = $wpdb->query(
			"DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_timeout_%'
             AND option_value < UNIX_TIMESTAMP()"
		);

		// Delete orphaned transients (those whose timeout has been deleted)
		$orphans = $wpdb->query(
			"DELETE a FROM {$wpdb->options} a
             LEFT JOIN {$wpdb->options} b ON
                CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12)) = b.option_name
             WHERE a.option_name LIKE '_transient_%'
             AND a.option_name NOT LIKE '_transient_timeout_%'
             AND b.option_name IS NULL"
		);

		// Also cleanup site transients for multisite
		if ( is_multisite() ) {
			$siteTimeouts = $wpdb->query(
				"DELETE FROM {$wpdb->sitemeta}
                 WHERE meta_key LIKE '_site_transient_timeout_%'
                 AND meta_value < UNIX_TIMESTAMP()"
			);

			return $timeouts + $orphans + $siteTimeouts;
		}

		return $timeouts + $orphans;
	}

	/**
	 * Handle task failure.
	 *
	 * @param \Throwable $e The exception that caused the failure.
	 */
	public function handleFailure( \Throwable $e ): void {
		parent::handleFailure( $e );

		// Optionally notify admin on failure
		if ( Hooks::applyFilters( 'notify_on_task_failure', false, $this->name ) ) {
			wp_mail(
				get_option( 'admin_email' ),
				__( 'Scheduled Task Failed: Transient Cleanup', 'bra-calculator' ),
				sprintf( 'Task "%s" failed with error: %s', $this->name, $e->getMessage() )
			);
		}
	}
}
