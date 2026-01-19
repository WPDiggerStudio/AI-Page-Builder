<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Mail;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * MailException - Exception for mail operations.
 */
class MailException extends FrameworkException {
	/**
	 * Create an exception for no recipients.
	 *
	 * @return static The exception instance.
	 */
	public static function noRecipients(): static {
		return new static( __( 'No recipients specified for the email.', 'wp-jarvis' ) );
	}

	/**
	 * Create an exception for no template.
	 *
	 * @return static The exception instance.
	 */
	public static function noTemplate(): static {
		return new static( __( 'No template specified for the email.', 'wp-jarvis' ) );
	}

	/**
	 * Create exception for template not found.
	 *
	 * @param string $template The template name.
	 *
	 * @return static The exception instance.
	 */
	public static function templateNotFound( string $template ): static {
		return ( new static(
			sprintf(
			/* translators: %s: template name */
				__( "Email template '%s' not found.", 'wp-jarvis' ),
				$template
			)
		) )->withContext( [ 'template' => $template ] );
	}

	/**
	 * Create an exception for sent failure.
	 *
	 * @param string $reason The reason for failure.
	 *
	 * @return static The exception instance.
	 */
	public static function sendFailed( string $reason = '' ): static {
		$message = __( 'Failed to send email.', 'wp-jarvis' );
		if ( $reason ) {
			$message .= ' ' . sprintf(
				/* translators: %s: failure reason */
					__( 'Reason: %s', 'wp-jarvis' ),
					$reason
				);
		}

		return new static( $message );
	}

	/**
	 * Create an exception for invalid attachment.
	 *
	 * @param string $path The attachment path.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidAttachment( string $path ): static {
		return ( new static(
			sprintf(
			/* translators: %s: attachment file path */
				__( "Attachment not found: '%s'", 'wp-jarvis' ),
				$path
			)
		) )->withContext( [ 'path' => $path ] );
	}

	/**
	 * Create exception for invalid recipient.
	 *
	 * @param string $email The email address.
	 *
	 * @return static The exception instance.
	 */
	public static function invalidRecipient( string $email ): static {
		return ( new static(
			sprintf(
			/* translators: %s: email address */
				__( "Invalid email address: '%s'", 'wp-jarvis' ),
				$email
			)
		) )->withContext( [ 'email' => $email ] );
	}
}
