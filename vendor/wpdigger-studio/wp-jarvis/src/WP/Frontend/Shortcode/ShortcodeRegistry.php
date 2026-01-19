<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Frontend\Shortcode;

use Illuminate\Support\Collection;
use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * ShortcodeRegistry - Central registry for all shortcodes.
 *
 * Enables adapter layers (Elementor, WPBakery, TinyMCE) to discover
 * all registered shortcodes and their attribute schemas.
 *
 * @package WPJarvis\Framework\WP\Frontend\Shortcode
 */
class ShortcodeRegistry {
	/**
	 * Registered shortcode instances.
	 *
	 * @var Collection<string, AbstractShortcode>
	 */
	private Collection $shortcodes;

	/**
	 * Create a new registry.
	 */
	public function __construct() {
		$this->shortcodes = new Collection();
	}

	/**
	 * Register a shortcode.
	 *
	 * @param AbstractShortcode $shortcode Shortcode instance.
	 *
	 * @return void
	 */
	public function register( AbstractShortcode $shortcode ): void {
		$tag = $shortcode::getTag();

		if ( empty( $tag ) ) {
			return;
		}

		$this->shortcodes->put( $tag, $shortcode );

		/**
		 * Fires when a shortcode is added to the registry.
		 *
		 * @param AbstractShortcode $shortcode The shortcode instance.
		 * @param string $tag The shortcode tag.
		 */
		Hooks::doAction( 'shortcode_registry_add', $shortcode, $tag );
	}

	/**
	 * Get a shortcode by tag.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return AbstractShortcode|null
	 */
	public function get( string $tag ): ?AbstractShortcode {
		return $this->shortcodes->get( $tag );
	}

	/**
	 * Check if a shortcode is registered.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return bool
	 */
	public function has( string $tag ): bool {
		return $this->shortcodes->has( $tag );
	}

	/**
	 * Get all registered shortcodes.
	 *
	 * @return array<string, AbstractShortcode>
	 */
	public function all(): array {
		return $this->shortcodes->all();
	}

	/**
	 * Get all shortcode tags.
	 *
	 * @return array<string>
	 */
	public function tags(): array {
		return $this->shortcodes->keys()->all();
	}

	/**
	 * Unregister a shortcode.
	 *
	 * @param string $tag Shortcode tag.
	 *
	 * @return void
	 */
	public function unregister( string $tag ): void {
		if ( ! $this->has( $tag ) ) {
			return;
		}

		$shortcode = $this->shortcodes->get( $tag );
		$this->shortcodes->forget( $tag );

		/**
		 * Fires when a shortcode is removed from the registry.
		 *
		 * @param AbstractShortcode|null $shortcode The shortcode instance.
		 * @param string $tag The shortcode tag.
		 */
		Hooks::doAction( 'shortcode_registry_remove', $shortcode, $tag );
	}

	/**
	 * Get shortcodes grouped by category.
	 *
	 * @return array<string, array<AbstractShortcode>>
	 */
	public function groupByCategory(): array {
		$grouped = [];

		foreach ( $this->shortcodes as $shortcode ) {
			$metadata = $shortcode->getMetadata();
			$category = $metadata['category'] ?? 'general';

			if ( ! isset( $grouped[ $category ] ) ) {
				$grouped[ $category ] = [];
			}

			$grouped[ $category ][] = $shortcode;
		}

		return $grouped;
	}

	/**
	 * Get a flat config array for JavaScript/JSON export.
	 *
	 * Used by TinyMCE and other editor integrations.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function toConfig(): array {
		$config = [];

		foreach ( $this->shortcodes as $tag => $shortcode ) {
			$metadata = $shortcode->getMetadata();

			$config[ $tag ] = [
				'tag'          => $tag,
				'title'        => $metadata['title'] ?? $tag,
				'description'  => $metadata['description'] ?? '',
				'icon'         => $metadata['icon'] ?? 'shortcode',
				'category'     => $metadata['category'] ?? 'general',
				'allowContent' => $shortcode->getAllowContent(),
				'attributes'   => $shortcode->getAttributeSchema(),
			];
		}

		return $config;
	}

	/**
	 * Get count of registered shortcodes.
	 *
	 * @return int
	 */
	public function count(): int {
		return $this->shortcodes->count();
	}
}
