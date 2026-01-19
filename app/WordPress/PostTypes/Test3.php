<?php

declare(strict_types=1);

namespace BraCalculator\App\WordPress\PostTypes;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Content\PostType as PostTypeBuilder;

/**
 * Test3 post-type
 *
 * Registers and manages Test3 custom post-type.
 *
 * @package BraCalculator\App\WordPress\PostTypes
 */
class Test3
{
    /**
     * The post-type slug.
     */
    public const SLUG = 'test3';

    /**
     * The singular label.
     */
    public const SINGULAR = 'Test3';

    /**
     * The plural label.
     */
    public const PLURAL = 'Test3s';

    /**
     * Register post-type.
     *
     * @return void
     */
    public function register(): void
    {
        Hooks::action('init', [$this, 'registerPostType']);
    }

    /**
     * Register post-type with WordPress.
     *
     * @return void
     */
    public function registerPostType(): void
    {
        PostTypeBuilder::make(self::SLUG)
            ->labels(self::SINGULAR, self::PLURAL)
            ->icon('dashicons-admin-post')
            ->supports(['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'])
            ->public()
            ->hasArchive()
            ->showInRest()
            ->rewrite(['slug' => self::SLUG, 'with_front' => false])
            ->capability('post')
            ->register();
    }

    /**
     * Get post-type slug.
     *
     * @return string post-type slug.
     */
    public static function slug(): string
    {
        return self::SLUG;
    }

    /**
     * Query posts of this type.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return \WP_Query Query object.
     */
    public static function query(array $args = []): \WP_Query
    {
        return new \WP_Query(array_merge([
            'post_type' => self::SLUG,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ], $args));
    }

    /**
     * Get a single post by ID.
     *
     * @param int $id Post ID.
     * @return \WP_Post|null Post object or null if not found.
     */
    public static function find(int $id): ?\WP_Post
    {
        $post = get_post($id);

        if ($post && $post->post_type === self::SLUG) {
            return $post;
        }

        return null;
    }

    /**
     * Get all published posts.
     *
     * @param int $limit Number of posts to retrieve. Use -1 for all.
     * @return array<\WP_Post> Array of post-objects.
     */
    public static function all(int $limit = -1): array
    {
        return self::query(['posts_per_page' => $limit])->posts;
    }

    /**
     * Get recent posts.
     *
     * @param int $limit Number of posts to retrieve.
     * @return array<\WP_Post> Array of post-objects.
     */
    public static function recent(int $limit = 5): array
    {
        return self::query([
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ])->posts;
    }

    /**
     * Check if a post exists.
     *
     * @param int $id Post ID.
     * @return bool True if the post exists.
     */
    public static function exists(int $id): bool
    {
        return self::find($id) !== null;
    }

    /**
     * Get post-meta value.
     *
     * @param int $postId Post ID.
     * @param string $key Meta key.
     * @param mixed $default Default value to return if meta doesn't exist.
     * @return mixed Meta value or default.
     */
    public static function getMeta(int $postId, string $key, mixed $default = null): mixed
    {
        $value = get_post_meta($postId, "_{$key}", true);
        return $value !== '' ? $value : $default;
    }

    /**
     * Set post-meta value.
     *
     * @param int $postId Post ID.
     * @param string $key Meta key.
     * @param mixed $value Meta value.
     * @return bool|int Meta-ID on success, false on failure.
     */
    public static function setMeta(int $postId, string $key, mixed $value): bool|int
    {
        return update_post_meta($postId, "_{$key}", $value);
    }

    /**
     * Delete post meta value.
     *
     * @param int $postId Post ID.
     * @param string $key Meta key.
     * @return bool True on success, false on failure.
     */
    public static function deleteMeta(int $postId, string $key): bool
    {
        return delete_post_meta($postId, "_{$key}");
    }

    /**
     * Get archive URL.
     *
     * @return string|false Archive URL or false on failure.
     */
    public static function archiveUrl(): string|false
    {
        return get_post_type_archive_link(self::SLUG);
    }

    /**
     * Get posts count.
     *
     * @param string $status Post status (default: 'publish').
     * @return int Number of posts with a given status.
     */
    public static function count(string $status = 'publish'): int
    {
        $counts = wp_count_posts(self::SLUG);
        return (int) ($counts->$status ?? 0);
    }

    /**
     * Create a new post.
     *
     * @param array<string, mixed> $data Post data.
     * @return int|\WP_Error Post ID on success, WP_Error on failure.
     */
    public static function create(array $data): int|\WP_Error
    {
        $defaults = [
            'post_type' => self::SLUG,
            'post_status' => 'publish',
            'post_title' => '',
            'post_content' => '',
        ];

        $data = wp_parse_args($data, $defaults);

        return wp_insert_post($data);
    }

    /**
     * Update an existing post.
     *
     * @param int $postId Post ID.
     * @param array<string, mixed> $data Post data to update.
     * @return int|\WP_Error Post ID on success, WP_Error on failure.
     */
    public static function update(int $postId, array $data): int|\WP_Error
    {
        $data['ID'] = $postId;
        return wp_update_post($data);
    }

    /**
     * Delete a post.
     *
     * @param int $postId Post ID.
     * @param bool $force Whether to bypass trash and force deletion.
     * @return bool|\WP_Post False on failure, post an object on success.
     */
    public static function delete(int $postId, bool $force = false): bool|\WP_Post
    {
        return wp_delete_post($postId, $force);
    }
}
