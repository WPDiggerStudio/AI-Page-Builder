<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\Cache;

/**
 * Event Cache Command
 *
 * Caches event listener mappings for faster event registration in production.
 *
 * @package WPJarvis\Framework\Console\Commands\Cache
 */
class EventCacheCommand extends BaseCacheCommand {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'event:cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create an event cache file for faster event registration';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int {
		$this->displayCachingHeader( 'Event' );

		// Ensure cache directory exists
		if ( ! $this->ensureCacheDirectoryExists() ) {
			return self::FAILURE;
		}

		try {
			// Load event mappings from config
			$events = $this->loadEventMappings();

			$listenerCount   = $this->countListeners( $events['listen'] ?? [] );
			$subscriberCount = count( $events['subscribe'] ?? [] );
			$totalCount      = $listenerCount + $subscriberCount;

			if ( $totalCount === 0 ) {
				$this->warn( 'No event listeners or subscribers found to cache.' );

				return self::FAILURE;
			}

			// Display what was cached
			if ( $listenerCount > 0 ) {
				$this->line( sprintf(
					"  <info>✓</info> Event listeners (%d registered)",
					$listenerCount
				) );
			}

			if ( $subscriberCount > 0 ) {
				$this->line( sprintf(
					"  <info>✓</info> Event subscribers (%d registered)",
					$subscriberCount
				) );
			}

			// Export to cache file
			$cachePath = $this->laravel->getCachedEventsPath();

			if ( ! $this->exportCache( $cachePath, $events, 'Events' ) ) {
				return self::FAILURE;
			}

			// Display success message
			$this->displayCacheSuccess( $cachePath, $totalCount, 'event binding(s)' );

			// Warn if in development
			$this->warnIfDevelopment();

			return self::SUCCESS;
		} catch ( \Throwable $e ) {
			$this->error( 'Event caching failed: ' . $e->getMessage() );

			if ( $this->isVeryVerbose() ) {
				$this->error( $e->getTraceAsString() );
			}

			return self::FAILURE;
		}
	}

	/**
	 * Load event mappings from configuration.
	 *
	 * @return array<string, mixed>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function loadEventMappings(): array {
		/** @var \Illuminate\Config\Repository $config */
		$config = $this->laravel->make( 'config' );

		return [
			'listen'    => $config->get( 'events.listen', [] ),
			'subscribe' => $config->get( 'events.subscribe', [] ),
		];
	}

	/**
	 * Count the total number of listeners.
	 *
	 * @param array<string, array<string>> $listen Listener mappings.
	 *
	 * @return int Total listener count.
	 */
	private function countListeners( array $listen ): int {
		$count = 0;

		foreach ( $listen as $listeners ) {
			if ( is_array( $listeners ) ) {
				$count += count( $listeners );
			}
		}

		return $count;
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
