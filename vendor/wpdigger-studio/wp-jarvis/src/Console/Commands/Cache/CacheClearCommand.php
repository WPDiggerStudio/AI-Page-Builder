<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Cache;

use Illuminate\Console\Command;

/**
 * Cache Clear Command
 *
 * Clears application caches with Laravel-style granular control.
 *
 * @package WPJarvis\Framework\Console\Commands
 */
class CacheClearCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:clear
		{--view : Clear compiled view cache}
		{--route : Clear routes cache}
		{--config : Clear configuration cache}
		{--event : Clear events cache}
		{--transients : Clear WordPress transients}
		{--object : Clear WordPress object cache}
		{--all : Clear all cache types (default)}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Clear the application cache';

	/**
	 * Cache operation statistics.
	 *
	 * @vararray<string, int>
	 */
	private array $stats = [
		'processed' => 0,
		'cleared' => 0,
		'skipped' => 0,
		'failed' => 0,
	];

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int
	{
		$this->displayHeader();

		try {
			$this->clearCaches();
			$this->displaySummary();

			return self::SUCCESS;
		} catch (\Throwable $e) {
			$this->newLine();
			$this->error('Cache clearing failed: ' . $e->getMessage());

			if ($this->isVeryVerbose()) {
				$this->error($e->getTraceAsString());
			}

			return self::FAILURE;
		}
	}

	/**
	 * Display command header.
	 *
	 * @return void
	 */
	private function displayHeader(): void
	{
		$this->info('Cache Management');
		$this->info('================');
		$this->newLine();
		$this->info('Clearing application caches...');
		$this->newLine();
	}

	/**
	 * Clear all selected cache types.
	 *
	 * @return void
	 */
	private function clearCaches(): void
	{
		// Determine which caches to clear
		$clearAll = $this->shouldClearAll();

		if ($clearAll || $this->option('config')) {
			$this->processCacheType('Configuration', fn() => $this->clearConfigCache());
		}

		if ($clearAll || $this->option('route')) {
			$this->processCacheType('Routes', fn() => $this->clearRouteCache());
		}

		if ($clearAll || $this->option('event')) {
			$this->processCacheType('Events', fn() => $this->clearEventCache());
		}

		if ($clearAll || $this->option('view')) {
			$this->processCacheType('View', fn() => $this->clearViewCache());
		}

		if ($clearAll || $this->option('transients')) {
			$this->processCacheType('Transients', fn() => $this->clearTransients());
		}

		if ($clearAll || $this->option('object')) {
			$this->processCacheType('Object', fn() => $this->clearObjectCache());
		}
	}

	/**
	 * Determine if all caches should be cleared.
	 *
	 * @return bool
	 */
	private function shouldClearAll(): bool
	{
		// If --all is specified, clear all
		if ($this->option('all')) {
			return true;
		}

		// If no specific options are provided, default to all
		return !(
			$this->option('config') ||
			$this->option('route') ||
			$this->option('event') ||
			$this->option('view') ||
			$this->option('transients') ||
			$this->option('object')
		);
	}

	/**
	 * Process a cache type clearing operation.
	 *
	 * @param string $type The cache type name.
	 * @param callable $callback The clearing callback.
	 *
	 * @return void
	 */
	private function processCacheType(string $type, callable $callback): void
	{
		$this->stats['processed']++;

		try {
			$result = $callback();

			if ($result === null) {
				// Cache type not configured
				$this->displayCacheResult($type, 'skipped', 'not configured');
				$this->stats['skipped']++;
			} elseif ($result === false) {
				// Clearing failed
				$this->displayCacheResult($type, 'failed', 'operation failed');
				$this->stats['failed']++;
			} else {
				// Success
				$details = is_array($result) ? $result['message'] ?? '' : $result;
				$this->displayCacheResult($type, 'success', $details);
				$this->stats['cleared']++;
			}
		} catch (\Throwable $e) {
			$this->displayCacheResult($type, 'failed', $e->getMessage());
			$this->stats['failed']++;
		}
	}

	/**
	 * Display the result of a cache clearing operation.
	 *
	 * @param string $type Cache type name.
	 * @param string $status Status: 'success', 'skipped', or 'failed'.
	 * @param string $details Optional details message.
	 *
	 * @return void
	 */
	private function displayCacheResult(string $type, string $status, string $details = ''): void
	{
		$icon = match ($status) {
			'success' => '<info>✓</info>',
			'skipped' => '<comment>○</comment>',
			'failed' => '<error>✗</error>',
			default => '<comment>•</comment>',
		};
		$message = "{$type} cache";

		if ($status === 'skipped') {
			$message .= ' <comment>(skipped' . ($details ? ": {$details}" : '') . ')</comment>';
		} elseif ($status === 'failed') {
			$message .= ' <error>(failed' . ($details ? ": {$details}" : '') . ')</error>';
		} elseif ($details) {
			$message .= " <comment>({$details})</comment>";
		}

		$this->line("  {$icon} {$message}");
	}

	/**
	 * Display summary of cache clearing operations.
	 *
	 * @return void
	 */
	private function displaySummary(): void
	{
		$this->newLine();
		$this->info('Summary');
		$this->info('-------');

		$this->info(sprintf(
			'<comment>%d</comment> cache type(s) processed, <info>%d</info> cleared, <comment>%d</comment> skipped, <error>%d</error> failed',
			$this->stats['processed'],
			$this->stats['cleared'],
			$this->stats['skipped'],
			$this->stats['failed']
		));

		$this->newLine();

		if ($this->stats['failed'] > 0) {
			$this->warn('Some cache types failed to clear. See errors above.');
		} elseif ($this->stats['cleared'] > 0) {
			$this->info('Cache cleared successfully!');
		} else {
			$this->warn('No caches were cleared.');
		}
	}

	/**
	 * Clear configuration cache.
	 *
	 * @return string|null Returns message on success, null if not configured.
	 */
	protected function clearConfigCache(): ?string
	{
		$cachePath = $this->laravel->getCachedConfigPath();

		if (!file_exists($cachePath)) {
			return null; // Not configured
		}

		$deleted = unlink($cachePath);

		if (!$deleted || file_exists($cachePath)) {
			return false; // Failed
		}

		return 'cleared';
	}

	/**
	 * Clear routes cache.
	 *
	 * @return string|null Returns message on success, null if not configured.
	 */
	protected function clearRouteCache(): ?string
	{
		$cachePath = $this->laravel->getCachedRoutesPath();

		if (!file_exists($cachePath)) {
			return null; // Not configured
		}

		$deleted = unlink($cachePath);

		if (!$deleted || file_exists($cachePath)) {
			return false; // Failed
		}

		return 'cleared';
	}

	/**
	 * Clear events cache.
	 *
	 * @return string|null Returns message on success, null if not configured.
	 */
	protected function clearEventCache(): ?string
	{
		$cachePath = $this->laravel->getCachedEventsPath();

		if (!file_exists($cachePath)) {
			return null; // Not configured
		}

		$deleted = unlink($cachePath);

		if (!$deleted || file_exists($cachePath)) {
			return false; // Failed
		}

		return 'cleared';
	}

	/**
	 * Clear view cache.
	 *
	 * @return string|null Returns message with file count, null if not configured.
	 */
	protected function clearViewCache(): ?string
	{
		$viewCachePath = $this->laravel->storagePath('framework/views');

		if (!is_dir($viewCachePath)) {
			return null; // Not configured
		}

		$files = glob($viewCachePath . '/*');
		$deletedCount = 0;

		if ($files === false) {
			return null;
		}

		foreach ($files as $file) {
			if (is_file($file)) {
				if (unlink($file)) {
					$deletedCount++;
				}
			}
		}

		if ($deletedCount === 0) {
			return 'no files to clear';
		}

		return "{$deletedCount} file" . ($deletedCount !== 1 ? 's' : '') . ' removed';
	}

	/**
	 * Clear WordPress transients.
	 *
	 * @return string|false Returns message with entry count, false on failure.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function clearTransients()
	{
		global $wpdb;

		$prefix = $this->laravel->make('config')->get('app.slug', 'WpJarvis');

		$rowsDeleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $prefix . '%',
				'_transient_timeout_' . $prefix . '%'
			)
		);

		if ($rowsDeleted === false) {
			return false;
		}

		if ($rowsDeleted === 0) {
			return 'no entries to clear';
		}

		return "{$rowsDeleted} " . ($rowsDeleted !== 1 ? 'entries' : 'entry') . ' removed';
	}

	/**
	 * Clear WordPress object cache.
	 *
	 * @return string|null Returns message on success, null if not available.
	 */
	protected function clearObjectCache(): ?string
	{
		if (!function_exists('wp_cache_flush')) {
			return null; // Not available
		}

		$result = wp_cache_flush();

		if (!$result) {
			return false; // Failed
		}

		return 'flushed';
	}

	/**
	 * Check if the output is very verbose.
	 *
	 * @return bool True if very verbose mode is enabled.
	 */
	private function isVeryVerbose(): bool
	{
		return $this->getOutput()->getVerbosity() >= 2;
	}
}