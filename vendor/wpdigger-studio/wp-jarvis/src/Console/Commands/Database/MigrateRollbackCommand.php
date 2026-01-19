<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use Illuminate\Console\Command;
use WPJarvis\Framework\Database\MigrationRepository;
use WPJarvis\Framework\Database\Migrator;

/**
 * MigrateRollback Command
 *
 * Rolls back last batch of database migrations.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class MigrateRollbackCommand extends Command {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'migrate:rollback
		{--force : Force rollback in production}
		{--step=1 : Number of batches to rollback (default: 1)}
		{--pretend : Dump the SQL queries that would be run}
		{--path= : The path to migration files}
		{--dry-run : Show what would be rolled back without actually running}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Rollback last batch of database migrations';

	/**
	 * Execute console command.
	 *
	 * @return int Command exit code.
	 */
	public function handle(): int {
		$force = (bool) $this->option('force');
		$step = (int) $this->option('step') ?: 1;
		$pretend = (bool) $this->option('pretend');
		$customPath = $this->option('path');
		$dryRun = (bool) $this->option('dry-run');

		// Validate step option
		if ($step < 1) {
			$this->error('Step must be at least 1.');

			return self::FAILURE;
		}

		// Check environment safeguards
		if (!$force && $this->isProduction()) {
			$this->error('Cannot rollback migrations in production environment. Use --force flag to override.');

			return self::FAILURE;
		}

		if ($dryRun) {
			$this->warn('Dry-run mode: Showing what would be rolled back without actually running...');
			$this->newLine();
		}

		if ($pretend) {
			$this->warn('Pretend mode: SQL queries will be dumped instead of executed.');
			$this->newLine();
		}

		$this->info('Rolling back database migrations...');

		try {
			$migrator = $this->getMigrator($customPath);

			if ($dryRun) {
				$result = $migrator->status();
				$this->displayDryRunStatus($result, $step);

				return self::SUCCESS;
			}

			if ($pretend) {
				$this->warn('Pretend mode is not yet fully implemented for rollbacks.');
				$this->warn('Use --dry-run to see what would be rolled back instead.');

				return self::FAILURE;
			}

			$result = $migrator->rollback($step, $force);

			if (!empty($result['rolled_back'])) {
				foreach ($result['rolled_back'] as $migration) {
					$this->line("<info>✓</info> Rolled back: {$migration}");

					if ($this->isVeryVerbose()) {
						$this->line("  <comment>Path:</comment> {$this->getMigrationPath($customPath)}/{$migration}.php");
					}
				}
			} else {
				$this->warn('Nothing to rollback.');
			}

			if (!empty($result['remaining'])) {
				$this->newLine();
				$this->warn(count($result['remaining']) . ' migrations still remaining.');
				foreach ($result['remaining'] as $migration) {
					$this->line("<comment>•</comment> {$migration}");
				}
			}

			$this->newLine();
			$this->info(sprintf(
				'<info>%d</info> migration(s) rolled back successfully.',
				count($result['rolled_back'])
			));

			return self::SUCCESS;
		} catch (\Throwable $e) {
			$this->error("Rollback failed: {$e->getMessage()}");

			if ($this->isVeryVerbose()) {
				$this->error($e->getTraceAsString());
			}

			return self::FAILURE;
		}
	}

	/**
	 * Display dry-run status for rollback.
	 *
	 * @param array<string, array<string>> $result The migration status result.
	 * @param int $step Number of batches to rollback.
	 *
	 * @return void
	 */
	private function displayDryRunStatus(array $result, int $step): void {
		if (empty($result['ran'])) {
			$this->warn('No migrations have been run yet. Nothing to rollback.');

			return;
		}

		$this->info('Previously ran migrations:');
		foreach ($result['ran'] as $migration) {
			$this->line("  <info>✓</info> {$migration}");
		}

		$this->newLine();
		$this->warn(sprintf(
			'Would rollback last <info>%d</info> batch(es) of migrations.',
			$step
		));

		$this->newLine();
		$this->warn('This action cannot be undone without re-running migrations.');
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

	/**
	 * Check if application is in production environment.
	 *
	 * @return bool True if in production.
	 */
	private function isProduction(): bool {
		$environment = $this->laravel->environment();

		return in_array($environment, ['production', 'prod'], true);
	}
}
