<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\WP;

/**
 * Taxonomy Interface
 *
 * Defines the contract for custom taxonomies.
 */
interface Taxonomy {
	/**
	 * Get the taxonomy slug.
	 */
	public function getSlug(): string;

	/**
	 * Get the associated post types.
	 */
	public function getPostTypes(): array;

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
	 * Get the taxonomy arguments.
	 */
	public function getArgs(): array;

	/**
	 * Check if taxonomy is hierarchical.
	 */
	public function isHierarchical(): bool;

	/**
	 * Register the taxonomy.
	 */
	public function register(): \WP_Taxonomy|\WP_Error;

	/**
	 * Unregister the taxonomy.
	 */
	public function unregister(): bool;

	/**
	 * Check if the taxonomy is registered.
	 */
	public function isRegistered(): bool;
}
