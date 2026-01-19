<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\WP;

/**
 * PostType Interface
 *
 * Defines the contract for custom post types.
 */
interface PostType {
	/**
	 * Get the post type slug.
	 */
	public function getSlug(): string;

	/**
	 * Get the singular label.
	 */
	public function getSingularLabel(): string;

	/**
	 * Get the plural label.
	 */
	public function getPluralLabel(): string;

	/**
	 * Get all labels.
	 */
	public function getLabels(): array;

	/**
	 * Get the post type arguments.
	 */
	public function getArgs(): array;

	/**
	 * Register the post type.
	 */
	public function register(): \WP_Post_Type|\WP_Error;

	/**
	 * Unregister the post type.
	 */
	public function unregister(): bool;

	/**
	 * Check if the post type is registered.
	 */
	public function isRegistered(): bool;
}
