<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Database;

use Illuminate\Support\Facades\DB;

/**
 * Seeder Abstract Class
 *
 * Base class for all database seeders.
 * Seeders should extend this class and implement the run() method.
 *
 * @package WPJarvis\Framework\Database
 */
abstract class Seeder {
	/**
	 * The seeder name.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Run the seeder.
	 *
	 * This method should contain the logic to populate
	 * the database with sample or initial data.
	 *
	 * @return void
	 */
	abstract public function run(): void;

	/**
	 * Get the seeder name.
	 *
	 * @return string The seeder name
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Call another seeder.
	 *
	 * @param string $seeder The seeder class name
	 *
	 * @return void
	 */
	protected function call( string $seeder ): void {
		if ( ! class_exists( $seeder ) ) {
			echo sprintf(
			/* translators: %s: seeder class name */
				__( 'Seeder %s not found.', 'wp-jarvis' ) . "\n",
				$seeder
			);

			return;
		}

		echo sprintf(
		/* translators: %s: seeder class name */
			__( 'Calling seeder: %s...', 'wp-jarvis' ) . "\n",
			$seeder
		);

		$instance = new $seeder();
		$instance->run();

		echo sprintf(
		/* translators: %s: seeder class name */
			__( 'Seeder %s completed.', 'wp-jarvis' ) . "\n",
			$seeder
		);
	}

	/**
	 * Get the table prefix.
	 *
	 * @return string The WordPress table prefix
	 */
	protected function getTablePrefix(): string {
		global $wpdb;

		return $wpdb->prefix;
	}

	/**
	 * Get a full table name with a prefix.
	 *
	 * @param string $table The table name without a prefix
	 *
	 * @return string The full table name with a prefix
	 */
	protected function getTableName( string $table ): string {
		return $this->getTablePrefix() . $table;
	}

	/**
	 * Insert data into a table.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<array<string, mixed>> $data The data to insert
	 *
	 * @return bool True if successful
	 */
	protected function insert( string $table, array $data ): bool {
		$tableName = $this->getTableName( $table );

		return DB::table( $tableName )->insert( $data ) !== false;
	}

	/**
	 * Insert data into a table with an ignore option.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<array<string, mixed>> $data The data to insert
	 *
	 * @return bool True if successful
	 */
	protected function insertOrIgnore( string $table, array $data ): bool {
		$tableName = $this->getTableName( $table );

		return DB::table( $tableName )->insertOrIgnore( $data ) !== false;
	}

	/**
	 * Truncate a table.
	 *
	 * @param string $table The table name without a prefix
	 *
	 * @return void
	 */
	protected function truncate( string $table ): void {
		$tableName = $this->getTableName( $table );

		DB::table( $tableName )->truncate();
	}

	/**
	 * Delete data from a table.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<string, mixed> $conditions The delete conditions
	 *
	 * @return int The number of rows deleted
	 */
	protected function delete( string $table, array $conditions ): int {
		$tableName = $this->getTableName( $table );

		$query = DB::table( $tableName );

		foreach ( $conditions as $column => $value ) {
			$query->where( $column, $value );
		}

		return $query->delete();
	}

	/**
	 * Update data in a table.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<string, mixed> $values The values to update
	 * @param array<string, mixed> $conditions The update conditions
	 *
	 * @return int The number of rows updated
	 */
	protected function update( string $table, array $values, array $conditions ): int {
		$tableName = $this->getTableName( $table );

		$query = DB::table( $tableName );

		foreach ( $conditions as $column => $value ) {
			$query->where( $column, $value );
		}

		return $query->update( $values );
	}

	/**
	 * Check if a record exists.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<string, mixed> $conditions The conditions to check
	 *
	 * @return bool True if the record exists
	 */
	protected function exists( string $table, array $conditions ): bool {
		$tableName = $this->getTableName( $table );

		$query = DB::table( $tableName );

		foreach ( $conditions as $column => $value ) {
			$query->where( $column, $value );
		}

		return $query->exists();
	}

	/**
	 * Get a record from a table.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<string, mixed> $conditions The conditions to find
	 *
	 * @return \stdClass|null The record or null if not found
	 */
	protected function find( string $table, array $conditions ): ?\stdClass {
		$tableName = $this->getTableName( $table );

		$query = DB::table( $tableName );

		foreach ( $conditions as $column => $value ) {
			$query->where( $column, $value );
		}

		return $query->first();
	}

	/**
	 * Get records from a table.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<string, mixed>|null $conditions The conditions to find (null for all)
	 *
	 * @return \Illuminate\Support\Collection The records
	 */
	protected function get( string $table, ?array $conditions = null ): \Illuminate\Support\Collection {
		$tableName = $this->getTableName( $table );

		$query = DB::table( $tableName );

		if ( $conditions !== null ) {
			foreach ( $conditions as $column => $value ) {
				$query->where( $column, $value );
			}
		}

		return $query->get();
	}

	/**
	 * Count records in a table.
	 *
	 * @param string $table The table name without a prefix
	 * @param array<string, mixed>|null $conditions The conditions to count (null for all)
	 *
	 * @return int The number of records
	 */
	protected function count( string $table, ?array $conditions = null ): int {
		$tableName = $this->getTableName( $table );

		$query = DB::table( $tableName );

		if ( $conditions !== null ) {
			foreach ( $conditions as $column => $value ) {
				$query->where( $column, $value );
			}
		}

		return $query->count();
	}
}
