<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Query;

/**
 * Builder - Fluent query builder for WP_Query.
 *
 * Provides a Laravel-like fluent interface for building WordPress queries.
 */
class Builder {
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
	 * Tax query conditions.
	 *
	 * @var array<array>
	 */
	private array $taxQuery = [];

	/**
	 * Date query conditions.
	 *
	 * @var array<array>
	 */
	private array $dateQuery = [];

	/**
	 * Create a new query builder instance.
	 *
	 * @param string|array $postType
	 */
	public function __construct( string|array $postType = 'post' ) {
		$this->args['post_type']      = $postType;
		$this->args['posts_per_page'] = - 1;
		$this->args['post_status']    = 'publish';
	}

	/**
	 * Create a new query builder.
	 *
	 * @param string|array $postType
	 *
	 * @return static
	 */
	public static function for( string|array $postType = 'post' ): static {
		return new static( $postType );
	}

	/**
	 * Set the post-type.
	 *
	 * @param string|array $postType
	 *
	 * @return static
	 */
	public function type( string|array $postType ): static {
		$this->args['post_type'] = $postType;

		return $this;
	}

	/**
	 * Set the post-status.
	 *
	 * @param string|array $status
	 *
	 * @return static
	 */
	public function status( string|array $status ): static {
		$this->args['post_status'] = $status;

		return $this;
	}

	/**
	 * Include all statuses.
	 *
	 * @return static
	 */
	public function anyStatus(): static {
		$this->args['post_status'] = 'any';

		return $this;
	}

	/**
	 * Set the number of posts per page.
	 *
	 * @param int $limit
	 *
	 * @return static
	 */
	public function limit( int $limit ): static {
		$this->args['posts_per_page'] = $limit;

		return $this;
	}

	/**
	 * Alias for limit.
	 *
	 * @param int $count
	 *
	 * @return static
	 */
	public function take( int $count ): static {
		return $this->limit( $count );
	}

	/**
	 * Set the page number.
	 *
	 * @param int $page
	 *
	 * @return static
	 */
	public function page( int $page ): static {
		$this->args['paged'] = $page;

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
	 * Skip a number of posts.
	 *
	 * @param int $count
	 *
	 * @return static
	 */
	public function skip( int $count ): static {
		return $this->offset( $count );
	}

	/**
	 * Order by a field.
	 *
	 * @param string $field
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderBy( string $field, string $direction = 'DESC' ): static {
		$this->args['orderby'] = $field;
		$this->args['order']   = strtoupper( $direction );

		return $this;
	}

	/**
	 * Order by date descending.
	 *
	 * @return static
	 */
	public function latest(): static {
		return $this->orderBy( 'date', 'DESC' );
	}

	/**
	 * Order by date ascending.
	 *
	 * @return static
	 */
	public function oldest(): static {
		return $this->orderBy( 'date', 'ASC' );
	}

	/**
	 * Order by menu order.
	 *
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderByMenuOrder( string $direction = 'ASC' ): static {
		return $this->orderBy( 'menu_order', $direction );
	}

	/**
	 * Order by meta-value.
	 *
	 * @param string $key
	 * @param string $direction
	 * @param string $type
	 *
	 * @return static
	 */
	public function orderByMeta( string $key, string $direction = 'DESC', string $type = 'CHAR' ): static {
		$this->args['meta_key'] = $key;
		$this->args['orderby']  = $type === 'NUMERIC' ? 'meta_value_num' : 'meta_value';
		$this->args['order']    = strtoupper( $direction );

		return $this;
	}

	/**
	 * Random order.
	 *
	 * @return static
	 */
	public function random(): static {
		$this->args['orderby'] = 'rand';

		return $this;
	}

	/**
	 * Filter by author.
	 *
	 * @param int|array $author
	 *
	 * @return static
	 */
	public function author( int|array $author ): static {
		if ( is_array( $author ) ) {
			$this->args['author__in'] = $author;
		} else {
			$this->args['author'] = $author;
		}

		return $this;
	}

	/**
	 * Exclude authors.
	 *
	 * @param array $authors
	 *
	 * @return static
	 */
	public function authorNotIn( array $authors ): static {
		$this->args['author__not_in'] = $authors;

		return $this;
	}

	/**
	 * Filter by specific post-IDs.
	 *
	 * @param array $ids
	 *
	 * @return static
	 */
	public function whereIn( array $ids ): static {
		$this->args['post__in'] = $ids;

		return $this;
	}

	/**
	 * Exclude specific post IDs.
	 *
	 * @param array $ids
	 *
	 * @return static
	 */
	public function whereNotIn( array $ids ): static {
		$this->args['post__not_in'] = $ids;

		return $this;
	}

	/**
	 * Filter by parent.
	 *
	 * @param int|array $parent
	 *
	 * @return static
	 */
	public function parent( int|array $parent ): static {
		if ( is_array( $parent ) ) {
			$this->args['post_parent__in'] = $parent;
		} else {
			$this->args['post_parent'] = $parent;
		}

		return $this;
	}

	/**
	 * Search posts.
	 *
	 * @param string $search
	 *
	 * @return static
	 */
	public function search( string $search ): static {
		$this->args['s'] = $search;

		return $this;
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
	 * Meta key exists.
	 *
	 * @param string $key
	 *
	 * @return static
	 */
	public function whereMetaExists( string $key ): static {
		$this->metaQuery[] = [
			'key'     => $key,
			'compare' => 'EXISTS',
		];

		return $this;
	}

	/**
	 * Meta-key does not exist.
	 *
	 * @param string $key
	 *
	 * @return static
	 */
	public function whereMetaNotExists( string $key ): static {
		$this->metaQuery[] = [
			'key'     => $key,
			'compare' => 'NOT EXISTS',
		];

		return $this;
	}

	/**
	 * Set meta query relation.
	 *
	 * @param string $relation
	 *
	 * @return static
	 */
	public function metaRelation( string $relation = 'AND' ): static {
		array_unshift( $this->metaQuery, [ 'relation' => strtoupper( $relation ) ] );

		return $this;
	}

	/**
	 * Add a taxonomy query condition.
	 *
	 * @param string $taxonomy
	 * @param int|string|array $terms
	 * @param string $field
	 * @param string $operator
	 *
	 * @return static
	 */
	public function whereTax(
		string $taxonomy,
		int|string|array $terms,
		string $field = 'term_id',
		string $operator = 'IN'
	): static {
		$this->taxQuery[] = [
			'taxonomy' => $taxonomy,
			'field'    => $field,
			'terms'    => $terms,
			'operator' => $operator,
		];

		return $this;
	}

	/**
	 * Filter by category.
	 *
	 * @param int|string|array $category
	 *
	 * @return static
	 */
	public function inCategory( int|string|array $category ): static {
		$field = is_numeric( $category ) || ( is_array( $category ) && isset( $category[0] ) && is_numeric( $category[0] ) )
			? 'term_id'
			: 'slug';

		return $this->whereTax( 'category', $category, $field );
	}

	/**
	 * Filter by tag.
	 *
	 * @param int|string|array $tag
	 *
	 * @return static
	 */
	public function hasTag( int|string|array $tag ): static {
		$field = is_numeric( $tag ) || ( is_array( $tag ) && isset( $tag[0] ) && is_numeric( $tag[0] ) )
			? 'term_id'
			: 'slug';

		return $this->whereTax( 'post_tag', $tag, $field );
	}

	/**
	 * Set tax query relation.
	 *
	 * @param string $relation
	 *
	 * @return static
	 */
	public function taxRelation( string $relation = 'AND' ): static {
		array_unshift( $this->taxQuery, [ 'relation' => strtoupper( $relation ) ] );

		return $this;
	}

	/**
	 * Add a date query condition.
	 *
	 * @param array $condition
	 *
	 * @return static
	 */
	public function whereDate( array $condition ): static {
		$this->dateQuery[] = $condition;

		return $this;
	}

	/**
	 * Posts from after a date.
	 *
	 * @param string $date
	 *
	 * @return static
	 */
	public function after( string $date ): static {
		$this->dateQuery[] = [ 'after' => $date ];

		return $this;
	}

	/**
	 * Posts from before a date.
	 *
	 * @param string $date
	 *
	 * @return static
	 */
	public function before( string $date ): static {
		$this->dateQuery[] = [ 'before' => $date ];

		return $this;
	}

	/**
	 * Posts from a specific year.
	 *
	 * @param int $year
	 *
	 * @return static
	 */
	public function year( int $year ): static {
		$this->args['year'] = $year;

		return $this;
	}

	/**
	 * Posts from a specific month.
	 *
	 * @param int $month
	 *
	 * @return static
	 */
	public function month( int $month ): static {
		$this->args['monthnum'] = $month;

		return $this;
	}

	/**
	 * Only return IDs.
	 *
	 * @return static
	 */
	public function onlyIds(): static {
		$this->args['fields'] = 'ids';

		return $this;
	}

	/**
	 * Enable caching.
	 *
	 * @return static
	 */
	public function cache(): static {
		$this->args['cache_results'] = true;

		return $this;
	}

	/**
	 * Disable caching.
	 *
	 * @return static
	 */
	public function noCache(): static {
		$this->args['cache_results']          = false;
		$this->args['update_post_meta_cache'] = false;
		$this->args['update_post_term_cache'] = false;

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

		if ( ! empty( $this->taxQuery ) ) {
			$args['tax_query'] = $this->taxQuery;
		}

		if ( ! empty( $this->dateQuery ) ) {
			$args['date_query'] = $this->dateQuery;
		}

		return $args;
	}

	/**
	 * Execute the query.
	 *
	 * @return \WP_Query
	 */
	public function query(): \WP_Query {
		return new \WP_Query( $this->toArgs() );
	}

	/**
	 * Get all matching posts.
	 *
	 * @return array<\WP_Post>
	 */
	public function get(): array {
		return $this->query()->posts;
	}

	/**
	 * Get the first matching post.
	 *
	 * @return \WP_Post|null
	 */
	public function first(): ?\WP_Post {
		$this->limit( 1 );
		$posts = $this->get();

		return $posts[0] ?? null;
	}

	/**
	 * Get the count of matching posts.
	 *
	 * @return int
	 */
	public function count(): int {
		$this->args['fields']         = 'ids';
		$this->args['posts_per_page'] = - 1;

		return $this->query()->found_posts;
	}

	/**
	 * Check if any posts exist.
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return $this->count() > 0;
	}

	/**
	 * Paginate results.
	 *
	 * @param int $perPage
	 * @param int $page
	 *
	 * @return array{items: array, total: int, per_page: int, current_page: int, total_pages: int}
	 */
	public function paginate( int $perPage = 10, int $page = 1 ): array {
		$this->limit( $perPage )->page( $page );
		$query = $this->query();

		return [
			'items'        => $query->posts,
			'total'        => $query->found_posts,
			'per_page'     => $perPage,
			'current_page' => $page,
			'total_pages'  => $query->max_num_pages,
		];
	}

	/**
	 * Chunk through results.
	 *
	 * @param int $count
	 * @param callable $callback
	 *
	 * @return bool
	 */
	public function chunk( int $count, callable $callback ): bool {
		$page = 1;

		do {
			$this->limit( $count )->page( $page );
			$query = $this->query();
			$posts = $query->posts;

			if ( empty( $posts ) ) {
				break;
			}

			if ( $callback( $posts, $page ) === false ) {
				return false;
			}

			$page ++;
		} while ( $query->max_num_pages >= $page );

		return true;
	}
}
