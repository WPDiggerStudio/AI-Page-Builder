<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Abstract Class
 *
 * Base class for all database migrations.
 * Migrations should extend this class and implement up() and down() methods.
 *
 * @package WPJarvis\Framework\Database
 */
abstract class Migration {
	/**
	 * The migration name.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Run the migration.
	 *
	 * This method should contain the logic to create tables,
	 * add columns, or modify the database schema.
	 *
	 * @return void
	 */
	abstract public function up(): void;

	/**
	 * Reverse the migration.
	 *
	 * This method should contain the logic to undo the changes
	 * made in the up() method (drop tables, remove columns, etc.).
	 *
	 * @return void
	 */
	abstract public function down(): void;

	/**
	 * Get the migration name.
	 *
	 * @return string The migration name
	 */
	public function getName(): string {
		return $this->name;
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
	 * Get a full table name with prefix.
	 *
	 * @param string $table The table name without prefix
	 *
	 * @return string The full table name with prefix
	 */
	protected function getTableName( string $table ): string {
		return $this->getTablePrefix() . $table;
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table The table name without prefix
	 *
	 * @return bool True if table exists
	 */
	protected function tableExists( string $table ): bool {
		$tableName = $this->getTableName( $table );

		return Schema::hasTable( $tableName );
	}

	/**
	 * Create a new table.
	 *
	 * @param string $table The table name without prefix
	 * @param callable $callback The callback to define the table schema
	 *
	 * @return void
	 */
	protected function createTable( string $table, callable $callback ): void {
		$tableName = $this->getTableName( $table );

		Schema::create( $tableName, $callback );
	}

	/**
	 * Drop a table if it exists.
	 *
	 * @param string $table The table name without prefix
	 *
	 * @return void
	 */
	protected function dropTableIfExists( string $table ): void {
		$tableName = $this->getTableName( $table );

		Schema::dropIfExists( $tableName );
	}

	/**
	 * Rename a table.
	 *
	 * @param string $from The old table name without prefix
	 * @param string $to The new table name without prefix
	 *
	 * @return void
	 */
	protected function renameTable( string $from, string $to ): void {
		$fromTable = $this->getTableName( $from );
		$toTable = $this->getTableName( $to );

		Schema::rename( $fromTable, $toTable );
	}

	/**
	 * Add a column to a table.
	 *
	 * @param string $table The table name without prefix
	 * @param string $column The column name
	 * @param callable $callback The callback to define the column
	 *
	 * @return void
	 */
	protected function addColumn( string $table, string $column, callable $callback ): void {
		$tableName = $this->getTableName( $table );

		Schema::table( $tableName, function ( Blueprint $table ) use ( $column, $callback ) {
			$table->addColumn( $column, $callback );
		} );
	}

	/**
	 * Drop a column from a table.
	 *
	 * @param string $table The table name without prefix
	 * @param string $column The column name
	 *
	 * @return void
	 */
	protected function dropColumn( string $table, string $column ): void {
		$tableName = $this->getTableName( $table );

		Schema::table( $tableName, function ( Blueprint $table ) use ( $column ) {
			$table->dropColumn( $column );
		} );
	}

	/**
	 * Rename a column in a table.
	 *
	 * @param string $table The table name without prefix
	 * @param string $from The old column name
	 * @param string $to The new column name
	 *
	 * @return void
	 */
	protected function renameColumn( string $table, string $from, string $to ): void {
		$tableName = $this->getTableName( $table );

		Schema::table( $tableName, function ( Blueprint $table ) use ( $from, $to ) {
			$table->renameColumn( $from, $to );
		} );
	}

	/**
	 * Add an index to a table.
	 *
	 * @param string $table The table name without prefix
	 * @param string|array $columns The column name(s)
	 * @param string|null $name The index name (optional)
	 *
	 * @return void
	 */
	protected function addIndex( string $table, string|array $columns, ?string $name = null ): void {
		$tableName = $this->getTableName( $table );

		Schema::table( $tableName, function ( Blueprint $table ) use ( $columns, $name ) {
			$table->index( $columns, $name );
		} );
	}

	/**
	 * Drop an index from a table.
	 *
	 * @param string $table The table name without prefix
	 * @param string|array $columns The column name(s)
	 *
	 * @return void
	 */
	protected function dropIndex( string $table, string|array $columns ): void {
		$tableName = $this->getTableName( $table );

		Schema::table( $tableName, function ( Blueprint $table ) use ( $columns ) {
			$table->dropIndex( $columns );
		} );
	}

	/**
	 * Add a foreign key constraint.
	 *
	 * @param string $table The table name without prefix
	 * @param string $column The column name
	 * @param string $referencedTable The referenced table name without prefix
	 * @param string $referencedColumn The referenced column name
	 * @param string|null $onDelete The on delete action (cascade, restrict, set null, no action)
	 * @param string|null $name The constraint name (optional)
	 *
	 * @return void
	 */
	protected function addForeignKey(
		string $table,
		string $column,
		string $referencedTable,
		string $referencedColumn,
		?string $onDelete = null,
		?string $name = null
	): void {
		$tableName = $this->getTableName( $table );
		$refTableName = $this->getTableName( $referencedTable );

		Schema::table( $tableName, function ( Blueprint $table ) use (
			$column,
			$refTableName,
			$referencedColumn,
			$onDelete,
			$name,
		) {
			$table->foreign( $column )
				->references( $referencedColumn )
				->on( $refTableName );

			if ( $onDelete !== null ) {
				$table->onDelete( $onDelete );
			}

			if ( $name !== null ) {
				$table->foreign( $column, $name );
			}
		} );
	}

	/**
	 * Drop a foreign key constraint.
	 *
	 * @param string $table The table name without prefix
	 * @param string|array $columns The column name(s)
	 *
	 * @return void
	 */
	protected function dropForeignKey( string $table, string|array $columns ): void {
		$tableName = $this->getTableName( $table );

		Schema::table( $tableName, function ( Blueprint $table ) use ( $columns ) {
			$table->dropForeign( $columns );
		} );
	}
}
