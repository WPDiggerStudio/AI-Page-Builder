<?php

namespace WPJarvis\Framework\Database\Migrations;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use WPJarvis\Framework\Application;

/**
 * Migrator
 *
 * Handles database migrations for WP Jarvis.
 *
 * @package WPJarvis\Framework\Database\Migrations
 */
class Migrator extends BaseMigrator {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected Application $app;

	/**
	 * The migration repository implementation.
	 *
	 * @var MigrationRepositoryInterface
	 */
	protected $repository;

	/**
	 * Create a new migrator instance.
	 *
	 * @param Application $app
	 * @param MigrationRepositoryInterface $repository
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct( Application $app, MigrationRepositoryInterface $repository ) {
		$this->app        = $app;
		$this->repository = $repository;

		parent::__construct( $repository, $app->make( 'db' ), $app->make( 'files' ) );
	}

	/**
	 * Run the pending migrations.
	 *
	 * @param array<string>|null $paths
	 * @param array<string>|null $options
	 *
	 * @return array<int, string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function run( $paths = [], array $options = [] ): array {
		// Get migration paths
		$paths = $paths ?: $this->paths();

		// Run migrations
		return parent::run( $paths, $options );
	}

	/**
	 * Roll back the last migration operation.
	 *
	 * @param array<string>|null $paths
	 * @param array<string>|null $options
	 *
	 * @return array<int, string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function rollback( $paths = [], array $options = [] ): array {
		// Get migration paths
		$paths = $paths ?: $this->paths();

		// Rollback migrations
		return parent::rollback( $paths, $options );
	}

	/**
	 * Get all the migration files.
	 *
	 * @return array<string, array<string>>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function getMigrationFiles( $paths = [] ): array {
		$files = [];

		foreach ( $this->paths() as $path ) {
			$files[ $path ] = $this->app->make( 'files' )->glob( $path . '/*.php' );
		}

		return $files;
	}

	/**
	 * Get the migration paths.
	 *
	 * @return array<string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function paths(): array {
		$paths = [
			$this->app->databasePath( 'migrations' ),
		];

		// Add plugin migration paths
		$pluginPaths = $this->app->make( 'config' )->get( 'app.migration_paths', [] );
		foreach ( $pluginPaths as $path ) {
			$paths[] = $path;
		}

		return array_unique( $paths );
	}

	/**
	 * Get the migration repository instance.
	 *
	 * @return MigrationRepositoryInterface
	 */
	public function getRepository(): MigrationRepositoryInterface {
		return $this->repository;
	}
}
