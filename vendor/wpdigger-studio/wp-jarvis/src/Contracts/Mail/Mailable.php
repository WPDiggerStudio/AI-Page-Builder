<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Mail;

/**
 * Mailable Interface
 *
 * Defines the contract for mailable classes.
 */
interface Mailable {
	/**
	 * Build the message.
	 */
	public function build(): static;

	/**
	 * Set the recipients.
	 */
	public function to( string|array $address, ?string $name = null ): static;

	/**
	 * Set the CC recipients.
	 */
	public function cc( string|array $address, ?string $name = null ): static;

	/**
	 * Set the BCC recipients.
	 */
	public function bcc( string|array $address, ?string $name = null ): static;

	/**
	 * Set the reply-to address.
	 */
	public function replyTo( string $address, ?string $name = null ): static;

	/**
	 * Set the subject.
	 */
	public function subject( string $subject ): static;

	/**
	 * Attach a file.
	 */
	public function attach( string $path, array $options = [] ): static;

	/**
	 * Set the template.
	 */
	public function template( string $template, array $data = [] ): static;

	/**
	 * Send the message.
	 */
	public function send(): bool;

	/**
	 * Queue the message for sending.
	 */
	public function queue( ?string $queue = null ): mixed;

	/**
	 * Render the message content.
	 */
	public function render(): string;
}
