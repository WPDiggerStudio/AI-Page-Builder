<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Assets;

use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Enqueue - Manages script and style enqueueing.
 */

/**
 * Enqueue - Manages script and style enqueueing.
 */
class Enqueue {
	/**
	 * Registered scripts.
	 *
	 * @var array<string, array{handle: string, src: string, deps: array, ver: string|bool|null, inFooter: bool}>
	 */
	private static array $scripts = [];

	/**
	 * Registered styles.
	 *
	 * @var array<string, array{handle: string, src: string, deps: array, ver: string|bool|null, media: string}>
	 */
	private static array $styles = [];

	/**
	 * Inline scripts.
	 *
	 * @var array<string, array{script: string, position: string}>
	 */
	private static array $inlineScripts = [];

	/**
	 * Localized data for scripts.
	 *
	 * @var array<string, array{objectName: string, data: array}>
	 */
	private static array $localizedData = [];

	/**
	 * Register a script for enqueueing.
	 *
	 * @param string $handle The script handle.
	 * @param string $src The script source URL.
	 * @param array<string> $deps The script dependencies.
	 * @param string|bool|null $ver The script version.
	 * @param bool $inFooter Whether to enqueue in footer.
	 *
	 * @return void
	 */
	public static function script(
		string $handle,
		string $src,
		array $deps = [],
		string|bool|null $ver = null,
		bool $inFooter = true
	): void {
		self::$scripts[ $handle ] = compact( 'handle', 'src', 'deps', 'ver', 'inFooter' );
	}

	/**
	 * Register a style for enqueueing.
	 *
	 * @param string $handle The style handle.
	 * @param string $src The style source URL.
	 * @param array<string> $deps The style dependencies.
	 * @param string|bool|null $ver The style version.
	 * @param string $media The media query.
	 *
	 * @return void
	 */
	public static function style(
		string $handle,
		string $src,
		array $deps = [],
		string|bool|null $ver = null,
		string $media = 'all'
	): void {
		self::$styles[ $handle ] = compact( 'handle', 'src', 'deps', 'ver', 'media' );
	}

	/**
	 * Add an inline script.
	 *
	 * @param string $handle The script handle.
	 * @param string $script The inline script code.
	 * @param string $position The position (before or after).
	 *
	 * @return void
	 */
	public static function inline( string $handle, string $script, string $position = 'after' ): void {
		self::$inlineScripts[ $handle ] = compact( 'script', 'position' );
	}

	/**
	 * Localize a script with data.
	 *
	 * @param string $handle The script handle.
	 * @param string $objectName The JavaScript object name.
	 * @param array<string, mixed> $data The data to localize.
	 *
	 * @return void
	 */
	public static function localize( string $handle, string $objectName, array $data ): void {
		self::$localizedData[ $handle ] = compact( 'objectName', 'data' );
	}

	/**
	 * Register assets for frontend.
	 *
	 * @return void
	 */
	public static function registerFrontend(): void {
		Hooks::action( 'wp_enqueue_scripts', [ self::class, 'enqueueAll' ] );
	}

	/**
	 * Register assets for admin.
	 *
	 * @return void
	 */
	public static function registerAdmin(): void {
		Hooks::action( 'admin_enqueue_scripts', [ self::class, 'enqueueAll' ] );
	}

	/**
	 * Enqueue all registered assets.
	 *
	 * @return void
	 */
	public static function enqueueAll(): void {
		foreach ( self::$styles as $style ) {
			wp_enqueue_style( $style['handle'], $style['src'], $style['deps'], $style['ver'], $style['media'] );
		}

		foreach ( self::$scripts as $script ) {
			wp_enqueue_script( $script['handle'], $script['src'], $script['deps'], $script['ver'], $script['inFooter'] );

			if ( isset( self::$localizedData[ $script['handle'] ] ) ) {
				$localized = self::$localizedData[ $script['handle'] ];
				wp_localize_script( $script['handle'], $localized['objectName'], $localized['data'] );
			}

			if ( isset( self::$inlineScripts[ $script['handle'] ] ) ) {
				$inline = self::$inlineScripts[ $script['handle'] ];
				wp_add_inline_script( $script['handle'], $inline['script'], $inline['position'] );
			}
		}
	}

	/**
	 * Load a script asynchronously.
	 *
	 * @param string $handle The script handle.
	 *
	 * @return void
	 */
	public static function async( string $handle ): void {
		Hooks::filter( 'script_loader_tag', static function ( $tag, $tagHandle ) use ( $handle ) {
			return $tagHandle === $handle ? str_replace( ' src', ' async src', $tag ) : $tag;
		}, 10, 2 );
	}

	/**
	 * Defer a script.
	 *
	 * @param string $handle The script handle.
	 *
	 * @return void
	 */
	public static function defer( string $handle ): void {
		Hooks::filter( 'script_loader_tag', static function ( $tag, $tagHandle ) use ( $handle ) {
			return $tagHandle === $handle ? str_replace( ' src', ' defer src', $tag ) : $tag;
		}, 10, 2 );
	}

	/**
	 * Clear all registered assets.
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$scripts       = [];
		self::$styles        = [];
		self::$inlineScripts = [];
		self::$localizedData = [];
	}
}
