<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields;

use Illuminate\Support\Collection;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldInterface;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;

/**
 * Field Registry
 *
 * Central registry for all field types. Provides registration,
 * retrieval, and validation of field type handlers.
 *
 * @package WPJarvis\Framework\WP\Fields
 */
class Registry implements FieldRegistryInterface {
	/**
	 * Registered field types.
	 *
	 * @var Collection<string, class-string<FieldInterface>>
	 */
	private Collection $fieldTypes;

	/**
	 * Create a new registry instance.
	 */
	public function __construct() {
		$this->fieldTypes = new Collection();
	}

	/**
	 * Register a field type.
	 *
	 * @param string $type Field type identifier.
	 * @param class-string<FieldInterface> $class Field handler class.
	 *
	 * @return void
	 */
	public function register( string $type, string $class ): void {
		$this->fieldTypes->put( $type, $class );

		/**
		 * Fires action when a field type is registered.
		 *
		 * @param string $type Field type identifier.
		 * @param class-string<FieldInterface> $class Field handler class.
		 */
		Hooks::doAction( 'field_registered', $type, $class );
	}

	/**
	 * Get a field type class.
	 *
	 * @param string $type Field type identifier.
	 *
	 * @return string|null Field class name or null if not found.
	 */
	public function get( string $type ): ?string {
		$handler = $this->fieldTypes->get( $type );

		if ( $handler === null || ! class_exists( $handler ) ) {
			return null;
		}

		return $handler;
	}

	/**
	 * Check if a field type is registered.
	 *
	 * @param string $type Field type identifier.
	 *
	 * @return bool True if a field type is registered.
	 */
	public function has( string $type ): bool {
		return $this->fieldTypes->has( $type );
	}

	/**
	 * Get all registered field types.
	 *
	 * @return array<string, class-string<FieldInterface>> All registered field types.
	 */
	public function all(): array {
		return $this->fieldTypes->toArray();
	}

	/**
	 * Unregister a field type.
	 *
	 * @param string $type Field type identifier.
	 *
	 * @return void
	 */
	public function unregister( string $type ): void {
		if ( ! $this->has( $type ) ) {
			return;
		}

		$this->fieldTypes->forget( $type );

		/**
		 * Fires action when a field type is unregistered.
		 *
		 * @param string $type Field type identifier.
		 */
		Hooks::doAction( 'field_unregistered', $type );
	}

	/**
	 * Create a field instance.
	 *
	 * @param string $type Field type identifier.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return FieldInterface Field instance.
	 * @throws \InvalidArgumentException If a field type is not registered.
	 */
	public function create( string $type, array $config = [] ): FieldInterface {
		$handler = $this->get( $type );

		if ( $handler === null ) {
			throw new \InvalidArgumentException( sprintf( __( 'Field type "%s" is not registered.', 'wp-jarvis' ), $type ) );
		}

		return new $handler();
	}
}
