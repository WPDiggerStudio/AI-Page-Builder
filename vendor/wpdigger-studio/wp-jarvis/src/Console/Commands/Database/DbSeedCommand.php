<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WPJarvis\Framework\Database\Seeder;

/**
 * DbSeed Command
 *
 * Runs database seeders to populate database with sample data.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class DbSeedCommand extends Command {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'db:seed
		{--class= : Run a specific seeder class}
		{--force : Force seeding in production}
		{--path= : The path to the seeder files}
		{--pretend : Dump the SQL queries that would be run}
		{--dry-run : Show what would be done without actually running seeders}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run database seeders';

	/**
	 * Execute console command.
	 *
	 * @return int Command exit code.
	 */
	public function handle(): int {
		$specificClass = $this->option('class');
		$force = (bool) $this->option('force');
		$customPath = $this->option('path');
		$pretend = (bool) $this->option('pretend');
		$dryRun = (bool) $this->option('dry-run');

		// Check environment safeguards
		if (!$force && $this->isProduction()) {
			$this->error('Cannot run seeders in production environment. Use --force flag to override.');

			return self::FAILURE;
		}

		// Set custom path if provided
		if ($customPath !== null) {
			$this->seederPath = $customPath;
		}

		if ($dryRun) {
			$this->warn('Dry-run mode: Showing what would be seeded without actually running...');
			$this->newLine();
		}

		if ($pretend) {
			$this->warn('Pretend mode: SQL queries will be dumped instead of executed.');
			$this->newLine();
		}

		$this->info('Running database seeders...');

		try {
			$seeders = $this->getSeeders($specificClass);

			if (empty($seeders)) {
				$this->warn('No seeders found.');

				return self::SUCCESS;
			}

			foreach ($seeders as $seeder) {
				$this->runSeeder($seeder, $dryRun, $pretend);
			}

			$this->newLine();
			$this->info('Seeding completed successfully.');

			return self::SUCCESS;
		} catch (\Throwable $e) {
			$this->error("Seeding failed: {$e->getMessage()}");

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
			// Validate specific seeder exists
			$fullClass = $this->resolveSeederClass($specificClass);
			if (!class_exists($fullClass)) {
				$this->error("Seeder class '{$specificClass}' not found.");

				return [];
			}

			return [$specificClass];
		}

		$seeders = [];
		$seederPath = $this->getSeederPath();

		if (!File::exists($seederPath)) {
			$this->warn("Seeder path does not exist: {$seederPath}");

			return $seeders;
		}

		$files = File::files($seederPath);

		foreach ($files as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}

			$seeders[] = $file->getBasename('.php');
		}

		// Sort seeders by name
		sort($seeders);

		return $seeders;
	}

	/**
	 * Run a seeder.
	 *
	 * @param string $seederClass The seeder class name.
	 * @param bool $dryRun Whether to run in dry-run mode.
	 * @param bool $pretend Whether to run in pretend mode.
	 *
	 * @return void
	 */
	private function runSeeder(string $seederClass, bool $dryRun, bool $pretend): void {
		$fullClass = $this->resolveSeederClass($seederClass);

		if (!class_exists($fullClass)) {
			$this->warn("Seeder {$seederClass} not found.");

			return;
		}

		$seeder = new $fullClass();

		if (!$seeder instanceof Seeder) {
			$this->warn("Class {$seederClass} is not a valid seeder.");

			return;
		}

		$this->line("<info>Running seeder:</info> {$seederClass}...");

		if ($dryRun) {
			$this->line("  <comment>Would seed:</comment> {$fullClass}");
			$this->line("  <comment>Would call:</comment> {$fullClass}::run()");

			return;
		}

		if ($pretend) {
			$this->line("  <comment>Would execute:</comment> {$fullClass}::run()");
			$this->line("  <comment>SQL queries would be dumped.</comment>");

			return;
		}

		$startTime = microtime(true);

		$seeder->run();

		$elapsed = round(microtime(true) - $startTime, 3);

		$this->line("<info>✓</info> Seeder {$seederClass} completed. <comment>({$elapsed}s)</comment>");

		if ($this->isVerbose()) {
			$this->line("  <comment>Class:</comment> {$fullClass}");
		}
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
