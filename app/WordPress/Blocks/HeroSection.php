<?php

declare(strict_types=1);

namespace BraCalculator\App\WordPress\Blocks;

/**
 * HeroSection Block
 *
 * A dynamic Gutenberg block for Hero Section.
 * Uses block.json for metadata and render.php for frontend output.
 *
 * @see https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/
 * @package BraCalculator\App\WordPress\Blocks
 */
class HeroSection
{
    /**
     * Block directory path.
     *
     * @var string
     */
    public const BLOCK_DIR = __DIR__ . '/HeroSection';

    /**
     * Register the block with WordPress.
     *
     * Uses block.json as the source of truth for block metadata.
     * The render callback is specified in block.json via "render": "file:./render.php"
     * or can be overridden here for more complex logic.
     *
     * @return void
     */
    public function register(): void
    {
        register_block_type(self::BLOCK_DIR);
    }

    /**
     * Get the block directory path.
     *
     * @return string Absolute path to the block directory.
     */
    public static function getBlockPath(): string
    {
        return self::BLOCK_DIR;
    }

    /**
     * Get the block directory URL.
     *
     * @return string URL to the block directory.
     */
    public static function getBlockUrl(): string
    {
        return plugins_url(basename(self::BLOCK_DIR), self::BLOCK_DIR . '/block.json');
    }

    /**
     * Enqueue additional block assets if needed.
     *
     * This method is optional. Use it for assets not defined in block.json.
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        // Example: Enqueue additional scripts or styles
        // wp_enqueue_style(
        //     'hero-section-custom',
        //     self::getBlockUrl() . '/custom.css',
        //     [],
        //     '1.0.0'
        // );
    }
}
