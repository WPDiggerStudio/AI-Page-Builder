<?php

declare( strict_types=1 );

namespace BraCalculator\App\Listeners;

use BraCalculator\\App\Events\SendTestWelcomeEmail;

/**
 * HandleSendTestWelcomeEmail Listener
 *
 * Handles the SendTestWelcomeEmail event.
 *
 * @package BraCalculator\App\Listeners
 */
class HandleSendTestWelcomeEmail {
	/**
	 * Create the event listener.
	 */
	public function __construct() {
		// Inject dependencies here if needed
	}

	/**
	 * Handle the event.
	 *
	 * @param SendTestWelcomeEmail $sendTestWelcomeEmail
	 *
	 * @return void
	 */
	public function handle( SendTestWelcomeEmail $sendTestWelcomeEmail ): void {
		// Handle the event
		// Access event data: $sendTestWelcomeEmail->data
		// Or use getter: $sendTestWelcomeEmail->get('key')
	}

	/**
	 * Register this listener.
	 *
	 * @return void
	 */
	public static function register(): void {
		SendTestWelcomeEmail::listen( [ new static(), 'handle' ] );
	}

	/**
	 * Handle a job failure.
	 *
	 * @param SendTestWelcomeEmail $sendTestWelcomeEmail
	 * @param \Throwable $exception
	 *
	 * @return void
	 */
	public function failed( SendTestWelcomeEmail $sendTestWelcomeEmail, \Throwable $exception ): void {
		// Handle listener failure
		error_log( sprintf(
			'[%s] Failed to handle %s: %s',
			static::class,
			SendTestWelcomeEmail::class,
			$exception->getMessage()
		) );
	}
}
