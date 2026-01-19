<?php
/**
 * Block render template.
 *
 * This file is used to render the block on the frontend.
 *
 * @see https://developer.wordpress.org/block-editor/getting-started/fundamentals/render/
 *
 * The following variables are available:
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package BraCalculator\App\WordPress\Blocks
 */

declare(strict_types=1);

// Get wrapper attributes with proper class handling.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'hero-section',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <p><?php esc_html_e('Hero Section – Edit this template.', 'bra-calculator'); ?></p>
</div>
