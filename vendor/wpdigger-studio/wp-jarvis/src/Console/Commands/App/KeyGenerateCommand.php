<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\App;

use Illuminate\Console\Command;

/**
 * Key Generate Command
 *
 * Generates an application key.
 *
 * @package WPJarvis\Framework\Console\Commands
 */
class KeyGenerateCommand extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'key:generate {--show : Display the key instead of modifying files}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a new application key';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 * @throws \Random\RandomException
	 */
	public function handle(): int {
		$key = $this->generateRandomKey();

		if ( $this->option( 'show' ) ) {
			$this->line( '<comment>' . $key . '</comment>' );

			return 0;
		}

		$envFile = $this->laravel->basePath( '.env' );

		if ( ! file_exists( $envFile ) ) {
			$this->error( '.env file not found.' );

			return 1;
		}

		$content = file_get_contents( $envFile );

		if ( preg_match( '/^APP_KEY=/m', $content ) ) {
			$content = preg_replace( '/^APP_KEY=.*/m', 'APP_KEY=' . $key, $content );
		} else {
			$content .= "\nAPP_KEY=" . $key;
		}

		file_put_contents( $envFile, $content );

		$this->info( 'Application key set successfully.' );

		return 0;
	}

	/**
	 * Generate a random key.
	 *
	 * @return string
	 * @throws \Random\RandomException
	 */
	protected function generateRandomKey(): string {
		return 'base64:' . base64_encode( random_bytes( 32 ) );
	}
}
