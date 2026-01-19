<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * Validation Service Provider
 *
 * @package WPJarvis\Framework\Providers
 */
class ValidationServiceProvider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void {
		// Validator is already registered in Application
	}

	/**
	 * Bootstrap the service provider.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void {
		// Add custom validation rules
		$this->registerCustomRules();
	}

	/**
	 * Register custom validation rules.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function registerCustomRules(): void {
		$validator = $this->app->make( 'validator' );

		// WordPress-specific validation rules
		$validator->extend( 'wp_nonce', function ( $attribute, $value, $parameters ) {
			$action = $parameters[0] ?? - 1;

			return wp_verify_nonce( $value, $action ) !== false;
		}, 'The :attribute is not a valid nonce.' );

		$validator->extend( 'capability', function ( $attribute, $value, $parameters ) {
			return current_user_can( $value );
		}, 'You do not have the required capability.' );

		$validator->extend( 'post_exists', function ( $attribute, $value, $parameters ) {
			return get_post( $value ) !== null;
		}, 'The :attribute does not exist.' );

		$validator->extend( 'term_exists', function ( $attribute, $value, $parameters ) {
			$taxonomy = $parameters[0] ?? '';

			return term_exists( $value, $taxonomy ) !== null;
		}, 'The :attribute does not exist.' );

		$validator->extend( 'user_exists', function ( $attribute, $value, $parameters ) {
			return get_user_by( 'ID', $value ) !== false;
		}, 'The :attribute does not exist.' );

		$validator->extend( 'sanitized_title', function ( $attribute, $value, $parameters ) {
			return sanitize_title( $value ) === $value;
		}, 'The :attribute must be a valid sanitized title.' );
	}
}
