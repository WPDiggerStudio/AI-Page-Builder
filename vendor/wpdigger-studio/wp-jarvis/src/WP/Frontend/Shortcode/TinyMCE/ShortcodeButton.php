<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend\Shortcode\TinyMCE;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Frontend\Shortcode\ShortcodeRegistry;

/**
 * ShortcodeButton - TinyMCE Classic Editor integration.
 *
 * Provides a toolbar button for inserting shortcodes with a
 * configuration dialog based on the shortcode's attribute schema.
 *
 * @package WPJarvis\Framework\WP\Frontend\Shortcode\TinyMCE
 */
class ShortcodeButton {
	/**
	 * Shortcode registry instance.
	 */
	private ShortcodeRegistry $registry;

	/**
	 * Button identifier.
	 */
	private string $buttonId;

	/**
	 * Button configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Create a new button provider.
	 *
	 * @param ShortcodeRegistry $registry Shortcode registry.
	 * @param string $buttonId Unique button identifier.
	 * @param array<string, mixed> $config Button configuration.
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct(
		ShortcodeRegistry $registry,
		string $buttonId = 'shortcodes',
		array $config = []
	) {
		$this->registry = $registry;
		$this->buttonId = ( $buttonId === 'shortcodes' ) ? strtolower( wpj_config( 'app.prefix' ) ) . '_shortcodes' : $buttonId;
		$this->config   = array_merge( [
			'title'     => wpj_config( 'app.prefix' ) . __( ' Shortcode', 'wp-jarvis' ),
			'icon'      => wpj_app()->baseUrl( 'resources/assets/icon/shortcode-icon.svg' ),
			'menuTitle' => __( 'Shortcodes', wpj_config( 'app.textdomain', 'wp-jarvis' ) ),
		], $config );
	}

	/**
	 * Register the TinyMCE button.
	 *
	 * @return void
	 */
	public function register(): void {
		// Only in admin
		if ( ! is_admin() ) {
			return;
		}

		// Register TinyMCE hooks
		Hooks::filter( 'mce_buttons', [ $this, 'addButton' ] );
		Hooks::filter( 'mce_external_plugins', [ $this, 'addPlugin' ] );

		// Enqueue dialog styles
		Hooks::action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );

		// Add an inline script with a shortcode config
		Hooks::action( 'admin_footer', [ $this, 'renderInlineConfig' ] );
	}

	/**
	 * Add the button to the TinyMCE toolbar.
	 *
	 * @param array<string> $buttons Existing buttons.
	 *
	 * @return array<string> Modified buttons.
	 */
	public function addButton( array $buttons ): array {
		$buttons[] = $this->buttonId;

		return $buttons;
	}

	/**
	 * Register the TinyMCE plugin.
	 *
	 * @param array<string, string> $plugins Existing plugins.
	 *
	 * @return array<string, string> Modified plugins.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function addPlugin( array $plugins ): array {
		$plugins[ $this->buttonId ] = $this->getPluginUrl();

		return $plugins;
	}

	/**
	 * Get the TinyMCE plugin JavaScript URL.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getPluginUrl(): string {
		// App not available => no URL.
		if ( ! function_exists( 'wpj_app' ) ) {
			return '';
		}

		$app = wpj_app();

		// -----------------------------------------
		// 1) Plugin override (preferred)
		// -----------------------------------------
		$customRel = 'resources/js/editor/shortcode-button.js';
		$customAbs = $app->basePath( $customRel );

		if ( is_string( $customAbs ) && $customAbs !== '' && file_exists( $customAbs ) ) {
			return $app->urlFromPath( $customAbs );
		}

		// -----------------------------------------
		// 2) Framework default (composer package)
		// -----------------------------------------
		$assetRelFromPackageRoot = 'resources/assets/js/tinymce/shortcode-button.js';

		// Try the composer-installed path first (fast + precise)
		$pkgDir = $app->composerPackagePath( 'wp-jarvis/framework' );
		if ( $pkgDir !== '' ) {
			$frameworkAbs = wp_normalize_path( $pkgDir . '/' . $assetRelFromPackageRoot );

			if ( file_exists( $frameworkAbs ) ) {
				return $app->urlFromPath( $frameworkAbs );
			}
		}

		// Fallback: search vendor/*/* for the asset (more dynamic but slower)
		$frameworkAbs = $app->findVendorAsset( $assetRelFromPackageRoot );
		if ( $frameworkAbs !== '' && file_exists( $frameworkAbs ) ) {
			return $app->urlFromPath( $frameworkAbs );
		}

		return '';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function enqueueAssets( string $hook ): void {
		// Only on post-editor screens
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		if ( ! function_exists( 'wpj_app' ) ) {
			return;
		}

		$app = wpj_app();

		// CSS is optional - dialog styling
		// Prefers plugin override (if you support it), otherwise load from vendor/framework.
		$customRel = 'resources/css/shortcode-dialog.css';
		$customAbs = $app->basePath( $customRel );

		if ( is_string( $customAbs ) && $customAbs !== '' && file_exists( $customAbs ) ) {
			wp_enqueue_style(
				wpj_config( 'app.slug' ) . '-shortcode-dialog',
				$app->urlFromPath( $customAbs ),
				[],
				(string) filemtime( $customAbs )
			);

			return;
		}

		// Framework/vendor default
		$assetRelFromPackageRoot = 'resources/assets/css/shortcode-dialog.css';

		// 1) Fast path: composer package location
		$pkgDir = $app->composerPackagePath( 'wp-jarvis/framework' );
		if ( $pkgDir !== '' ) {
			$frameworkAbs = wp_normalize_path( $pkgDir . '/' . $assetRelFromPackageRoot );

			if ( file_exists( $frameworkAbs ) ) {
				wp_enqueue_style(
					wpj_config( 'app.slug' ) . '-shortcode-dialog',
					$app->urlFromPath( $frameworkAbs ),
					[],
					(string) filemtime( $frameworkAbs )
				);

				return;
			}
		}

		// 2) Fallback: scan vendor/*/* (slower)
		$frameworkAbs = $app->findVendorAsset( $assetRelFromPackageRoot );
		if ( $frameworkAbs !== '' && file_exists( $frameworkAbs ) ) {
			wp_enqueue_style(
				wpj_config( 'app.slug' ) . '-shortcode-dialog',
				$app->urlFromPath( $frameworkAbs ),
				[],
				(string) filemtime( $frameworkAbs )
			);

			return;
		}
	}

	/**
	 * Render inline config script for TinyMCE plugin.
	 *
	 * @return void
	 */
	public function renderInlineConfig(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'post' ) {
			return;
		}

		$config = [
			'buttonId'   => $this->buttonId,
			'title'      => $this->config['title'],
			'menuTitle'  => $this->config['menuTitle'],
			'shortcodes' => $this->registry->toConfig(),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'icon'       => file_get_contents( $this->config['icon'] ),
			'nonce'      => wp_create_nonce( 'wpj_shortcode_dialog' ),
			'useModal'   => true, // Enable a modal-based dialog with Field System
			'i18n'       => [
				'insert'          => __( 'Insert', 'wp-jarvis' ),
				'cancel'          => __( 'Cancel', 'wp-jarvis' ),
				'noShortcodes'    => __( 'No shortcodes available.', 'wp-jarvis' ),
				'selectShortcode' => __( 'Select a shortcode', 'wp-jarvis' ),
				'content'         => __( 'Content', 'wp-jarvis' ),
				'loading'         => __( 'Loading...', 'wp-jarvis' ),
			],
		];

		printf(
			'<script>var wpJarvisShortcodeConfig = %s;</script>',
			wp_json_encode( $config )
		);
	}

	/**
	 * Get the button ID.
	 *
	 * @return string
	 */
	public function getButtonId(): string {
		return $this->buttonId;
	}

	/**
	 * Create a single-shortcode button.
	 *
	 * Creates a dedicated button for a single shortcode rather than a dropdown menu.
	 *
	 * @param ShortcodeRegistry $registry Shortcode registry.
	 * @param string $tag Shortcode tag.
	 * @param array<string, mixed> $config Button configuration.
	 *
	 * @return static
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public static function forShortcode(
		ShortcodeRegistry $registry,
		string $tag,
		array $config = []
	): static {
		$shortcode = $registry->get( $tag );

		if ( $shortcode === null ) {
			throw new \InvalidArgumentException( sprintf( __( "Shortcode '%s' not found in registry.", 'wp-jarvis' ), $tag ) );
		}

		$metadata = $shortcode->getMetadata();

		return new static(
			$registry,
			wpj_config( 'app.slug' ) . '_sc_' . $tag,
			array_merge( [
				'title'           => sprintf( __( 'Insert %s', wpj_config( 'app.textdomain', 'wp-jarvis' ) ), $metadata['title'] ),
				'singleShortcode' => $tag,
			], $config )
		);
	}
}
