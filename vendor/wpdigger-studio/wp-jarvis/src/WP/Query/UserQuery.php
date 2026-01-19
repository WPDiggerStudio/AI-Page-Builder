<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Query;

/**
 * UserQuery - Fluent query builder for WP_User_Query.
 *
 * Provides a Laravel-like interface for querying WordPress users.
 */
class UserQuery {
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
	 * Create a new user query builder.
	 */
	public function __construct() {
		$this->args['number'] = - 1;
	}

	/**
	 * Create a new query instance.
	 *
	 * @return static The new query instance.
	 */
	public static function make(): static {
		return new static();
	}

	/**
	 * Filter by role.
	 *
	 * @param string|array $role
	 *
	 * @return static
	 */
	public function role( string|array $role ): static {
		if ( is_array( $role ) ) {
			$this->args['role__in'] = $role;
		} else {
			$this->args['role'] = $role;
		}

		return $this;
	}

	/**
	 * Exclude roles.
	 *
	 * @param array $roles
	 *
	 * @return static
	 */
	public function roleNotIn( array $roles ): static {
		$this->args['role__not_in'] = $roles;

		return $this;
	}

	/**
	 * Filter by specific user IDs.
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
	 * Exclude specific user IDs.
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
	 * Search users.
	 *
	 * @param string $search
	 * @param array $columns
	 *
	 * @return static
	 */
	public function search( string $search, array $columns = [ 'user_login', 'user_email', 'display_name' ] ): static {
		$this->args['search']         = '*' . $search . '*';
		$this->args['search_columns'] = $columns;

		return $this;
	}

	/**
	 * Filter by email.
	 *
	 * @param string $email
	 *
	 * @return static
	 */
	public function email( string $email ): static {
		$this->args['search']         = $email;
		$this->args['search_columns'] = [ 'user_email' ];

		return $this;
	}

	/**
	 * Filter by login.
	 *
	 * @param string $login
	 *
	 * @return static
	 */
	public function login( string $login ): static {
		$this->args['login'] = $login;

		return $this;
	}

	/**
	 * Filter by nicename.
	 *
	 * @param string $nicename
	 *
	 * @return static
	 */
	public function nicename( string $nicename ): static {
		$this->args['nicename'] = $nicename;

		return $this;
	}

	/**
	 * Set the number of users to return.
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
	 * Order by registration date.
	 *
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderByRegistration( string $direction = 'DESC' ): static {
		return $this->orderBy( 'registered', $direction );
	}

	/**
	 * Order by display name.
	 *
	 * @param string $direction
	 *
	 * @return static
	 */
	public function orderByName( string $direction = 'ASC' ): static {
		return $this->orderBy( 'display_name', $direction );
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
	 * Check if meta-key exists.
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
	 * Only return user IDs.
	 *
	 * @return static
	 */
	public function onlyIds(): static {
		$this->args['fields'] = 'ID';

		return $this;
	}

	/**
	 * Filter users who have published posts.
	 *
	 * @param string $postType
	 *
	 * @return static
	 */
	public function hasPublishedPosts( string $postType = 'post' ): static {
		$this->args['has_published_posts'] = $postType;

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
	 * Execute the query.
	 *
	 * @return \WP_User_Query
	 */
	public function query(): \WP_User_Query {
		return new \WP_User_Query( $this->toArgs() );
	}

	/**
	 * Get all matching users.
	 *
	 * @return array<\WP_User>
	 */
	public function get(): array {
		return $this->query()->get_results();
	}

	/**
	 * Get the first matching user.
	 *
	 * @return \WP_User|null
	 */
	public function first(): ?\WP_User {
		$this->limit( 1 );
		$users = $this->get();

		return $users[0] ?? null;
	}

	/**
	 * Get the count of matching users.
	 *
	 * @return int
	 */
	public function count(): int {
		$this->args['count_total'] = true;

		return $this->query()->get_total();
	}

	/**
	 * Check if any users exist.
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
		$this->args['count_total'] = true;

		$query = $this->query();
		$total = $query->get_total();

		return [
			'items'        => $query->get_results(),
			'total'        => $total,
			'per_page'     => $perPage,
			'current_page' => $page,
			'total_pages'  => (int) ceil( $total / $perPage ),
		];
	}

	/**
	 * Get user by ID.
	 *
	 * @param int $id
	 *
	 * @return \WP_User|null
	 */
	public static function find( int $id ): ?\WP_User {
		$user = get_user_by( 'id', $id );

		return $user instanceof \WP_User ? $user : null;
	}

	/**
	 * Get user by email.
	 *
	 * @param string $email
	 *
	 * @return \WP_User|null
	 */
	public static function findByEmail( string $email ): ?\WP_User {
		$user = get_user_by( 'email', $email );

		return $user instanceof \WP_User ? $user : null;
	}

	/**
	 * Get user by login.
	 *
	 * @param string $login
	 *
	 * @return \WP_User|null
	 */
	public static function findByLogin( string $login ): ?\WP_User {
		$user = get_user_by( 'login', $login );

		return $user instanceof \WP_User ? $user : null;
	}
}
