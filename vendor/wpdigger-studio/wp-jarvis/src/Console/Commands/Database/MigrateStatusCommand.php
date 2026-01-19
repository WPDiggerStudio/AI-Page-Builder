<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use Illuminate\Console\Command;
use WPJarvis\Framework\Database\MigrationRepository;
use WPJarvis\Framework\Database\Migrator;

/**
 * MigrateStatus Command
 *
 * Shows status of all migrations.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class MigrateStatusCommand extends Command {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'migrate:status
		{--path= : The path to migration files}
		{--pending : Show only pending migrations}
		{--ran : Show only ran migrations}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Show status of all migrations';

	/**
	 * Execute console command.
	 *
	 * @return int Command exit code.
	 */
	public function handle(): int {
		$customPath = $this->option('path');
		$showPending = (bool) $this->option('pending');
		$showRan = (bool) $this->option('ran');

		$this->info('Migration status:');
		$this->newLine();

		$migrator = $this->getMigrator($customPath);
		$result = $migrator->status();

		if (empty($result['ran']) && empty($result['pending'])) {
			$this->warn('No migrations found.');

			return self::SUCCESS;
		}

		// Display ran migrations
		if (!$showPending && !empty($result['ran'])) {
			$this->info('Ran migrations:');
			foreach ($result['ran'] as $migration) {
				$this->line("  <info>✓</info> {$migration}");

				if ($this->isVeryVerbose()) {
					$this->line("    <comment>Path:</comment> {$this->getMigrationPath($customPath)}/{$migration}.php");
				}
			}
		}

		// Display pending migrations
		if (!$showRan && !empty($result['pending'])) {
			if (!$showPending && !empty($result['ran'])) {
				$this->newLine();
			}

			$this->info('Pending migrations:');
			foreach ($result['pending'] as $migration) {
				$this->line("  <comment>•</comment> {$migration}");

				if ($this->isVeryVerbose()) {
					$this->line("    <comment>Path:</comment> {$this->getMigrationPath($customPath)}/{$migration}.php");
				}
			}
		}

		// Display summary
		$this->newLine();

		$total = count($result['ran']) + count($result['pending']);
		$ranCount = count($result['ran']);
		$pendingCount = count($result['pending']);

		if ($showPending) {
			$this->info(sprintf(
				'<comment>%d</comment> pending migration(s) found.',
				$pendingCount
			));
		} elseif ($showRan) {
			$this->info(sprintf(
				'<info>%d</info> migration(s) ran.',
				$ranCount
			));
		} else {
			$this->info(sprintf(
				'<comment>%d</comment> migration(s) found, <info>%d</info> ran, <comment>%d</comment> pending.',
				$total,
				$ranCount,
				$pendingCount
			));
		}

		// Display warning if there are pending migrations
		if ($pendingCount > 0 && !$showRan) {
			$this->newLine();
			$this->warn('Run <info>php wp-jarvis migrate</info> to run pending migrations.');
		}

		return self::SUCCESS;
	}

	/**
	 * Get the migrator instance.
	 *
	 * @param string|null $customPath Optional custom migration path.
	 *
	 * @return Migrator The migrator instance.
	 */
	private function getMigrator(?string $customPath = null): Migrator {
		$migrator = new Migrator($this->getRepository());
		$migrator->path($this->getMigrationPath($customPath));

		return $migrator;
	}

	/**
	 * Get the migration repository.
	 *
	 * @return MigrationRepository The migration repository.
	 */
	private function getRepository(): MigrationRepository {
		return new MigrationRepository();
	}

	/**
	 * Get the migration path.
	 *
	 * @param string|null $customPath Optional custom migration path.
	 *
	 * @return string The migration path.
	 */
	private function getMigrationPath(?string $customPath = null): string {
		if ($customPath !== null) {
			return $customPath;
		}

		return $this->laravel->basePath('database/migrations');
	}
}
