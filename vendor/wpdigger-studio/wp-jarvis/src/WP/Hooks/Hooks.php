<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Hooks;

use WPJarvis\Framework\Support\Facades\Config;

/**
 * Hooks Manager Class
 *
 * This class provides a wrapper for WordPress hooks API with a consistent prefix.
 * It allows for easier management of actions and filters within the WPJarvis plugin.
 *
 * @since 1.0.0
 */
class Hooks {
	/**
	 * The hook prefix slug.
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Store registered hooks for tracking.
	 *
	 * @var array
	 */
	private array $registeredHooks = [
		'actions' => [],
		'filters' => []
	];

	/**
	 * List of core WordPress hooks that should NOT be prefixed.
	 *
	 * @var array<string>
	 */
	private array $wordpressHooks = [
		// Init & load
		'init',
		'plugins_loaded',
		'after_setup_theme',
		'setup_theme',
		'muplugins_loaded',

		// Template system
		'template_redirect',
		'wp_loaded',
		'shutdown',

		// Query system
		'pre_get_posts',
		'the_post',
		'loop_start',
		'loop_end',

		// Admin
		'admin_init',
		'admin_menu',
		'admin_notices',
		'current_screen',
		'admin_bar_menu',
		'post_updated_messages',
		'bulk_post_updated_messages',
		'restrict_manage_posts',
		'add_meta_boxes',
		'admin_head',
		'admin_footer',

		// REST API
		'rest_api_init',
		'rest_api_loaded',

		// Scripts & styles
		'wp_enqueue_scripts',
		'admin_enqueue_scripts',

		// Save & edit
		'save_post',
		'edit_post',
		'wp_insert_post',

		// Login
		'wp_login',
		'wp_logout',
		'authenticate',

		// Another common
		'wp_head',
		'widgets_init',
		'customize_register',
		'print_styles_array',
		'script_loader_tag',
		'cron_schedules',
		'schedule_run',
		'define_schedule',
		'shortcode_*',
		'manage_*',
		'wp_ajax_*',
		'wp_ajax_nopriv_*',

		// Mail
		'wp_mail_from',
		'wp_mail_from_name',

		// Tinymce
		'mce_buttons',
		'mce_external_plugins',

		// Third Party apps
		'elementor/loaded',
		'elementor/widgets/register',
		'vc_before_init',
	];


	/**
	 * Hooks constructor.
	 *
	 */
	public function __construct() {
		$this->prefix = Config::get( 'app.slug', 'wp-jarvis' );
	}

	/**
	 * Add prefix to hook name.
	 *
	 * @param string $hook The hook name to prefix.
	 *
	 * @return string The prefixed hook name.
	 */
	protected function prefix( string $hook ): string {
		foreach ( $this->wordpressHooks as $pattern ) {
			if ( fnmatch( $pattern, $hook ) ) {
				return $hook;
			}
		}

		return "{$this->prefix}_{$hook}";
	}

	/**
	 * Register a WordPress action.
	 *
	 * @param string $hook The action name.
	 * @param callable|array $callback The callback function or method.
	 * @param int|null $priority Optional. The priority at which the function should be executed. Default 10.
	 * @param int $acceptedArgs Optional. The number of arguments the function accepts. Default 1.
	 *
	 * @return void
	 */
	public function action( string $hook, callable|array $callback, int|null $priority = null, int $acceptedArgs = 1 ): void {
		$priority     ??= 10;
		$prefixedHook = $this->prefix( $hook );

		add_action( $prefixedHook, $callback, $priority, $acceptedArgs );

		// Track the registered action
		$this->registeredHooks['actions'][ $prefixedHook ][] = [
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $acceptedArgs
		];
	}

	/**
	 * Register a WordPress filter.
	 *
	 * @param string $hook The filter name.
	 * @param callable|array $callback The callback function or method.
	 * @param int $priority Optional. The priority at which the function should be executed. Default 10.
	 * @param int $acceptedArgs Optional. The number of arguments the function accepts. Default 1.
	 *
	 * @return void
	 */
	public function filter( string $hook, callable|array $callback, int $priority = 10, int $acceptedArgs = 1 ): void {
		$prefixedHook = $this->prefix( $hook );

		add_filter( $prefixedHook, $callback, $priority, $acceptedArgs );

		// Track the registered filter
		$this->registeredHooks['filters'][ $prefixedHook ][] = [
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $acceptedArgs
		];
	}

	/**
	 * Remove a registered action.
	 *
	 * @param string $hook The action name.
	 * @param callable|array $callback The callback function or method.
	 * @param int $priority Optional. The priority of the function. Default 10.
	 *
	 * @return void
	 */
	public function removeAction( string $hook, callable|array $callback, int $priority = 10 ): void {
		$prefixedHook = $this->prefix( $hook );
		remove_action( $prefixedHook, $callback, $priority );

		// Remove from tracked actions
		if ( isset( $this->registeredHooks['actions'][ $prefixedHook ] ) ) {
			foreach ( $this->registeredHooks['actions'][ $prefixedHook ] as $key => $action ) {
				if ( $action['callback'] === $callback && $action['priority'] === $priority ) {
					unset( $this->registeredHooks['actions'][ $prefixedHook ][ $key ] );
				}
			}
		}
	}

	/**
	 * Remove a registered filter.
	 *
	 * @param string $hook The filter name.
	 * @param callable|array $callback The callback function or method.
	 * @param int $priority Optional. The priority of the function. Default 10.
	 *
	 * @return void
	 */
	public function removeFilter( string $hook, callable|array $callback, int $priority = 10 ): void {
		$prefixedHook = $this->prefix( $hook );
		remove_filter( $prefixedHook, $callback, $priority );

		// Remove from tracked filters
		if ( isset( $this->registeredHooks['filters'][ $prefixedHook ] ) ) {
			foreach ( $this->registeredHooks['filters'][ $prefixedHook ] as $key => $filter ) {
				if ( $filter['callback'] === $callback && $filter['priority'] === $priority ) {
					unset( $this->registeredHooks['filters'][ $prefixedHook ][ $key ] );
				}
			}
		}
	}

	/**
	 * Check if an action has been fired.
	 *
	 * Wrapper around WordPress {@see did_action()}.
	 *
	 * @param string $hook The action hook name.
	 *
	 * @return int            Number of times the action was fired (0 if never).
	 */
	public function didAction( string $hook ): int {
		if ( function_exists( 'did_action' ) ) {
			return did_action( $hook );
		}

		return 0;
	}

	/**
	 * Execute all functions hooked to a given action.
	 *
	 * @param string $hook The action name.
	 * @param mixed ...$args Additional parameters passed to the functions hooked to the action.
	 *
	 * @return void
	 */
	public function doAction( string $hook, ...$args ): void {
		do_action( $this->prefix( $hook ), ...$args );
	}

	/**
	 * Apply filters to a value.
	 *
	 * @param string $hook The filter hook name.
	 * @param mixed $value The value to filter.
	 * @param mixed ...$args Additional parameters passed to the functions hooked to the filter.
	 *
	 * @return mixed The filtered value.
	 */
	public function applyFilters( string $hook, mixed $value, ...$args ): mixed {
		return apply_filters( $this->prefix( $hook ), $value, ...$args );
	}

	/**
	 * Check if an action has been registered.
	 *
	 * @param string $hook The action name.
	 * @param callable|array|string|null $callback Optional. The callback to check for. Default null.
	 *
	 * @return bool|int Returns the priority of the hook if it exists, false otherwise.
	 */
	public function hasAction( string $hook, callable|array|string $callback = null ): bool|int {
		return has_action( $this->prefix( $hook ), $callback );
	}

	/**
	 * Check if a filter has been registered.
	 *
	 * @param string $hook The filter name.
	 * @param callable|array|string|null $callback Optional. The callback to check for. Default null.
	 *
	 * @return bool|int Returns the priority of the hook if it exists, false otherwise.
	 */
	public function hasFilter( string $hook, callable|array|string $callback = null ): bool|int {
		return has_filter( $this->prefix( $hook ), $callback );
	}

	/**
	 * Get all registered hooks (actions and filters).
	 *
	 * @return array An array containing all registered actions and filters.
	 */
	public function getRegisteredHooks(): array {
		return $this->registeredHooks;
	}

	/**
	 * Get all registered actions.
	 *
	 * @return array An array containing all registered actions.
	 */
	public function getRegisteredActions(): array {
		return $this->registeredHooks['actions'];
	}

	/**
	 * Get all registered filters.
	 *
	 * @return array An array containing all registered filters.
	 */
	public function getRegisteredFilters(): array {
		return $this->registeredHooks['filters'];
	}

	/**
	 * Get all WordPress registered hooks that match our prefix.
	 *
	 * @return array An array containing all WordPress hooks with our prefix.
	 */
	public function getAllWordPressHooks(): array {
		global $wp_filter;

		$allHooks = [
			'actions' => [],
			'filters' => []
		];

		// WordPress stores both actions and filters in $wp_filter
		foreach ( $wp_filter as $hookName => $hookDetails ) {
			// Only include hooks with our prefix
			if ( str_starts_with( $hookName, $this->prefix ) ) {
				$callbacks = [];

				// Extract callback details
				foreach ( $hookDetails as $priority => $priorityCallbacks ) {
					foreach ( $priorityCallbacks as $callbackKey => $callbackDetails ) {
						$callbacks[] = [
							'priority'      => $priority,
							'accepted_args' => $callbackDetails['accepted_args'],
							'callback'      => $this->getCallbackName( $callbackDetails['function'] )
						];
					}
				}

				// Add to the appropriate category (using WordPress internals to determine type)
				if ( has_action( $hookName ) ) {
					$allHooks['actions'][ $hookName ] = $callbacks;
				} else {
					$allHooks['filters'][ $hookName ] = $callbacks;
				}
			}
		}

		return $allHooks;
	}

	/**
	 * Get a human-readable name for a callback.
	 *
	 * @param callable|array|string $callback The callback to get the name for.
	 *
	 * @return string The human-readable name of the callback.
	 */
	protected function getCallbackName( callable|array|string $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) ) {
			$target = $callback[0] ?? null;
			$method = $callback[1] ?? null;

			if ( is_object( $target ) ) {
				return get_class( $target ) . '->' . (string) $method;
			}

			return (string) $target . '::' . (string) $method;
		}

		if ( $callback instanceof \Closure ) {
			return 'Closure';
		}

		return 'Unknown';
	}
}