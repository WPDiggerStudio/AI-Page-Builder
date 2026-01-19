<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Mail;

use WPJarvis\Framework\Contracts\Mail\Mailable as MailableContract;
use WPJarvis\Framework\Contracts\Mail\Mailer as MailerContract;

/**
 * Mailer - Main mail service for sending emails.
 */
class Mailer implements MailerContract {
	/**
	 * Default from address.
	 *
	 * @var string
	 */
	private string $defaultFrom;

	/**
	 * Default from name.
	 *
	 * @var string
	 */
	private string $defaultFromName;

	/**
	 * Failed recipients from last send.
	 *
	 * @var array<string>
	 */
	private array $failures = [];

	/**
	 * Create a new mailer instance.
	 *
	 * @param string|null $defaultFrom Optional default from email address.
	 * @param string|null $defaultFromName Optional default from name.
	 */
	public function __construct( ?string $defaultFrom = null, ?string $defaultFromName = null ) {
		$this->defaultFrom     = $defaultFrom ?? get_option( 'admin_email' );
		$this->defaultFromName = $defaultFromName ?? get_option( 'blogname' );
	}

	/**
	 * Send a mailable.
	 *
	 * @param MailableContract $mailable The mailable to send.
	 *
	 * @return bool True if the email was sent successfully.
	 * @throws \Throwable If sending fails.
	 */
	public function send( MailableContract $mailable ): bool {
		$this->failures = [];

		try {
			return $mailable->send();
		} catch ( \Throwable $e ) {
			$this->failures = $mailable->getTo();
			throw $e;
		}
	}

	/**
	 * Send raw content.
	 *
	 * @param string $content The email content.
	 * @param array<string> $to The recipients.
	 * @param string $subject The email subject.
	 * @param array<string, mixed> $options Additional options (headers, attachments, html, from).
	 *
	 * @return bool True if the email was sent successfully.
	 */
	public function raw( string $content, array $to, string $subject, array $options = [] ): bool {
		$headers     = $options['headers'] ?? [];
		$attachments = $options['attachments'] ?? [];

		if ( ! empty( $options['html'] ) ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		if ( ! empty( $options['from'] ) ) {
			$headers[] = 'From: ' . $options['from'];
		}

		return wp_mail( $to, $subject, $content, $headers, $attachments );
	}

	/**
	 * Queue a mailable for later sending.
	 *
	 * @param MailableContract $mailable The mailable to queue.
	 * @param string|null $queue Optional queue name.
	 *
	 * @return mixed The result of the queue operation.
	 */
	public function queue( MailableContract $mailable, ?string $queue = null ): mixed {
		// Would integrate with a Queue system
		return $mailable->queue( $queue );
	}

	/**
	 * Send a mailable after a delay.
	 *
	 * @param int $delay The delay in seconds.
	 * @param MailableContract $mailable The mailable to send.
	 * @param string|null $queue Optional queue name.
	 *
	 * @return mixed The result of the scheduling operation.
	 */
	public function later( int $delay, MailableContract $mailable, ?string $queue = null ): mixed {
		// Would integrate with a Queue system with delay
		// For now, schedule with WP-Cron
		wp_schedule_single_event( time() + $delay, 'WpJarvis_send_mail', [ serialize( $mailable ) ] );

		return true;
	}

	/**
	 * Get an array of failed recipients.
	 *
	 * @return array<string> The array of failed recipient email addresses.
	 */
	public function failures(): array {
		return $this->failures;
	}

	/**
	 * Set default from address.
	 *
	 * @param string $email The default from email address.
	 * @param string|null $name Optional default from name.
	 *
	 * @return static The current instance for chaining.
	 */
	public function setDefaultFrom( string $email, ?string $name = null ): static {
		$this->defaultFrom = $email;
		if ( $name ) {
			$this->defaultFromName = $name;
		}

		return $this;
	}

	/**
	 * Get default from address.
	 *
	 * @return string The default from email address.
	 */
	public function getDefaultFrom(): string {
		return $this->defaultFrom;
	}

	/**
	 * Get default from name.
	 *
	 * @return string The default from name.
	 */
	public function getDefaultFromName(): string {
		return $this->defaultFromName;
	}
}
