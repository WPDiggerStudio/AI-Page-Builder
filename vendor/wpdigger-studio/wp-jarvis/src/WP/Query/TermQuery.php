<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Query;

/**
 * TermQuery - Fluent query builder for get_terms/WP_Term_Query.
 *
 * Provides a Laravel-like interface for querying WordPress terms.
 */
class TermQuery {
	/**
	 * The query arguments.
	 *
	 * @var array<string, mixed>
	 */
	private array $args = [];

	/**
	 * Meta query conditions.
	 *
	 * @var array<array>
	 */
	private array $metaQuery = [];

	/**
	 * Create a new term query builder.
	 *
	 * @param string|array $taxonomy
	 */
	public function __construct( string|array $taxonomy = '' ) {
		if ( $taxonomy ) {
			$this->args['taxonomy'] = $taxonomy;
		}
		$this->args['hide_empty'] = false;
	}

	/**
	 * Create a new query for a taxonomy.
	 *
	 * @param string|array $taxonomy
	 *
	 * @return static
	 */
	public static function for( string|array $taxonomy ): static {
		return new static( $taxonomy );
	}

	/**
	 * Set the taxonomy.
	 *
	 * @param string|array $taxonomy
	 *
	 * @return static
	 */
	public function taxonomy( string|array $taxonomy ): static {
		$this->args['taxonomy'] = $taxonomy;

		return $this;
	}

	/**
	 * Hide empty terms.
	 *
	 * @param bool $hide
	 *
	 * @return static
	 */
	public function hideEmpty( bool $hide = true ): static {
		$this->args['hide_empty'] = $hide;

		return $this;
	}

	/**
	 * Only show terms with posts.
	 *
	 * @return static
	 */
	public function withPosts(): static {
		return $this->hideEmpty( true );
	}

	/**
	 * Filter by specific term IDs.
	 *
	 * @param array $ids
	 *
	 * @return static
	 */
	public function whereIn( array $ids ): static {
		$this->args['include'] = $ids;

		return $this;
	}

	/**
	 * Exclude specific term IDs.
	 *
	 * @param array $ids
	 *
	 * @return static
	 */
	public function whereNotIn( array $ids ): static {
		$this->args['exclude'] = $ids;

		return $this;
	}

	/**
	 * Filter by slug.
	 *
	 * @param string|array $slug
	 *
	 * @return static
	 */
	public function slug( string|array $slug ): static {
		$this->args['slug'] = $slug;

		return $this;
	}

	/**
	 * Filter by name.
	 *
	 * @param string|array $name
	 *
	 * @return static
	 */
	public function name( string|array $name ): static {
		$this->args['name'] = $name;

		return $this;
	}

	/**
	 * Search terms.
	 *
	 * @param string $search
	 *
	 * @return static
	 */
	public function search( string $search ): static {
		$this->args['search'] = $search;

		return $this;
	}

	/**
	 * Filter by parent.
	 *
	 * @param int $parent
	 *
	 * @return static
	 */
	public function parent( int $parent ): static {
		$this->args['parent'] = $parent;

		return $this;
	}

	/**
	 * Get only top-level terms.
	 *
	 * @return static
	 */
	public function topLevel(): static {
		return $this->parent( 0 );
	}

	/**
	 * Get child terms of a parent.
	 *
	 * @param int $parent
	 *
	 * @return static
	 */
	public function childOf( int $parent ): static {
		$this->args['child_of'] = $parent;

		return $this;
	}

	/**
	 * Set the number of terms to return.
	 *
	 * @param int $limit
	 *
	 * @return static
	 */
	public function limit( int $limit ): static {
		$this->args['number'] = $limit;

		return $this;
	}

	/**
	 * Set the offset.
	 *
	 * @param int $offset
	 *
	 * @return static
	 */
	public function offset( int $offset ): static {
		$this->args['offset'] = $offset;

		return $this;
	}

	/**
	 * Order by a field.
	 *
	 * @param string $field
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderBy( string $field, string $direction = 'ASC' ): static {
		$this->args['orderby'] = $field;
		$this->args['order']   = strtoupper( $direction );

		return $this;
	}

	/**
	 * Order by name.
	 *
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderByName( string $direction = 'ASC' ): static {
		return $this->orderBy( 'name', $direction );
	}

	/**
	 * Order by count.
	 *
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderByCount( string $direction = 'DESC' ): static {
		return $this->orderBy( 'count', $direction );
	}

	/**
	 * Order by term ID.
	 *
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderById( string $direction = 'ASC' ): static {
		return $this->orderBy( 'term_id', $direction );
	}

	/**
	 * Add a meta-query condition.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param string $compare
	 * @param string $type
	 *
	 * @return static
	 */
	public function whereMeta(
		string $key,
		mixed $value,
		string $compare = '=',
		string $type = 'CHAR'
	): static {
		$this->metaQuery[] = [
			'key'     => $key,
			'value'   => $value,
			'compare' => $compare,
			'type'    => $type,
		];

		return $this;
	}

	/**
	 * Only return term IDs.
	 *
	 * @return static
	 */
	public function onlyIds(): static {
		$this->args['fields'] = 'ids';

		return $this;
	}

	/**
	 * Return terms with specific fields.
	 *
	 * @param string $fields
	 *
	 * @return static
	 */
	public function fields( string $fields ): static {
		$this->args['fields'] = $fields;

		return $this;
	}

	/**
	 * Include term counts.
	 *
	 * @param bool $pad
	 *
	 * @return static
	 */
	public function padCounts( bool $pad = true ): static {
		$this->args['pad_counts'] = $pad;

		return $this;
	}

	/**
	 * Build the query arguments.
	 *
	 * @return array<string, mixed>
	 */
	public function toArgs(): array {
		$args = $this->args;

		if ( ! empty( $this->metaQuery ) ) {
			$args['meta_query'] = $this->metaQuery;
		}

		return $args;
	}

	/**
	 * Get all matching terms.
	 *
	 * @return array<\WP_Term>|\WP_Error
	 */
	public function get(): array|\WP_Error {
		return get_terms( $this->toArgs() );
	}

	/**
	 * Get the first matching term.
	 *
	 * @return \WP_Term|null
	 */
	public function first(): ?\WP_Term {
		$this->limit( 1 );

		$terms = $this->get();

		return ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? ( $terms[0] ?? null ) : null;
	}

	/**
	 * Get the count of matching terms.
	 *
	 * @return int
	 */
	public function count(): int {
		$this->args['fields'] = 'count';
		$count                = get_terms( $this->toArgs() );

		return is_numeric( $count ) ? (int) $count : 0;
	}

	/**
	 * Check if any terms exist.
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return $this->count() > 0;
	}

	/**
	 * Get terms as options array (id => name).
	 *
	 * @return array<int, string>
	 */
	public function asOptions(): array {
		$terms   = $this->get();
		$options = [];

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get terms as a hierarchical tree.
	 *
	 * @param int $parent
	 *
	 * @return array
	 */
	public function asTree( int $parent = 0 ): array {
		$this->parent( $parent );
		$terms = $this->get();

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$tree = [];
		foreach ( $terms as $term ) {
			$children = static::for( $term->taxonomy )->asTree( $term->term_id );
			$tree[]   = [
				'term'     => $term,
				'children' => $children,
			];
		}

		return $tree;
	}

	/**
	 * Find a term by ID.
	 *
	 * @param int $id
	 * @param string $taxonomy
	 *
	 * @return \WP_Term|null
	 */
	public static function find( int $id, string $taxonomy = '' ): ?\WP_Term {
		$term = get_term( $id, $taxonomy );

		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * Find a term by slug.
	 *
	 * @param string $slug
	 * @param string $taxonomy
	 *
	 * @return \WP_Term|null
	 */
	public static function findBySlug( string $slug, string $taxonomy ): ?\WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );

		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * Get terms for a post.
	 *
	 * @param int $postId
	 * @param string $taxonomy
	 *
	 * @return array<\WP_Term>
	 */
	public static function forPost( int $postId, string $taxonomy ): array {
		$terms = get_the_terms( $postId, $taxonomy );

		return is_array( $terms ) ? $terms : [];
	}
}
