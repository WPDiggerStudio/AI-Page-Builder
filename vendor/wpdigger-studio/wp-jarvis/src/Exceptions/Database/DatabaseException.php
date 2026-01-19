<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Database;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * DatabaseException - Exception for database operations.
 */
class DatabaseException extends FrameworkException {
	/**
	 * The SQL query that caused the error.
	 */
	protected ?string $query = null;

	/**
	 * Create an exception for connection failure.
	 *
	 * @param string $message The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function connectionFailed( string $message = '' ): static {
		$defaultMessage = __( 'Database connection failed', 'wp-jarvis' );

		return new static( $message ?: $defaultMessage );
	}

	/**
	 * Create exception for query failure.
	 *
	 * @param string $query The SQL query.
	 * @param string $error The database error.
	 *
	 * @return static The exception instance.
	 */
	public static function queryFailed( string $query, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: %s: database error message */
				__( 'Query failed: %s', 'wp-jarvis' ),
				$error
			)
		) )
			->withContext( [ 'query' => $query, 'error' => $error ] )
			->setQuery( $query );
	}

	/**
	 * Create exception for migration failure.
	 *
	 * @param string $migration The migration name.
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function migrationFailed( string $migration, string $error ): static {
		return ( new static(
			sprintf(
			/* translators: 1: migration name, 2: error message */
				__( "Migration '%1\$s' failed: %2\$s", 'wp-jarvis' ),
				$migration,
				$error
			)
		) )
			->withContext( [ 'migration' => $migration, 'error' => $error ] );
	}

	/**
	 * Create an exception for migration not found.
	 *
	 * @param string $migration The migration name.
	 *
	 * @return static The exception instance.
	 */
	public static function migrationNotFound( string $migration ): static {
		return ( new static(
			sprintf(
			/* translators: %s: migration name */
				__( "Migration '%s' not found.", 'wp-jarvis' ),
				$migration
			)
		) )
			->withContext( [ 'migration' => $migration ] );
	}

	/**
	 * Create exception for table not found.
	 *
	 * @param string $table The table name.
	 *
	 * @return static The exception instance.
	 */
	public static function tableNotFound( string $table ): static {
		return ( new static(
			sprintf(
			/* translators: %s: table name */
				__( "Table '%s' not found.", 'wp-jarvis' ),
				$table
			)
		) )
			->withContext( [ 'table' => $table ] );
	}

	/**
	 * Create exception for model not found.
	 *
	 * @param string $model The model class name.
	 * @param int|string $id The model ID.
	 *
	 * @return static The exception instance.
	 */
	public static function modelNotFound( string $model, int|string $id ): static {
		return ( new static(
			sprintf(
			/* translators: 1: model class name, 2: model ID */
				__( 'Model [%1$s] with ID [%2$s] not found.', 'wp-jarvis' ),
				$model,
				$id
			)
		) )
			->withContext( [ 'model' => $model, 'id' => $id ] );
	}

	/**
	 * Create exception for transaction failure.
	 *
	 * @param string $error The error message.
	 *
	 * @return static The exception instance.
	 */
	public static function transactionFailed( string $error ): static {
		return new static(
			sprintf(
			/* translators: %s: error message */
				__( 'Database transaction failed: %s', 'wp-jarvis' ),
				$error
			)
		);
	}

	/**
	 * Set the SQL query.
	 *
	 * @param string $query The SQL query.
	 *
	 * @return static The exception instance for method chaining.
	 */
	public function setQuery( string $query ): static {
		$this->query = $query;

		return $this;
	}

	/**
	 * Get the SQL query.
	 *
	 * @return string|null The SQL query.
	 */
	public function getQuery(): ?string {
		return $this->query;
	}
}
