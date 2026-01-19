<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\Cache;

/**
 * Config Cache Command
 *
 * Compiles configuration files into a single cached file for production.
 *
 * @package WPJarvis\Framework\Console\Commands\Cache
 */
class ConfigCacheCommand extends BaseCacheCommand {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'config:cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a cache file for faster configuration loading';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int {
		$this->displayCachingHeader( 'Configuration' );

		// Ensure the cache directory exists
		if ( ! $this->ensureCacheDirectoryExists() ) {
			return self::FAILURE;
		}

		try {
			// Load all configuration files
			$config = $this->loadConfigFiles();

			if ( empty( $config ) ) {
				$this->warn( 'No configuration files found to cache.' );

				return self::FAILURE;
			}


			// Display each cached config file
			foreach ( array_keys( $config ) as $name ) {
				$this->line( "  <info>✓</info> {$name}" );
			}

			// Export to a cache file
			$cachePath = $this->laravel->getCachedConfigPath();

			if ( ! $this->exportCache( $cachePath, $config, 'Configuration' ) ) {
				return self::FAILURE;
			}

			// Display a success message
			$this->displayCacheSuccess( $cachePath, count( $config ), 'configuration file(s)' );

			// Warn if in development
			$this->warnIfDevelopment();

			return self::SUCCESS;
		} catch ( \Throwable $e ) {
			$this->error( 'Configuration caching failed: ' . $e->getMessage() );

			if ( $this->isVeryVerbose() ) {
				$this->error( $e->getTraceAsString() );
			}

			return self::FAILURE;
		}
	}

	/**
	 * Load all configuration files.
	 *
	 * @return array<string, mixed>
	 */
	private function loadConfigFiles(): array {
		$config     = [];
		$configPath = $this->laravel->configPath();

		if ( ! is_dir( $configPath ) ) {
			return $config;
		}

		$files = glob( $configPath . '/*.php' );

		if ( $files === false ) {
			return $config;
		}

		foreach ( $files as $file ) {
			$key            = basename( $file, '.php' );
			$config[ $key ] = require $file;
		}

		return $config;
	}

	/**
	 * Check if the output is very verbose.
	 *
	 * @return bool True if very verbose mode is enabled.
	 */
	private function isVeryVerbose(): bool {
		return $this->getOutput()->getVerbosity() >= 2;
	}
}
