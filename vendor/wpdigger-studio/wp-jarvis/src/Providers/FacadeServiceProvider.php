<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use Illuminate\Support\Facades\Facade;
use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\App;
use WPJarvis\Framework\Support\Facades\Cache;
use WPJarvis\Framework\Support\Facades\Config;
use WPJarvis\Framework\Support\Facades\DB;
use WPJarvis\Framework\Support\Facades\Event;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\Support\Facades\Job;
use WPJarvis\Framework\Support\Facades\Log;
use WPJarvis\Framework\Support\Facades\Rest;
use WPJarvis\Framework\Support\Facades\Route;
use WPJarvis\Framework\Support\Facades\Validator;
use WPJarvis\Framework\Support\Facades\View;

/**
 * Facade Service Provider
 *
 * Registers facade aliases for easy access to services.
 *
 * @package WPJarvis\Framework\Providers
 */
class FacadeServiceProvider extends ServiceProvider {
	/**
	 * The facade aliases.
	 *
	 * Framework core facades are automatically registered by this provider.
	 * Application-specific facades can be added via config/app.php aliases.
	 *
	 * @var array<string, class-string>
	 */
	protected array $facades = [
		'App'       => App::class,
		'Config'    => Config::class,
		'Event'     => Event::class,
		'Hooks'     => Hooks::class,
		'Log'       => Log::class,
		'View'      => View::class,
		'Cache'     => Cache::class,
		'DB'        => DB::class,
		'Validator' => Validator::class,
		'Route'     => Route::class,
		'Rest'      => Rest::class,
		'Job'       => Job::class,
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void {
		// Set the facade application
		Facade::setFacadeApplication( $this->app );
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void {
		// Register facade aliases
		$this->registerFacadeAliases();
	}

	/**
	 * Register facade aliases.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function registerFacadeAliases(): void {
		try {
			// Get additional facades from config
			$configFacades = $this->app->make( 'config' )->get( 'app.aliases', [] );

			$aliases = array_merge( $this->facades, $configFacades );

			foreach ( $aliases as $alias => $facade ) {
				if ( class_exists( $facade ) && ! class_exists( $alias ) ) {
					class_alias( $facade, $alias );
				}
			}

		} catch ( \Exception $e ) {
			// Log error but don't crash
			wpj_logger()?->warning(
				'Could not register facade aliases - ' . $e->getMessage(),
				[ 'exception' => $e ]
			);
		}
	}
}
