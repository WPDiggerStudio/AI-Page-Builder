<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use Illuminate\Console\Command;
use WPJarvis\Framework\Database\MigrationRepository;
use WPJarvis\Framework\Database\Migrator;

/**
 * Migrate Command
 *
 * Handles database migration operations.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class MigrateCommand extends Command {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'migrate
		{--force : Force migration in production}
		{--step : Run migrations one at a time}
		{--pretend : Dump the SQL queries that would be run}
		{--path= : The path to migration files}
		{--dry-run : Show what would be migrated without actually running}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run database migrations';

	/**
	 * Execute console command.
	 *
	 * @return int Command exit code.
	 */
	public function handle(): int {
		$force = (bool) $this->option('force');
		$step = (bool) $this->option('step');
		$pretend = (bool) $this->option('pretend');
		$customPath = $this->option('path');
		$dryRun = (bool) $this->option('dry-run');

		// Check environment safeguards
		if (!$force && $this->isProduction()) {
			$this->error('Cannot run migrations in production environment. Use --force flag to override.');

			return self::FAILURE;
		}

		if ($dryRun) {
			$this->warn('Dry-run mode: Showing what would be migrated without actually running...');
			$this->newLine();
		}

		if ($pretend) {
			$this->warn('Pretend mode: SQL queries will be dumped instead of executed.');
			$this->newLine();
		}

		$this->info('Running database migrations...');

		try {
			$migrator = $this->getMigrator($customPath);

			if ($dryRun) {
				$result = $migrator->status();
				$this->displayDryRunStatus($result);

				return self::SUCCESS;
			}

			if ($pretend) {
				$this->warn('Pretend mode is not yet fully implemented for migrations.');
				$this->warn('Use --dry-run to see what would be migrated instead.');

				return self::FAILURE;
			}

			$result = $migrator->run($step, $force);

			if (!empty($result['ran'])) {
				foreach ($result['ran'] as $migration) {
					$this->line("<info>✓</info> {$migration}");

					if ($this->isVeryVerbose()) {
						$this->line("  <comment>Path:</comment> {$this->getMigrationPath($customPath)}/{$migration}.php");
					}
				}
			}

			if (!empty($result['pending'])) {
				$this->newLine();
				$this->warn(count($result['pending']) . ' migrations still pending.');
				foreach ($result['pending'] as $migration) {
					$this->line("<comment>•</comment> {$migration}");
				}
			}

			$this->newLine();
			$this->info(sprintf(
				'<info>%d</info> migration(s) executed successfully.',
				count($result['ran'])
			));

			return self::SUCCESS;
		} catch (\Throwable $e) {
			$this->error("Migration failed: {$e->getMessage()}");

			if ($this->isVeryVerbose()) {
				$this->error($e->getTraceAsString());
			}

			return self::FAILURE;
		}
	}

	/**
	 * Display dry-run status.
	 *
	 * @param array<string, array<string>> $result The migration status result.
	 *
	 * @return void
	 */
	private function displayDryRunStatus(array $result): void {
		if (empty($result['ran']) && empty($result['pending'])) {
			$this->warn('No migrations found.');

			return;
		}

		if (!empty($result['ran'])) {
			$this->info('Already ran migrations (will be skipped):');
			foreach ($result['ran'] as $migration) {
				$this->line("  <info>✓</info> {$migration}");
			}
		}

		if (!empty($result['pending'])) {
			$this->newLine();
			$this->info('Pending migrations (would be executed):');
			foreach ($result['pending'] as $migration) {
				$this->line("  <comment>→</comment> {$migration}");
			}
		}

		$this->newLine();
		$this->info(sprintf(
			'<info>%d</info> migration(s) would be executed.',
			count($result['pending'])
		));
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
