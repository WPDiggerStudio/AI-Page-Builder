<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Contracts\Mail\Mailer as MailerContract;
use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Mail\Mailable;
use WPJarvis\Framework\WP\Mail\Mailer;

/**
 * MailServiceProvider
 *
 * Registers mail services for the framework.
 */
class MailServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 */
	protected bool $defer = true;

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->app->singleton( 'mailer', function ( $app ) {
			$config = $app->make( 'config' );

			return new Mailer(
				$config->get( 'mail.from.address', get_option( 'admin_email' ) ),
				$config->get( 'mail.from.name', get_bloginfo( 'name' ) )
			);
		} );

		$this->app->alias( 'mailer', Mailer::class );
		$this->app->alias( 'mailer', MailerContract::class );
	}

	/**
	 * Bootstrap the service provider.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void {
		// Register the mail sending hook for queued emails
		add_action( 'WpJarvis_send_mail', [ $this, 'handleQueuedMail' ] );

		// Configure WordPress mail settings
		$this->configureWordPressMail();
	}

	/**
	 * Handle queued mail.
	 *
	 * @param string $serializedMailable Serialized mailable.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function handleQueuedMail( string $serializedMailable ): void {
		try {
			$mailable = unserialize( $serializedMailable, [ 'allowed_classes' => [ Mailable::class ] ] );
			if ( $mailable ) {
				$mailable->send();
			}
		} catch ( \Throwable $e ) {
			wpj_logger()?->error(
				'[WP Jarvis Mail] Failed to send queued email: ' . $e->getMessage(),
				[ 'exception' => $e ]
			);
		}
	}

	/**
	 * Configure WordPress mail settings.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function configureWordPressMail(): void {
		$config = $this->app->make( 'config' );

		// Set from email
		Hooks::filter( 'wp_mail_from', static function ( $email ) use ( $config ) {
			return $config->get( 'mail.from.address', $email );
		} );

		// Set from name
		Hooks::filter( 'wp_mail_from_name', static function ( $name ) use ( $config ) {
			return $config->get( 'mail.from.name', $name );
		} );

		// Configure SMTP if settings exist
		if ( $config->get( 'mail.driver' ) === 'smtp' ) {
			add_action( 'phpmailer_init', static function ( $phpmailer ) use ( $config ) {
				$phpmailer->isSMTP();
				$phpmailer->Host       = $config->get( 'mail.host' );
				$phpmailer->Port       = $config->get( 'mail.port', 587 );
				$phpmailer->SMTPAuth   = true;
				$phpmailer->Username   = $config->get( 'mail.username' );
				$phpmailer->Password   = $config->get( 'mail.password' );
				$phpmailer->SMTPSecure = $config->get( 'mail.encryption', 'tls' );
			} );
		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array<string>
	 */
	public function provides(): array {
		return [
			'mailer',
			Mailer::class,
			MailerContract::class,
		];
	}
}
