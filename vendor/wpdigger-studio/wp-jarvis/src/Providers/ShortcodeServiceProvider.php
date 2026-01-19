<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Frontend\Shortcode\Adapters\ElementorWidget;
use WPJarvis\Framework\WP\Frontend\Shortcode\Adapters\WPBakeryElement;
use WPJarvis\Framework\WP\Frontend\Shortcode\ShortcodeRegistry;
use WPJarvis\Framework\WP\Frontend\Shortcode\TinyMCE\ShortcodeButton;
use WPJarvis\Framework\WP\Frontend\Shortcode\TinyMCE\ShortcodeDialogController;

/**
 * ShortcodeServiceProvider
 *
 * Registers shortcode-related services including the registry, TinyMCE button,
 * AJAX dialog controller for Field System integration, and page builder integrations.
 */
class ShortcodeServiceProvider extends ServiceProvider
{
	/**
	 * Register service provider.
	 */
	public function register(): void
	{
		// Register the shortcode registry as a singleton
		$this->app->singleton(ShortcodeRegistry::class, function () {
			return new ShortcodeRegistry();
		});
	}

	/**
	 * Bootstrap service provider.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void
	{
		// Register the TinyMCE button after init (when shortcodes are registered)
		Hooks::action('init', [$this, 'registerTinyMCEButton'], 20);

		// Register AJAX handler directly (not on init, needs to be early)
		$this->registerDialogController();

		// Register Elementor widgets when Elementor is active
		$this->registerElementorWidgets();

		// Register WPBakery elements when WPBakery is active
		$this->registerWPBakeryElements();
	}

	/**
	 * Register the TinyMCE shortcode button.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerTinyMCEButton(): void
	{
		// Only if we have Classic Editor or TinyMCE available
		if (!is_admin()) {
			return;
		}

		// Check if TinyMCE is being used (not Gutenberg-only)
		$config = $this->app->make('config');
		if (!$config->get('app.tinymce_button', true)) {
			return;
		}

		$registry = $this->app->make(ShortcodeRegistry::class);

		// Only register the button if we have shortcodes
		if ($registry->count() === 0) {
			return;
		}

		$button = new ShortcodeButton($registry);
		$button->register();
	}

	/**
	 * Register the AJAX dialog controller for Field System rendering.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function registerDialogController(): void
	{
		$registry = $this->app->make(ShortcodeRegistry::class);
		$fieldRegistry = $this->app->make('field_registry');

		$controller = new ShortcodeDialogController($registry, $fieldRegistry);
		$controller->register();
	}

	// ═══════════════════════════════════════════════════════════════════════
	// ELEMENTOR INTEGRATION
	// ═══════════════════════════════════════════════════════════════════════

	/**
	 * Register Elementor widgets when Elementor plugin is active.
	 *
	 * Auto-discovers widget classes from app/WordPress/Shortcodes/Elementor directory.
	 *
	 * @return void
	 */
	public function registerElementorWidgets(): void
	{
		// Only register if Elementor is active
		if (!did_action('elementor/loaded')) {
			// Hook for when Elementor loads later
			Hooks::action('elementor/loaded', function () {
				$this->hookElementorWidgetRegistration();
			});
		} else {
			$this->hookElementorWidgetRegistration();
		}
	}

	/**
	 * Hook into Elementor's widget registration.
	 *
	 * @return void
	 */
	protected function hookElementorWidgetRegistration(): void
	{
		Hooks::action('elementor/widgets/register', function ($widgets_manager) {
			$this->discoverAndRegisterElementorWidgets($widgets_manager);
		});
	}

	/**
	 * Discover and register Elementor widgets from the plugin's Elementor directory.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function discoverAndRegisterElementorWidgets($widgets_manager): void
	{
		$config = $this->app->make('config');

		// Get the Elementor widgets directory path from config or use default
		$elementorDir = $config->get(
			'app.elementor_widgets_path',
			$this->app->basePath('app/WordPress/Shortcodes/Elementor')
		);

		if (!is_dir($elementorDir)) {
			return;
		}

		// Get the namespace for widget classes
		$namespace = $config->get(
			'app.elementor_widgets_namespace',
			$config->get('app.namespace', 'App') . '\\WordPress\\Shortcodes\\Elementor'
		);

		// Scan directory for PHP files
		$files = glob($elementorDir . '/*.php');

		if (empty($files)) {
			return;
		}

		foreach ($files as $file) {
			$className = pathinfo($file, PATHINFO_FILENAME);
			$fullClass = $namespace . '\\' . $className;

			// Skip if the class doesn't exist
			if (!class_exists($fullClass)) {
				continue;
			}

			// Verify it extends ElementorWidget
			if (!is_subclass_of($fullClass, ElementorWidget::class)) {
				continue;
			}

			// Register the widget
			$widgets_manager->register(new $fullClass());
		}

		/**
		 * Fires after all auto-discovered Elementor widgets are registered.
		 *
		 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
		 */
		Hooks::doAction('elementor_widgets_registered', $widgets_manager);
	}

	// ═══════════════════════════════════════════════════════════════════════
	// WPBAKERY INTEGRATION
	// ═══════════════════════════════════════════════════════════════════════

	/**
	 * Register WPBakery elements when the WPBakery plugin is active.
	 *
	 * Auto-discovers element classes from app/WordPress/Shortcodes/WPBakery directory.
	 *
	 * @return void
	 */
	public function registerWPBakeryElements(): void
	{
		// WPBakery uses vc_before_init for element registration
		Hooks::action('vc_before_init', function () {
			$this->discoverAndRegisterWPBakeryElements();
		});
	}

	/**
	 * Discover and register WPBakery elements from the plugin's WPBakery directory.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Exception
	 */
	protected function discoverAndRegisterWPBakeryElements(): void
	{
		// Check if the vc_map function exists (WPBakery is active)
		if (!function_exists('vc_map')) {
			return;
		}

		$config = $this->app->make('config');

		// Get the WPBakery elements directory path from config or use default
		$wpbakeryDir = $config->get(
			'app.wpbakery_elements_path',
			$this->app->basePath('app/WordPress/Shortcodes/WPBakery')
		);

		if (!is_dir($wpbakeryDir)) {
			return;
		}

		// Get the namespace for element classes
		$namespace = $config->get(
			'app.wpbakery_elements_namespace',
			$config->get('app.namespace', 'App') . '\\WordPress\\Shortcodes\\WPBakery'
		);

		// Scan directory for PHP files
		$files = glob($wpbakeryDir . '/*.php');

		if (empty($files)) {
			return;
		}

		foreach ($files as $file) {
			$className = pathinfo($file, PATHINFO_FILENAME);
			$fullClass = $namespace . '\\' . $className;

			// Skip if the class doesn't exist
			if (!class_exists($fullClass)) {
				continue;
			}

			// Verify it extends WPBakeryElement
			if (!is_subclass_of($fullClass, WPBakeryElement::class)) {
				continue;
			}

			// Create an instance and register
			$element = new $fullClass();
			$element->register();
		}

		/**
		 * Fires after all auto-discovered WPBakery elements are registered.
		 */
		Hooks::doAction('wpbakery_elements_registered');
	}
}
