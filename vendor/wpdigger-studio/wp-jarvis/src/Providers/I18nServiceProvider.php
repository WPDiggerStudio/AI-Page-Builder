<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * I18n Service Provider
 *
 * Handles internationalization and localization.
 *
 * @package WPJarvis\Framework\Providers
 */
class I18nServiceProvider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void {
		// Nothing to register
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', [ $this, 'loadTextdomain' ] );
	}

	/**
	 * Load the plugin textdomain.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function loadTextdomain(): void {
		$config     = $this->app->make( 'config' );
		$textDomain = $config->get( 'textdomain', $this->app->instanceValue( 'plugin.textdomain' ) );

		load_plugin_textdomain(
			$textDomain,
			false,
			$this->app->langPath()
		);
	}
}
