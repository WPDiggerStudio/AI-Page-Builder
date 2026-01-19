<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Metaboxes;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Content\Metabox as MetaboxBuilder;

/**
 * TestMeta Metabox
 *
 * Manages Test Meta metabox for post-editing.
 *
 * @package BraCalculator\App\WordPress\Metaboxes
 */
class TestMeta {
	/**
	 * The metabox ID.
	 */
	public const ID = 'test_meta';

	/**
	 * The metabox title.
	 */
	public const TITLE = 'Test Meta';

	/**
	 * Post-types to attach to.
	 */
	public const POST_TYPES = [ 'test_article' ];

	/**
	 * The metabox builder instance.
	 *
	 * @var MetaboxBuilder|null
	 */
	private ?MetaboxBuilder $metabox = null;

	/**
	 * Register metabox.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function register(): void {
		$this->metabox = MetaboxBuilder::make( self::ID, __( self::TITLE, 'bra-calculator' ) )
		                               ->forPostTypes( ...self::POST_TYPES )
		                               ->context( 'normal' )
		                               ->priority( 'default' )
			// Define your fields here using the fluent API:
			                           ->field( 'text', 'subtitle', __( 'Subtitle', 'bra-calculator' ), [
				'description' => __( 'Enter a subtitle for this content.', 'bra-calculator' ),
			] )
		                               ->field( 'textarea', 'description', __( 'Description', 'bra-calculator' ), [
			                               'description' => __( 'Enter a detailed description.', 'bra-calculator' ),
		                               ] )
		                               ->field( 'select', 'status', __( 'Status', 'bra-calculator' ), [
			                               'options' => [
				                               'active'   => __( 'Active', 'bra-calculator' ),
				                               'inactive' => __( 'Inactive', 'bra-calculator' ),
			                               ],
		                               ] )
		                               ->field( 'checkbox', 'featured', __( 'Featured', 'bra-calculator' ) );

		// Hook registration to add_meta_boxes action
		Hooks::action( 'add_meta_boxes', [ $this, 'registerMetabox' ] );
	}

	/**
	 * Register metabox with WordPress.
	 *
	 * @return void
	 */
	public function registerMetabox(): void {
		$this->metabox?->register();
	}

	/**
	 * Set a field value for a post.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 * @param mixed $default Default value.
	 *
	 * @return mixed Field value.
	 */
	public static function get( int $postId, string $key, mixed $default = null ): mixed {
		$value = get_post_meta( $postId, '_' . $key, true );

		return $value !== '' ? $value : $default;
	}

	/**
	 * Set a field value for a post.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 * @param mixed $value Value to set.
	 *
	 * @return bool|int Meta-ID on success, false on failure.
	 */
	public static function set( int $postId, string $key, mixed $value ): bool|int {
		return update_post_meta( $postId, '_' . $key, $value );
	}

	/**
	 * Delete field value for a post.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete( int $postId, string $key ): bool {
		return delete_post_meta( $postId, '_' . $key );
	}

	/**
	 * Check if a field has a truthy value.
	 *
	 * @param int $postId Post ID.
	 * @param string $key Field key.
	 *
	 * @return bool True if value is truthy.
	 */
	public static function isTrue( int $postId, string $key ): bool {
		return (bool) self::get( $postId, $key, false );
	}

	/**
	 * Get multiple field values for a post.
	 *
	 * @param int $postId Post ID.
	 * @param array<string> $keys Field keys to retrieve.
	 *
	 * @return array<string, mixed> Array of field values.
	 */
	public static function getMany( int $postId, array $keys ): array {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = self::get( $postId, $key );
		}

		return $values;
	}

	/**
	 * Set multiple field values for a post.
	 *
	 * @param int $postId Post ID.
	 * @param array<string, mixed> $data Key-value pairs to set.
	 *
	 * @return void
	 */
	public static function setMany( int $postId, array $data ): void {
		foreach ( $data as $key => $value ) {
			self::set( $postId, $key, $value );
		}
	}

	/**
	 * Get the metabox builder instance.
	 *
	 * @return MetaboxBuilder|null The metabox builder instance.
	 */
	public function getMetabox(): ?MetaboxBuilder {
		return $this->metabox;
	}

	/**
	 * Get all fields configuration.
	 *
	 * @return array<string, array<string, mixed>> All fields configuration.
	 */
	public function getFields(): array {
		return $this->metabox?->getFields() ?? [];
	}
}
