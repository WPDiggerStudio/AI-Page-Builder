<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Admin;



use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Columns - Manages admin list table columns.
 */

/**
 * Columns - Manages admin list table columns.
 */
class Columns {
	/**
	 * The post-type slug.
	 *
	 * @var string
	 */
	private string $postType;

	/**
	 * The columns to add.
	 *
	 * @var array<string, array{label: string, callback: callable}>
	 */
	private array $columns = [];

	/**
	 * The columns to remove.
	 *
	 * @var array<string>
	 */
	private array $remove = [];

	/**
	 * The sortable columns.
	 *
	 * @var array<string, array{meta_key: string, orderby: string}>
	 */
	private array $sortable = [];

	/**
	 * The column order configuration.
	 *
	 * @var array<string, array{position: string, reference: string}>
	 */
	private array $order = [];

	/**
	 * Create a new Columns instance.
	 *
	 * @param string $postType The post-type slug.
	 */
	public function __construct( string $postType ) {
		$this->postType = $postType;
	}

	/**
	 * Create a new Columns instance for a post-type.
	 *
	 * @param string $postType The post-type slug.
	 *
	 * @return static The new Columns instance.
	 */
	public static function for( string $postType ): static {
		return new static( $postType );
	}

	/**
	 * Add a custom column.
	 *
	 * @param string $key The column key.
	 * @param string $label The column label.
	 * @param callable $callback The render callback function.
	 *
	 * @return static The current instance for chaining.
	 */
	public function add( string $key, string $label, callable $callback ): static {
		$this->columns[ $key ] = [ 'label' => $label, 'callback' => $callback ];

		return $this;
	}

	/**
	 * Add a column after another column.
	 *
	 * @param string $afterColumn The column to insert after.
	 * @param string $key The new column key.
	 * @param string $label The column label.
	 * @param callable $callback The render callback function.
	 *
	 * @return static The current instance for chaining.
	 */
	public function after( string $afterColumn, string $key, string $label, callable $callback ): static {
		$this->add( $key, $label, $callback );
		$this->order[ $key ] = [ 'position' => 'after', 'reference' => $afterColumn ];

		return $this;
	}

	/**
	 * Add a column before another column.
	 *
	 * @param string $beforeColumn The column to insert before.
	 * @param string $key The new column key.
	 * @param string $label The column label.
	 * @param callable $callback The render callback function.
	 *
	 * @return static The current instance for chaining.
	 */
	public function before( string $beforeColumn, string $key, string $label, callable $callback ): static {
		$this->add( $key, $label, $callback );
		$this->order[ $key ] = [ 'position' => 'before', 'reference' => $beforeColumn ];

		return $this;
	}

	/**
	 * Add a thumbnail column.
	 *
	 * @param string $key The column key.
	 * @param string $label The column label.
	 * @param array|string $size The image size.
	 *
	 * @return static The current instance for chaining.
	 */
	public function thumbnail( string $key, string $label, array|string $size = 'thumbnail' ): static {
		return $this->add( $key, $label, function ( int $postId ) use ( $size ) {
			if ( has_post_thumbnail( $postId ) ) {
				echo get_the_post_thumbnail( $postId, $size );
			} else {
				echo '<span class="dashicons dashicons-format-image" style="color:#ccc;font-size:32px;"></span>';
			}
		} );
	}

	/**
	 * Add a meta value column.
	 *
	 * @param string $key The column key.
	 * @param string $label The column label.
	 * @param string $metaKey The meta-key to retrieve.
	 * @param string $default The default value.
	 *
	 * @return static The current instance for chaining.
	 */
	public function meta( string $key, string $label, string $metaKey, string $default = '—' ): static {
		return $this->add( $key, $label, function ( int $postId ) use ( $metaKey, $default ) {
			echo esc_html( get_post_meta( $postId, $metaKey, true ) ?: $default );
		} );
	}

	/**
	 * Add a boolean meta value column.
	 *
	 * @param string $key The column key.
	 * @param string $label The column label.
	 * @param string $metaKey The meta-key to retrieve.
	 * @param string $trueText The text for true values.
	 * @param string $falseText The text for false values.
	 *
	 * @return static The current instance for chaining.
	 */
	public function boolean( string $key, string $label, string $metaKey, string $trueText = '✓', string $falseText = '—' ): static {
		return $this->add( $key, $label, function ( int $postId ) use ( $metaKey, $trueText, $falseText ) {
			echo esc_html( get_post_meta( $postId, $metaKey, true ) ? $trueText : $falseText );
		} );
	}

	/**
	 * Add a taxonomy terms column.
	 *
	 * @param string $key The column key.
	 * @param string $label The column label.
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return static The current instance for chaining.
	 */
	public function taxonomy( string $key, string $label, string $taxonomy ): static {
		return $this->add( $key, $label, function ( int $postId ) use ( $taxonomy ) {
			$terms = get_the_terms( $postId, $taxonomy );
			echo $terms && ! is_wp_error( $terms ) ? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ) : '—';
		} );
	}

	/**
	 * Make a column sortable.
	 *
	 * @param string $key The column key.
	 * @param string $metaKey The meta-key to sort by.
	 * @param string $orderby The orderby type (meta_value or meta_value_num).
	 *
	 * @return static The current instance for chaining.
	 */
	public function sortable( string $key, string $metaKey, string $orderby = 'meta_value' ): static {
		$this->sortable[ $key ] = [ 'meta_key' => $metaKey, 'orderby' => $orderby ];

		return $this;
	}

	/**
	 * Remove a column.
	 *
	 * @param string $key The column key to remove.
	 *
	 * @return static The current instance for chaining.
	 */
	public function remove( string $key ): static {
		$this->remove[] = $key;

		return $this;
	}

	/**
	 * Register the columns with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		Hooks::filter( "manage_{$this->postType}_posts_columns", [ $this, 'filterColumns' ] );
		Hooks::action( "manage_{$this->postType}_posts_custom_column", [ $this, 'renderColumn' ], 10, 2 );

		if ( $this->sortable ) {
			Hooks::filter( "manage_edit-{$this->postType}_sortable_columns", [ $this, 'filterSortable' ] );
			Hooks::action( 'pre_get_posts', [ $this, 'handleSort' ] );
		}
	}

	/**
	 * Filter the admin columns.
	 *
	 * @param array<string, string> $columns The existing columns.
	 *
	 * @return array<string, string> The filtered columns.
	 */
	public function filterColumns( array $columns ): array {
		foreach ( $this->remove as $key ) {
			unset( $columns[ $key ] );
		}

		foreach ( $this->columns as $key => $config ) {
			if ( isset( $this->order[ $key ] ) ) {
				$columns = $this->insertColumn( $columns, $key, $config['label'], $this->order[ $key ] );
			} else {
				$columns[ $key ] = $config['label'];
			}
		}

		return $columns;
	}

	/**
	 * Insert a column at a specific position.
	 *
	 * @param array<string, string> $columns The existing columns.
	 * @param string $key The column key to insert.
	 * @param string $label The column label.
	 * @param array{position: string, reference: string} $order The position configuration.
	 *
	 * @return array<string, string> The modified columns array.
	 */
	protected function insertColumn( array $columns, string $key, string $label, array $order ): array {
		$new = [];
		foreach ( $columns as $colKey => $colLabel ) {
			if ( $order['position'] === 'before' && $colKey === $order['reference'] ) {
				$new[ $key ] = $label;
			}
			$new[ $colKey ] = $colLabel;
			if ( $order['position'] === 'after' && $colKey === $order['reference'] ) {
				$new[ $key ] = $label;
			}
		}

		return $new;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column The column key.
	 * @param int $postId The post-ID.
	 *
	 * @return void
	 */
	public function renderColumn( string $column, int $postId ): void {
		if ( isset( $this->columns[ $column ]['callback'] ) ) {
			call_user_func( $this->columns[ $column ]['callback'], $postId );
		}
	}

	/**
	 * Filter the sortable columns.
	 *
	 * @param array<string, string> $columns The existing sortable columns.
	 *
	 * @return array<string, string> The filtered sortable columns.
	 */
	public function filterSortable( array $columns ): array {
		foreach ( $this->sortable as $key => $config ) {
			$columns[ $key ] = $key;
		}

		return $columns;
	}

	/**
	 * Handle the sorting for custom columns.
	 *
	 * @param \WP_Query $query The WordPress query object.
	 *
	 * @return void
	 */
	public function handleSort( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderBy = $query->get( 'orderby' );
		if ( isset( $this->sortable[ $orderBy ] ) ) {
			$query->set( 'meta_key', $this->sortable[ $orderBy ]['meta_key'] );
			$query->set( 'orderby', $this->sortable[ $orderBy ]['orderby'] );
		}
	}
}
