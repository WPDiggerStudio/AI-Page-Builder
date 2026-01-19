<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Mail;

/**
 * Mailer Interface
 *
 * Defines the contract for the mail sending service.
 */
interface Mailer {
	/**
	 * Send a mailable.
	 */
	public function send( Mailable $mailable ): bool;

	/**
	 * Send raw content.
	 */
	public function raw( string $content, array $to, string $subject, array $options = [] ): bool;

	/**
	 * Queue a mailable for sending.
	 */
	public function queue( Mailable $mailable, ?string $queue = null ): mixed;

	/**
	 * Send a mailable later.
	 */
	public function later( int $delay, Mailable $mailable, ?string $queue = null ): mixed;

	/**
	 * Get the array of failed recipients.
	 */
	public function failures(): array;
}
