<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use WPJarvis\Framework\Support\Facades\Config;

/**
 * Migration Repository
 *
 * Manages the migration repository table and tracks
 * which migrations have been run.
 *
 * @package WPJarvis\Framework\Database
 */
class MigrationRepository {
	/**
	 * The migration table name.
	 *
	 * @var string
	 */
	private string $table = 'wp_jarvis_migrations';

	/**
	 * Create the migrations table.
	 *
	 * @return void
	 */
	public function createTable(): void {
		if ( $this->tableExists() ) {
			return;
		}

		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		DB::statement( "
			CREATE TABLE `{$tableName}` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`migration` varchar(255) NOT NULL,
				`batch` int(11) NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `migration` (`migration`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		" );
	}

	/**
	 * Check if the migrations table exists.
	 *
	 * @return bool True if table exists
	 */
	public function tableExists(): bool {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		return Schema::hasTable( $tableName );
	}

	/**
	 * Get all migrations that have been run.
	 *
	 * @return array<string> Array of migration names
	 */
	public function getRan(): array {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		$migrations = DB::table( $tableName )
			->orderBy( 'batch', 'desc' )
			->orderBy( 'id', 'desc' )
			->pluck( 'migration' )
			->toArray();

		return $migrations;
	}

	/**
	 * Get the last batch number.
	 *
	 * @return int The last batch number
	 */
	public function getLastBatchNumber(): int {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		$lastBatch = DB::table( $tableName )
			->max( 'batch' );

		return (int) ( $lastBatch ?? 0 );
	}

	/**
	 * Get migrations in the last batch.
	 *
	 * @return array<string> Array of migration names in the last batch
	 */
	public function getLast(): array {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		$lastBatch = $this->getLastBatchNumber();

		$migrations = DB::table( $tableName )
			->where( 'batch', $lastBatch )
			->orderBy( 'id', 'desc' )
			->pluck( 'migration' )
			->toArray();

		return $migrations;
	}

	/**
	 * Log a migration as run.
	 *
	 * @param string $migration The migration name
	 * @param int $batch The batch number
	 *
	 * @return void
	 */
	public function log( string $migration, int $batch ): void {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		DB::table( $tableName )->insert( [
			'migration' => $migration,
			'batch'     => $batch,
		] );
	}

	/**
	 * Remove a migration from the log.
	 *
	 * @param string $migration The migration name
	 *
	 * @return void
	 */
	public function delete( string $migration ): void {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		DB::table( $tableName )
			->where( 'migration', $migration )
			->delete();
	}

	/**
	 * Get the next batch number.
	 *
	 * @return int The next batch number
	 */
	public function getNextBatchNumber(): int {
		return $this->getLastBatchNumber() + 1;
	}

	/**
	 * Check if a migration has been run.
	 *
	 * @param string $migration The migration name
	 *
	 * @return bool True if migration has been run
	 */
	public function hasRun( string $migration ): bool {
		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		$count = DB::table( $tableName )
			->where( 'migration', $migration )
			->count();

		return $count > 0;
	}

	/**
	 * Drop the migrations table.
	 *
	 * @return void
	 */
	public function dropTable(): void {
		if ( ! $this->tableExists() ) {
			return;
		}

		$prefix = DB::getTablePrefix();
		$tableName = $prefix . $this->table;

		Schema::dropIfExists( $tableName );
	}

	/**
	 * Get the migration table name.
	 *
	 * @return string The migration table name
	 */
	public function getTableName(): string {
		$prefix = DB::getTablePrefix();

		return $prefix . $this->table;
	}
}
