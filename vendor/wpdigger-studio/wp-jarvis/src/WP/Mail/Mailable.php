<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Mail;

use WPJarvis\Framework\Contracts\Mail\Mailable as MailableContract;
use WPJarvis\Framework\Exceptions\Mail\MailException;

/**
 * Mailable - Base class for all mailable classes.
 *
 * Provides a fluent interface for building and sending emails.
 * Inspired by Laravel's Mailable and WP_Mail.php.
 */
abstract class Mailable implements MailableContract {
	/**
	 * The recipients of the email.
	 *
	 * @var array<string>
	 */
	protected array $to = [];

	/**
	 * The CC recipients of the email.
	 *
	 * @var array<string>
	 */
	protected array $cc = [];

	/**
	 * The BCC recipients of the email.
	 *
	 * @var array<string>
	 */
	protected array $bcc = [];

	/**
	 * The reply-to addresses of the email.
	 *
	 * @var array<string>
	 */
	protected array $replyTo = [];

	/**
	 * Custom email headers.
	 *
	 * @var array<string>
	 */
	protected array $headers = [];

	/**
	 * File attachments.
	 *
	 * @var array<string>
	 */
	protected array $attachments = [];

	/**
	 * The email subject.
	 *
	 * @var string
	 */
	protected string $subject = '';

	/**
	 * The from email address.
	 *
	 * @var string
	 */
	protected string $from = '';

	/**
	 * The from name.
	 *
	 * @var string
	 */
	protected string $fromName = '';

	/**
	 * Whether to send it as HTML.
	 *
	 * @var bool
	 */
	protected bool $sendAsHtml = true;

	/**
	 * The header template path.
	 *
	 * @var string|null
	 */
	protected ?string $headerTemplate = null;

	/**
	 * Variables for the header template.
	 *
	 * @var array<string, mixed>
	 */
	protected array $headerVariables = [];

	/**
	 * The main template path.
	 *
	 * @var string|null
	 */
	protected ?string $template = null;

	/**
	 * Variables for the main template.
	 *
	 * @var array<string, mixed>
	 */
	protected array $variables = [];

	/**
	 * The footer template path.
	 *
	 * @var string|null
	 */
	protected ?string $footerTemplate = null;

	/**
	 * Variables for the footer template.
	 *
	 * @var array<string, mixed>
	 */
	protected array $footerVariables = [];

	/**
	 * Build the message.
	 *
	 * This method should be implemented by child classes to configure
	 * email properties like a subject, recipients, and templates.
	 *
	 * @return static The current instance for chaining.
	 */
	abstract public function build(): static;

	/**
	 * Create a new mailable instance.
	 *
	 * @return static The new mailable instance.
	 */
	public static function make(): static {
		return new static();
	}

	/**
	 * Set the recipients.
	 *
	 * @param string|array<string> $address The email address or array of addresses.
	 * @param string|null $name Optional name for a single address.
	 *
	 * @return static The current instance for chaining.
	 */
	public function to( string|array $address, ?string $name = null ): static {
		if ( is_array( $address ) ) {
			$this->to = array_merge( $this->to, $address );
		} else {
			$this->to[] = $name ? "{$name} <{$address}>" : $address;
		}

		return $this;
	}

	/**
	 * Set the CC recipients.
	 *
	 * @param string|array<string> $address The email address or array of addresses.
	 * @param string|null $name Optional name for a single address.
	 *
	 * @return static The current instance for chaining.
	 */
	public function cc( string|array $address, ?string $name = null ): static {
		if ( is_array( $address ) ) {
			$this->cc = array_merge( $this->cc, $address );
		} else {
			$this->cc[] = $name ? "{$name} <{$address}>" : $address;
		}

		return $this;
	}

	/**
	 * Set the BCC recipients.
	 *
	 * @param string|array<string> $address The email address or array of addresses.
	 * @param string|null $name Optional name for a single address.
	 *
	 * @return static The current instance for chaining.
	 */
	public function bcc( string|array $address, ?string $name = null ): static {
		if ( is_array( $address ) ) {
			$this->bcc = array_merge( $this->bcc, $address );
		} else {
			$this->bcc[] = $name ? "{$name} <{$address}>" : $address;
		}

		return $this;
	}

	/**
	 * Set the reply-to address.
	 *
	 * @param string $address The email address.
	 * @param string|null $name Optional name.
	 *
	 * @return static The current instance for chaining.
	 */
	public function replyTo( string $address, ?string $name = null ): static {
		$this->replyTo[] = $name ? "{$name} <{$address}>" : $address;

		return $this;
	}

	/**
	 * Set the from address.
	 *
	 * @param string $address The email address.
	 * @param string|null $name Optional name.
	 *
	 * @return static The current instance for chaining.
	 */
	public function from( string $address, ?string $name = null ): static {
		$this->from     = $address;
		$this->fromName = $name ?? '';

		return $this;
	}

	/**
	 * Set the subject.
	 *
	 * @param string $subject The email subject.
	 *
	 * @return static The current instance for chaining.
	 */
	public function subject( string $subject ): static {
		$this->subject = $subject;

		return $this;
	}

	/**
	 * Set custom headers.
	 *
	 * @param array<string> $headers The custom headers to add.
	 *
	 * @return static The current instance for chaining.
	 */
	public function headers( array $headers ): static {
		$this->headers = array_merge( $this->headers, $headers );

		return $this;
	}

	/**
	 * Attach a file.
	 *
	 * @param string $path The path to the file.
	 * @param array<string, mixed> $options Additional attachment options.
	 *
	 * @return static The current instance for chaining.
	 * @throws MailException If the file does not exist.
	 */
	public function attach( string $path, array $options = [] ): static {
		if ( ! file_exists( $path ) ) {
			throw MailException::invalidAttachment( $path );
		}
		$this->attachments[] = $path;

		return $this;
	}

	/**
	 * Attach multiple files.
	 *
	 * @param array<string> $paths Array of file paths.
	 *
	 * @return static The current instance for chaining.
	 * @throws \WPJarvis\Framework\Exceptions\Mail\MailException
	 */
	public function attachMany( array $paths ): static {
		foreach ( $paths as $path ) {
			$this->attach( $path );
		}

		return $this;
	}

	/**
	 * Set whether to send as HTML.
	 *
	 * @param bool $html Whether to send it as HTML. Defaults to true.
	 *
	 * @return static The current instance for chaining.
	 */
	public function asHtml( bool $html = true ): static {
		$this->sendAsHtml = $html;

		return $this;
	}

	/**
	 * Set the header template.
	 *
	 * @param string $template The template path or name.
	 * @param array<string, mixed> $variables Variables to pass to the template.
	 *
	 * @return static The current instance for chaining.
	 */
	public function templateHeader( string $template, array $variables = [] ): static {
		$this->headerTemplate  = $template;
		$this->headerVariables = $variables;

		return $this;
	}

	/**
	 * Set the main template.
	 *
	 * @param string $template The template path or name.
	 * @param array<string, mixed> $data Variables to pass to the template.
	 *
	 * @return static The current instance for chaining.
	 */
	public function template( string $template, array $data = [] ): static {
		$this->template  = $template;
		$this->variables = $data;

		return $this;
	}

	/**
	 * Set the footer template.
	 *
	 * @param string $template The template path or name.
	 * @param array<string, mixed> $variables Variables to pass to the template.
	 *
	 * @return static The current instance for chaining.
	 */
	public function templateFooter( string $template, array $variables = [] ): static {
		$this->footerTemplate  = $template;
		$this->footerVariables = $variables;

		return $this;
	}

	/**
	 * Add/merge template variables.
	 *
	 * @param string|array<string, mixed> $key The variable name or array of variables.
	 * @param mixed $value The variable value if $key is a string.
	 *
	 * @return static The current instance for chaining.
	 */
	public function with( string|array $key, mixed $value = null ): static {
		if ( is_array( $key ) ) {
			$this->variables = array_merge( $this->variables, $key );
		} else {
			$this->variables[ $key ] = $value;
		}

		return $this;
	}

	/**
	 * Render the complete email content.
	 *
	 * Renders the header, main, and footer templates combined.
	 *
	 * @return string The rendered email content.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function render(): string {
		return $this->renderPart( 'header' ) .
		       $this->renderPart( 'main' ) .
		       $this->renderPart( 'footer' );
	}

	/**
	 * Render a specific part of the email.
	 *
	 * @param string $part The part to render ('header', 'footer', or 'main').
	 *
	 * @return string The rendered part content.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function renderPart( string $part ): string {
		$template = match ( $part ) {
			'header' => $this->headerTemplate,
			'footer' => $this->footerTemplate,
			default => $this->template,
		};

		$variables = match ( $part ) {
			'header' => $this->headerVariables,
			'footer' => $this->footerVariables,
			default => $this->variables,
		};

		if ( ! $template ) {
			return '';
		}

		$templatePath = $this->resolveTemplatePath( $template );
		if ( ! $templatePath ) {
			return '';
		}

		$extension = strtolower( pathinfo( $templatePath, PATHINFO_EXTENSION ) );

		if ( $extension === 'php' ) {
			return $this->renderPhpTemplate( $templatePath, $variables );
		}

		return $this->renderHtmlTemplate( $templatePath, $variables );
	}

	/**
	 * Resolve the template path.
	 *
	 * Checks for the template in various locations, including absolute paths,
	 * theme directories, and framework directories.
	 *
	 * @param string $template The template name or path.
	 *
	 * @return string|null The resolved template path, or null if not found.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function resolveTemplatePath( string $template ): ?string {
		// Check if it's an absolute path
		if ( file_exists( $template ) ) {
			return $template;
		}

		// Check in resources/views
		$basePaths = [
			get_stylesheet_directory() . '/resources/views/',
			get_template_directory() . '/resources/views/',
			wpj_app()->basePath( '/resources/views/' ),
		];

		$extensions = [ 'php', 'html' ];

		foreach ( $basePaths as $basePath ) {
			if ( ! $basePath ) {
				continue;
			}
			foreach ( $extensions as $ext ) {
				$path = $basePath . $template . '.' . $ext;
				if ( file_exists( $path ) ) {
					return $path;
				}
			}
		}

		return null;
	}

	/**
	 * Render a PHP template.
	 *
	 * Uses output buffering to include the PHP template file with variables.
	 *
	 * @param string $path The template file path.
	 * @param array<string, mixed> $variables Variables to extract into the template scope.
	 *
	 * @return string The rendered template content.
	 */
	private function renderPhpTemplate( string $path, array $variables ): string {
		ob_start();
		extract( $variables );
		include $path;

		return ob_get_clean();
	}

	/**
	 * Render an HTML template with mustache-style variables.
	 *
	 * Loads the HTML file and replaces {{variable}} placeholders.
	 *
	 * @param string $path The template file path.
	 * @param array<string, mixed> $variables Variables to replace in the template.
	 *
	 * @return string The rendered template content.
	 */
	private function renderHtmlTemplate( string $path, array $variables ): string {
		$content = file_get_contents( $path );

		return $this->parseVariables( $content, $variables );
	}

	/**
	 * Parse mustache-style variables.
	 *
	 * Replaces {{key}} placeholders with corresponding values from variables.
	 *
	 * @param string $content The content to parse.
	 * @param array<string, mixed> $variables The variables to replace.
	 *
	 * @return string The parsed content.
	 */
	private function parseVariables( string $content, array $variables ): string {
		preg_match_all( '/{{\s*(.+?)\s*}}/', $content, $matches );

		foreach ( $matches[0] as $i => $match ) {
			$key = trim( $matches[1][ $i ] );

			if ( array_key_exists( $key, $variables ) && ! is_array( $variables[ $key ] ) ) {
				$content = str_replace( $match, (string) $variables[ $key ], $content );
			}
		}

		return $content;
	}

	/**
	 * Build the subject with variables.
	 *
	 * Parses the subject string to replace any mustache-style variables.
	 *
	 * @return string The parsed subject.
	 */
	private function buildSubject(): string {
		$allVariables = array_merge(
			$this->headerVariables,
			$this->variables,
			$this->footerVariables
		);

		return $this->parseVariables( $this->subject, $allVariables );
	}

	/**
	 * Build the email headers.
	 *
	 * Constructs the complete headers array including CC, BCC, Reply-To,
	 * From, and Content-Type headers.
	 *
	 * @return array<string> The complete headers array.
	 */
	private function buildHeaders(): array {
		$headers = $this->headers;

		foreach ( $this->cc as $cc ) {
			$headers[] = "Cc: {$cc}";
		}

		foreach ( $this->bcc as $bcc ) {
			$headers[] = "Bcc: {$bcc}";
		}

		foreach ( $this->replyTo as $replyTo ) {
			$headers[] = "Reply-To: {$replyTo}";
		}

		if ( $this->from ) {
			$from      = $this->fromName ? "{$this->fromName} <{$this->from}>" : $this->from;
			$headers[] = "From: {$from}";
		}

		if ( $this->sendAsHtml ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		return $headers;
	}

	/**
	 * Send the email.
	 *
	 * Builds the email configuration and sends it via wp_mail().
	 *
	 * @return bool True if the email was sent successfully.
	 * @throws MailException If there are no recipients or no template set.
	 * @throws MailException|\Illuminate\Contracts\Container\BindingResolutionException If sending the email fails.
	 */
	public function send(): bool {
		$this->build();

		if ( empty( $this->to ) ) {
			throw MailException::noRecipients();
		}

		if ( ! $this->template ) {
			throw MailException::noTemplate();
		}

		$result = wp_mail(
			$this->to,
			$this->buildSubject(),
			$this->render(),
			$this->buildHeaders(),
			$this->attachments
		);

		if ( ! $result ) {
			throw MailException::sendFailed();
		}

		return true;
	}

	/**
	 * Queue the email for later sending.
	 *
	 * Note: This currently sends immediately. Future versions will
	 * integrate with a queue system.
	 *
	 * @param string|null $queue Optional queue name.
	 *
	 * @return bool The result of the send operation.
	 * @throws \WPJarvis\Framework\Exceptions\Mail\MailException|\Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function queue( ?string $queue = null ): bool {
		// This would integrate with the Queue system
		// For now, just send immediately
		return $this->send();
	}

	/**
	 * Get the recipients.
	 *
	 * @return array<string> The array of recipient email addresses.
	 */
	public function getTo(): array {
		return $this->to;
	}

	/**
	 * Get the subject.
	 *
	 * @return string The email subject.
	 */
	public function getSubject(): string {
		return $this->subject;
	}
}
