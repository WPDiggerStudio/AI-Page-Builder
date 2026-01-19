<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Contracts;

/**
 * Field Registry Interface
 *
 * Defines the contract for field type registration and retrieval.
 *
 * @package WPJarvis\Framework\WP\Fields\Contracts
 */
interface FieldRegistryInterface {
	/**
	 * Register a field type.
	 *
	 * @param string $type Field type identifier.
	 * @param class-string<FieldInterface> $class Field class name.
	 *
	 * @return void
	 */
	public function register( string $type, string $class ): void;

	/**
	 * Get a field type class.
	 *
	 * @param string $type Field type identifier.
	 *
	 * @return class-string<FieldInterface>|null Field class name or null if not registered.
	 */
	public function get( string $type ): ?string;

	/**
	 * Check if a field type is registered.
	 *
	 * @param string $type Field type identifier.
	 *
	 * @return bool True if registered, false otherwise.
	 */
	public function has( string $type ): bool;

	/**
	 * Get all registered field types.
	 *
	 * @return array<string, class-string<FieldInterface>> Array of a field type => class name.
	 */
	public function all(): array;

	/**
	 * Unregister a field type.
	 *
	 * @param string $type Field type identifier.
	 *
	 * @return void
	 */
	public function unregister( string $type ): void;

	/**
	 * Create a field instance.
	 *
	 * @param string $type Field type identifier.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return FieldInterface Field instance.
	 * @throws \InvalidArgumentException If a field type is not registered.
	 */
	public function create( string $type, array $config = [] ): FieldInterface;
}
