<?php

namespace WPJarvis\Framework\Database\Seeders;

use Illuminate\Database\Seeder as BaseSeeder;
use WPJarvis\Framework\Application;

/**
 * Seeder
 *
 * Base seeder class for WP Jarvis.
 *
 * @package WPJarvis\Framework\Database\Seeders
 */
abstract class Seeder extends BaseSeeder {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected Application $app;

	/**
	 * Create a new seeder instance.
	 *
	 * @param Application $app
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	abstract public function run(): void;

	/**
	 * Call another seeder class.
	 *
	 * @param string $class
	 *
	 * @return $this
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function call( $class, $silent = false, array $parameters = [] ): self {
		$seeder = $this->app->make( $class );

		$seeder->setContainer( $this->app );
		$seeder->setCommand( $this->command );

		$seeder->run();

		return $this;
	}
}
