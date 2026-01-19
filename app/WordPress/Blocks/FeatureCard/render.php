<?php
/**
 * Block render template: Feature Card
 *
 * This file renders the Feature Card block on the frontend.
 *
 * @see https://developer.wordpress.org/block-editor/getting-started/fundamentals/render/
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 *
 * @package BraCalculator\App\WordPress\Blocks
 */

declare(strict_types=1);

// Extract attributes with defaults.
$heading = $attributes['heading'] ?? 'Feature Title';
$description = $attributes['description'] ?? 'Feature description goes here';
$showIcon = $attributes['showIcon'] ?? true;

// Get wrapper attributes with proper class handling.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'feature-card',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($showIcon): ?>
        <div class="feature-card__icon">
            <span class="dashicons dashicons-star-filled"></span>
        </div>
    <?php endif; ?>

    <div class="feature-card__content">
        <?php if (!empty($heading)): ?>
            <h3 class="feature-card__heading"><?php echo esc_html($heading); ?></h3>
        <?php endif; ?>

        <?php if (!empty($description)): ?>
            <p class="feature-card__description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($content)): ?>
        <div class="feature-card__inner">
            <?php echo $content; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .feature-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #0073aa;
    }

    .feature-card__icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0073aa;
        border-radius: 50%;
        color: #fff;
    }

    .feature-card__icon .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
    }

    .feature-card__content {
        flex: 1;
    }

    .feature-card__heading {
        margin: 0 0 0.5rem;
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e1e1e;
    }

    .feature-card__description {
        margin: 0;
        color: #555;
        line-height: 1.6;
    }
</style>