<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Foundation;

use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeContract;

/**
 * WordPress Maintenance Mode
 *
 * Provides maintenance mode functionality for WordPress applications.
 */
class WordPressMaintenanceMode implements MaintenanceModeContract {
	/**
	 * The application instance.
	 *
	 * @var \WPJarvis\Framework\Application
	 */
	private \WPJarvis\Framework\Application $app;

	/**
	 * Create a new maintenance mode instance.
	 *
	 * @param \WPJarvis\Framework\Application $app
	 */
	public function __construct( \WPJarvis\Framework\Application $app ) {
		$this->app = $app;
	}

	/**
	 * Take the application down for maintenance.
	 *
	 * @param array $payload
	 *
	 * @return void
	 */
	public function activate( array $payload ): void {
		// Optimization: Store everything in ONE option instead of 4
		$data = [
			'active'  => true,
			'message' => $payload['message'] ?? null, // Store null to allow dynamic translation on retrieval
			'retry'   => $payload['retry'] ?? null,
			'payload' => $payload,
			'time'    => time(),
		];

		update_option( $this->getOptionName(), $data );
	}

	/**
	 * Take the application out of maintenance.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		delete_option( $this->getOptionName() );
	}

	/**
	 * Determine if the application is currently down for maintenance.
	 *
	 * @return bool
	 */
	public function active(): bool {
		// 1. Check native WordPress maintenance file
		if ( file_exists( ABSPATH . '.maintenance' ) ) {
			return true;
		}

		// 2. Check our custom option
		$data = get_option( $this->getOptionName(), [] );

		return ! empty( $data['active'] );
	}

	/**
	 * Get the data array which was provided when the application was placed into maintenance.
	 *
	 * @return array
	 */
	public function data(): array {
		// 1. Handle native WordPress maintenance file
		if ( file_exists( ABSPATH . '.maintenance' ) ) {
			// We cannot parse the variable from the file safely without including it,
			// so we just return a generic WP structure.
			return [
				'enabled' => true,
				'message' => __( 'Briefly unavailable for scheduled maintenance. Check back in a minute.', 'wp-jarvis' ), // WP Core string
				'retry'   => 600,
			];
		}

		// 2. Handle our custom maintenance data
		$data = get_option( $this->getOptionName(), [] );

		if ( empty( $data['active'] ) ) {
			return [];
		}

		return [
			'enabled' => true,
			// Localize here: If a DB message is null, use the translation
			'message' => $data['message'] ?? __( 'Site is under maintenance. Please check back soon.', 'wp-jarvis' ),
			'retry'   => $data['retry'] ?? null,
			'payload' => $data['payload'] ?? [],
		];
	}

	/**
	 * Get the application prefix for options.
	 *
	 * @return string
	 */
	protected function getOptionName(): string {
		try {
			$slug = $this->app->bound( 'config' )
				? $this->app['config']->get( 'app.slug', 'wp-jarvis' )
				: 'wp-jarvis';
		} catch ( \Throwable $e ) {
			$slug = 'wp-jarvis';
		}

		return $slug . '_maintenance_mode';
	}
}