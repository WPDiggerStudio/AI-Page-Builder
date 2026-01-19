<?php

declare( strict_types=1 );

namespace BraCalculator\App\Providers;

use BraCalculator\App\Console\Kernel;
use BraCalculator\App\Shortcodes\ExampleShortcode;
use BraCalculator\App\Widgets\ExampleWidget;
use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * App Service Provider
 *
 * Main application service provider for registering services.
 *
 * @package BraCalculator\App\Providers
 */
class AppServiceProvider extends ServiceProvider {
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(): void {
		//
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Register the Console Kernel for scheduled tasks
		Kernel::register();
	}
}
