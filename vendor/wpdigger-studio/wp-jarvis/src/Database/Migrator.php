<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use WPJarvis\Framework\Support\Facades\Config;

/**
 * Migrator
 *
 * Handles migration discovery, execution, and rollback.
 *
 * @package WPJarvis\Framework\Database
 */
class Migrator {
	/**
	 * The migration repository instance.
	 *
	 * @var MigrationRepository
	 */
	private MigrationRepository $repository;

	/**
	 * The migration paths.
	 *
	 * @var array<string>
	 */
	private array $paths = [];

	/**
	 * Create a new Migrator instance.
	 *
	 * @param MigrationRepository|null $repository Migration repository instance.
	 */
	public function __construct( ?MigrationRepository $repository = null ) {
		$this->repository = $repository ?? new MigrationRepository();
	}

	/**
	 * Add a migration path.
	 *
	 * @param string $path The path to migration files.
	 *
	 * @return static The current instance for chaining.
	 */
	public function path( string $path ): static {
		$this->paths[] = $path;

		return $this;
	}

	/**
	 * Set multiple migration paths.
	 *
	 * @param array<string> $paths The paths to migration files.
	 *
	 * @return static The current instance for chaining.
	 */
	public function paths( array $paths ): static {
		$this->paths = array_merge( $this->paths, $paths );

		return $this;
	}

	/**
	 * Run all pending migrations.
	 *
	 * @param bool $step Whether to run migrations one at a time.
	 * @param bool $force Whether to force migration in production.
	 *
	 * @return array{ran: array<string>, pending: array<string>} Migration results.
	 * @throws \RuntimeException If trying to migrate in production without force flag.
	 */
	public function run( bool $step = false, bool $force = false ): array {
		$this->ensureMigrationTable();

		// Check environment safeguards
		if ( ! $force && $this->isProduction() ) {
			throw new \RuntimeException(
				__( 'Cannot run migrations in production environment. Use --force flag to override.', 'wp-jarvis' )
			);
		}

		$migrations = $this->getPendingMigrations();
		$ran        = [];

		if ( empty( $migrations ) ) {
			return [
				'ran'     => [],
				'pending' => [],
			];
		}

		$batch = $this->repository->getNextBatchNumber();

		foreach ( $migrations as $migration ) {
			$this->runUp( $migration, $batch );
			$ran[] = $migration;

			if ( $step ) {
				break;
			}
		}

		return [
			'ran'     => $ran,
			'pending' => array_diff( $migrations, $ran ),
		];
	}

	/**
	 * Rollback the last batch of migrations.
	 *
	 * @param int|null $steps The number of batches to rollback (null for all).
	 * @param bool $force Whether to force rollback in production.
	 *
	 * @return array{rolled_back: array<string>, remaining: array<string>} Rollback results.
	 * @throws \RuntimeException If trying to rollback in production without force flag.
	 */
	public function rollback( ?int $steps = null, bool $force = false ): array {
		$this->ensureMigrationTable();

		// Check environment safeguards
		if ( ! $force && $this->isProduction() ) {
			throw new \RuntimeException(
				__( 'Cannot rollback migrations in production environment. Use--force flag to override.', 'wp-jarvis' )
			);
		}

		$migrations = $this->getLastMigrations( $steps );
		$rolledBack = [];

		foreach ( $migrations as $migration ) {
			$this->runDown( $migration );
			$rolledBack[] = $migration;
		}

		return [
			'rolled_back' => $rolledBack,
			'remaining'   => array_diff( $migrations, $rolledBack ),
		];
	}

	/**
	 * Reset all migrations (rollback all then run all).
	 *
	 * @param bool $force Whether to force reset in production.
	 *
	 * @return array{rolled_back: array<string>, ran: array<string>} Reset results.
	 * @throws \RuntimeException If trying to reset in production without force flag.
	 */
	public function reset( bool $force = false ): array {
		$this->ensureMigrationTable();

		// Check environment safeguards
		if ( ! $force && $this->isProduction() ) {
			throw new \RuntimeException(
				__( 'Cannot reset migrations in production environment. Use --force flag to override.', 'wp-jarvis' )
			);
		}

		// Rollback all migrations
		$rollbackResult = $this->rollback( null, $force );

		// Run all migrations
		$runResult = $this->run( false, $force );

		return [
			'rolled_back' => $rollbackResult['rolled_back'],
			'ran'         => $runResult['ran'],
		];
	}

	/**
	 * Refresh migrations (rollback last batch then run).
	 *
	 * @param bool $force Whether to force refresh in production.
	 *
	 * @return array{rolled_back: array<string>, ran: array<string>} Refresh results.
	 * @throws \RuntimeException If trying to refresh in production without force flag.
	 */
	public function refresh( bool $force = false ): array {
		$this->ensureMigrationTable();

		// Check environment safeguards
		if ( ! $force && $this->isProduction() ) {
			throw new \RuntimeException(
				__( 'Cannot refresh migrations in production environment. Use --force flag to override.', 'wp-jarvis' )
			);
		}

		// Rollback last batch
		$rollbackResult = $this->rollback( 1, $force );

		// Run all migrations
		$runResult = $this->run( false, $force );

		return [
			'rolled_back' => $rollbackResult['rolled_back'],
			'ran'         => $runResult['ran'],
		];
	}

	/**
	 * Get migration status.
	 *
	 * @return array{ran: array<string>, pending: array<string>} Migration status.
	 */
	public function status(): array {
		$this->ensureMigrationTable();

		$all     = $this->getAllMigrations();
		$ran     = $this->repository->getRan();
		$pending = array_diff( $all, $ran );

		return [
			'ran'     => $ran,
			'pending' => $pending,
		];
	}

	/**
	 * Get all migration files.
	 *
	 * @return array<string> All migration file names.
	 */
	private function getAllMigrations(): array {
		$migrations = [];

		foreach ( $this->paths as $path ) {
			if ( ! File::exists( $path ) ) {
				continue;
			}

			$files = File::files( $path );

			foreach ( $files as $file ) {
				if ( $file->getExtension() !== 'php' ) {
					continue;
				}

				$migrations[] = $file->getBasename( '.php' );
			}
		}

		// Sort migrations by name
		sort( $migrations );

		return $migrations;
	}

	/**
	 * Get pending migrations.
	 *
	 * @return array<string> Pending migration names.
	 */
	private function getPendingMigrations(): array {
		$all = $this->getAllMigrations();
		$ran = $this->repository->getRan();

		return array_diff( $all, $ran );
	}

	/**
	 * Get the last migrations to rollback.
	 *
	 * @param int|null $steps The number of batches to rollback.
	 *
	 * @return array<string> Migration names to rollback.
	 */
	private function getLastMigrations( ?int $steps = null ): array {
		if ( $steps === null ) {
			return $this->repository->getRan();
		}

		$migrations = [];
		$batches    = [];

		// Get all migrations with their batches
		$allMigrations = $this->repository->getRan();
		$prefix        = DB::getTablePrefix();
		$tableName     = $prefix . 'wp_jarvis_migrations';

		$migrationData = DB::table( $tableName )
		                   ->whereIn( 'migration', $allMigrations )
		                   ->orderBy( 'batch', 'desc' )
		                   ->orderBy( 'id', 'desc' )
		                   ->get()
		                   ->groupBy( 'batch' )
		                   ->take( $steps )
		                   ->pluck( 'batch' )
		                   ->toArray();

		foreach ( $migrationData as $batch ) {
			$batchMigrations = DB::table( $tableName )
			                     ->where( 'batch', $batch )
			                     ->pluck( 'migration' )
			                     ->toArray();

			$migrations = array_merge( $migrations, $batchMigrations );
		}

		return $migrations;
	}

	/**
	 * Run a migration's up method.
	 *
	 * @param string $migration The migration name.
	 * @param int $batch The batch number.
	 *
	 * @return void
	 */
	private function runUp( string $migration, int $batch ): void {
		$instance = $this->resolveMigration( $migration );

		if ( $instance === null ) {
			echo sprintf(
			/* translators: %s: migration name */
				__( 'Migration %s not found.', 'wp-jarvis' ) . "\n",
				$migration
			);

			return;
		}

		echo sprintf(
		/* translators: %s: migration name */
			__( 'Running migration: %s...', 'wp-jarvis' ) . "\n",
			$migration
		);

		try {
			$instance->up();
			$this->repository->log( $migration, $batch );
			echo sprintf(
			/* translators: %s: migration name */
				__( 'Migration %s completed.', 'wp-jarvis' ) . "\n",
				$migration
			);
		} catch ( \Throwable $e ) {
			echo sprintf(
			/* translators: 1: migration name, 2: error message */
				__( 'Migration %1$s failed: %2$s', 'wp-jarvis' ) . "\n",
				$migration,
				$e->getMessage()
			);
			throw $e;
		}
	}

	/**
	 * Run a migration's down method.
	 *
	 * @param string $migration The migration name.
	 *
	 * @return void
	 */
	private function runDown( string $migration ): void {
		$instance = $this->resolveMigration( $migration );

		if ( $instance === null ) {
			echo sprintf(
			/* translators: %s: migration name */
				__( 'Migration %s not found.', 'wp-jarvis' ) . "\n",
				$migration
			);

			return;
		}

		echo sprintf(
		/* translators: %s: migration name */
			__( 'Rolling back migration: %s...', 'wp-jarvis' ) . "\n",
			$migration
		);

		try {
			$instance->down();
			$this->repository->delete( $migration );
			echo sprintf(
			/* translators: %s: migration name */
				__( 'Migration %s rolled back.', 'wp-jarvis' ) . "\n",
				$migration
			);
		} catch ( \Throwable $e ) {
			echo sprintf(
			/* translators: 1: migration name, 2: error message */
				__( 'Migration %1$s rollback failed: %2$s', 'wp-jarvis' ) . "\n",
				$migration,
				$e->getMessage()
			);
			throw $e;
		}
	}

	/**
	 * Resolve a migration class from its file name.
	 *
	 * @param string $migration The migration name.
	 *
	 * @return Migration|null The migration instance or null if not found.
	 */
	private function resolveMigration( string $migration ): ?Migration {
		foreach ( $this->paths as $path ) {
			$file = $path . '/' . $migration . '.php';

			if ( ! File::exists( $file ) ) {
				continue;
			}

			require_once $file;

			// Convert filename to class name
			$class = $this->getMigrationClass( $migration );

			if ( ! class_exists( $class ) ) {
				continue;
			}

			$instance = new $class();

			if ( ! $instance instanceof Migration ) {
				continue;
			}

			return $instance;
		}

		return null;
	}

	/**
	 * Get the migration class name from file name.
	 *
	 * @param string $migration The migration file name.
	 *
	 * @return string The migration class name.
	 */
	private function getMigrationClass( string $migration ): string {
		// Remove date prefix if present
		$name = preg_replace( '/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration );

		// Convert to class name
		$class = str_replace( '_', ' ', ucwords( $name ) );
		$class = str_replace( ' ', '', $class );

		return $class;
	}

	/**
	 * Ensure the migrations table exists.
	 *
	 * @return void
	 */
	private function ensureMigrationTable(): void {
		$this->repository->createTable();
	}

	/**
	 * Check if the application is in production environment.
	 *
	 * @return bool True if in production.
	 */
	private function isProduction(): bool {
		$environment = Config::get( 'app.env', 'local' );

		return in_array( $environment, [ 'production', 'prod' ], true );
	}
}
