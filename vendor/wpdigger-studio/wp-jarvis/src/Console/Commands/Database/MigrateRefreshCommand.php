<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use Illuminate\Console\Command;
use WPJarvis\Framework\Database\MigrationRepository;
use WPJarvis\Framework\Database\Migrator;

/**
 * MigrateRefresh Command
 *
 * Rolls back and re-runs all migrations.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class MigrateRefreshCommand extends Command {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'migrate:refresh
		{--force : Force refresh in production}
		{--step : Run migrations one at a time}
		{--path= : The path to migration files}
		{--seed : Run seeders after refresh}
		{--seeder= : The seeder class to run}
		{--dry-run : Show what would be refreshed without actually running}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Rollback and re-run all migrations';

	/**
	 * Execute console command.
	 *
	 * @return int Command exit code.
	 */
	public function handle(): int {
		$force = (bool) $this->option('force');
		$step = (bool) $this->option('step');
		$customPath = $this->option('path');
		$seed = (bool) $this->option('seed');
		$seeder = $this->option('seeder');
		$dryRun = (bool) $this->option('dry-run');

		// Check environment safeguards
		if (!$force && $this->isProduction()) {
			$this->error('Cannot refresh migrations in production environment. Use --force flag to override.');

			return self::FAILURE;
		}

		if ($dryRun) {
			$this->warn('Dry-run mode: Showing what would be refreshed without actually running...');
			$this->newLine();

			$migrator = $this->getMigrator($customPath);
			$result = $migrator->status();

			$this->info('Current migration status:');
			$this->newLine();

			if (!empty($result['ran'])) {
				$this->info('Migrations that would be rolled back:');
				foreach ($result['ran'] as $migration) {
					$this->line("  <info>↓</info> {$migration}");
				}
			}

			if (!empty($result['pending'])) {
				$this->newLine();
				$this->info('Migrations that would be run:');
				foreach ($result['pending'] as $migration) {
					$this->line("  <comment>↑</comment> {$migration}");
				}
			}

			if ($seed) {
				$this->newLine();
				$this->info('Seeders that would be run:');
				if ($seeder) {
					$this->line("  <comment>→</comment> {$seeder}");
				} else {
					$this->line("  <comment>→</comment> All seeders");
				}
			}

			$this->newLine();
			$this->warn('This is a destructive operation. All data will be lost.');

			return self::SUCCESS;
		}

		$this->warn('This will rollback all migrations and re-run them!');
		$this->warn('This is a destructive operation. All data will be lost.');
		$confirmed = $this->confirm('Do you really wish to continue?', false);

		if (!$confirmed) {
			$this->warn('Migration refresh cancelled.');

			return self::FAILURE;
		}

		$this->newLine();
		$this->info('Rolling back migrations...');

		try {
			$migrator = $this->getMigrator($customPath);
			$result = $migrator->rollback(PHP_INT_MAX, $force);

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

			$this->newLine();
			$this->info('Running migrations...');

			$result = $migrator->run($step, $force);

			if (!empty($result['ran'])) {
				foreach ($result['ran'] as $migration) {
					$this->line("<info>✓</info> {$migration}");

					if ($this->isVeryVerbose()) {
						$this->line("  <comment>Path:</comment> {$this->getMigrationPath($customPath)}/{$migration}.php");
					}
				}
			} else {
				$this->warn('No migrations to run.');
			}

			// Run seeders if requested
			if ($seed) {
				$this->newLine();
				$this->info('Running seeders...');

				$seederClass = $seeder ?: null;
				$seeders = $this->getSeeders($seederClass);

				foreach ($seeders as $seederName) {
					$this->runSeeder($seederName);
				}
			}

			$this->newLine();
			$this->info('Migration refresh completed successfully.');

			return self::SUCCESS;
		} catch (\Throwable $e) {
			$this->error("Migration refresh failed: {$e->getMessage()}");

			if ($this->isVeryVerbose()) {
				$this->error($e->getTraceAsString());
			}

			return self::FAILURE;
		}
	}

	/**
	 * Get all seeder classes.
	 *
	 * @param string|null $specificClass Run a specific seeder class.
	 *
	 * @return array<string> Seeder class names.
	 */
	private function getSeeders(?string $specificClass): array {
		if ($specificClass !== null) {
			return [$specificClass];
		}

		$seeders = [];
		$seederPath = $this->getSeederPath();

		if (!is_dir($seederPath)) {
			$this->warn("Seeder path does not exist: {$seederPath}");

			return $seeders;
		}

		$files = scandir($seederPath);

		foreach ($files as $file) {
			if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
				continue;
			}

			if ($file === '.' || $file === '..') {
				continue;
			}

			$seeders[] = pathinfo($file, PATHINFO_FILENAME);
		}

		// Sort seeders by name
		sort($seeders);

		return $seeders;
	}

	/**
	 * Run a seeder.
	 *
	 * @param string $seederClass The seeder class name.
	 *
	 * @return void
	 */
	private function runSeeder(string $seederClass): void {
		$fullClass = $this->resolveSeederClass($seederClass);

		if (!class_exists($fullClass)) {
			$this->warn("Seeder {$seederClass} not found.");

			return;
		}

		$seeder = new $fullClass();

		if (!$seeder instanceof \WPJarvis\Framework\Database\Seeder) {
			$this->warn("Class {$seederClass} is not a valid seeder.");

			return;
		}

		$this->line("<info>Running seeder:</info> {$seederClass}...");

		$seeder->run();

		$this->line("<info>✓</info> Seeder {$seederClass} completed.");
	}

	/**
	 * Resolve the full seeder class name.
	 *
	 * @param string $seederClass The seeder class name.
	 *
	 * @return string The fully qualified class name.
	 */
	private function resolveSeederClass(string $seederClass): string {
		// If class already has namespace, return as-is
		if (str_contains($seederClass, '\\')) {
			return $seederClass;
		}

		return 'Database\\Seeders\\' . $seederClass;
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
	 * Get the seeder path.
	 *
	 * @return string The seeder path.
	 */
	private function getSeederPath(): string {
		return $this->laravel->basePath('database/seeders');
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
