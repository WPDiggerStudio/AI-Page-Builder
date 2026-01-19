<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\Cache;

/**
 * Route Cache Command
 *
 * Caches route definitions for faster route registration in production.
 *
 * @package WPJarvis\Framework\Console\Commands\Cache
 */
class RouteCacheCommand extends BaseCacheCommand {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'route:cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a route cache file for faster route registration';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int {
		$this->displayCachingHeader( 'Route' );

		// Ensure cache directory exists
		if ( ! $this->ensureCacheDirectoryExists() ) {
			return self::FAILURE;
		}

		try {
			// Discover route files
			$routes = $this->discoverRouteFiles();

			if ( empty( $routes ) ) {
				$this->warn( 'No route files found to cache.' );

				return self::FAILURE;
			}

			// Display each route file
			foreach ( $routes as $routeFile ) {
				$relativePath = str_replace(
					$this->laravel->basePath() . DIRECTORY_SEPARATOR,
					'',
					$routeFile
				);
				$this->line( "  <info>✓</info> {$relativePath}" );
			}

			// Create cache data
			$cacheData = [
				'files'     => $routes,
				'timestamp' => time(),
			];

			// Export to cache file
			$cachePath = $this->laravel->getCachedRoutesPath();

			if ( ! $this->exportCache( $cachePath, $cacheData, 'Routes' ) ) {
				return self::FAILURE;
			}

			// Display success message
			$this->displayCacheSuccess( $cachePath, count( $routes ), 'route file(s)' );

			// Warn if in development
			$this->warnIfDevelopment();

			return self::SUCCESS;
		} catch ( \Throwable $e ) {
			$this->error( 'Route caching failed: ' . $e->getMessage() );

			if ( $this->isVeryVerbose() ) {
				$this->error( $e->getTraceAsString() );
			}

			return self::FAILURE;
		}
	}

	/**
	 * Discover all route files.
	 *
	 * @return array<string>
	 */
	private function discoverRouteFiles(): array {
		$routes     = [];
		$routesPath = $this->laravel->routesPath();

		// Check for common route files
		$routeFiles = [ 'api.php', 'admin.php', 'web.php' ];

		foreach ( $routeFiles as $file ) {
			$filePath = $routesPath . DIRECTORY_SEPARATOR . $file;

			if ( file_exists( $filePath ) ) {
				$routes[] = $filePath;
			}
		}

		return $routes;
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