<?php

declare(strict_types=1);

namespace BraCalculator\App\Listeners;

use BraCalculator\\App\Events\ProductPurchased;

/**
 * HandleProductPurchased Listener
 *
 * Handles the ProductPurchased event.
 *
 * @package BraCalculator\App\Listeners
 */
class HandleProductPurchased
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Inject dependencies here if needed
    }

    /**
     * Handle the event.
     *
     * @param ProductPurchased $productPurchased
     * @return void
     */
    public function handle(ProductPurchased $productPurchased): void
    {
        // Handle the event
        // Access event data: $productPurchased->data
        // Or use getter: $productPurchased->get('key')
    }

    /**
     * Register this listener.
     *
     * @return void
     */
    public static function register(): void
    {
        ProductPurchased::listen([new static(), 'handle']);
    }

    /**
     * Handle a job failure.
     *
     * @param ProductPurchased $productPurchased
     * @param \Throwable $exception
     * @return void
     */
    public function failed(ProductPurchased $productPurchased, \Throwable $exception): void
    {
        // Handle listener failure
        error_log(sprintf(
            '[%s] Failed to handle %s: %s',
            static::class,
            ProductPurchased::class,
            $exception->getMessage()
        ));
    }
}
