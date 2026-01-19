<?php

declare(strict_types=1);

namespace BraCalculator\App\Tasks;

use WPJarvis\Framework\WP\Scheduling\Task;

/**
 * SyncProductInventory
 *
 * A scheduled task that syncs product inventory from an external API.
 * Runs every 5 minutes to keep inventory levels up-to-date.
 *
 * @package BraCalculator\App\Tasks
 */
class SyncProductInventory extends Task
{
    /**
     * The task name.
     */
    protected string $name = 'sync-product-inventory';

    /**
     * The task description.
     */
    protected string $description = 'Sync Product Inventory Task';

    /**
     * API endpoint for inventory data.
     */
    protected string $apiEndpoint = 'https://api.example.com/inventory';

    /**
     * Create a new task instance.
     */
    public function __construct()
    {
        // Run every 5 minutes
        $this->everyFiveMinutes();

        // Prevent overlapping - important for API sync tasks
        $this->withoutOverlappingUsing(300);

        // Run in background to not block cron
        $this->runInBackground();
    }

    /**
     * Execute the scheduled task.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handle(): mixed
    {
        $this->log('Starting inventory sync...');

        try {
            $result = $this->execute();
            $this->log("Inventory sync completed. Updated {$result['updated']} products, {$result['errors']} errors.");
            return $result;
        } catch (\Throwable $e) {
            $this->log('Inventory sync failed: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Execute the main task logic.
     *
     * @return array{updated: int, errors: int, skipped: int}
     */
    protected function execute(): array
    {
        $stats = [
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0,
        ];

        // Get inventory data from external API
        $inventoryData = $this->fetchInventoryData();

        if (empty($inventoryData)) {
            $this->log('No inventory data received from API', 'warning');
            return $stats;
        }

        foreach ($inventoryData as $item) {
            try {
                $result = $this->updateProductInventory($item);

                if ($result === true) {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->log("Failed to update SKU {$item['sku']}: {$e->getMessage()}", 'error');
            }
        }

        // Fire action for other plugins to hook into
        do_action('wpjarvis_inventory_synced', $stats);

        return $stats;
    }

    /**
     * Fetch inventory data from external API.
     *
     * @return array<int, array{sku: string, quantity: int, price?: float}>
     */
    protected function fetchInventoryData(): array
    {
        // Get API credentials from options
        $apiKey = get_option('bra_calculator_api_key', '');

        if (empty($apiKey)) {
            $this->log('API key not configured, skipping sync', 'warning');
            return [];
        }

        $response = wp_remote_get($this->apiEndpoint, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode !== 200) {
            throw new \Exception("API returned status code: {$statusCode}");
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse API response: ' . json_last_error_msg());
        }

        return $data['products'] ?? [];
    }

    /**
     * Update a single product's inventory.
     *
     * @param array{sku: string, quantity: int, price?: float} $item Inventory item data.
     *
     * @return bool True if updated, false if skipped.
     */
    protected function updateProductInventory(array $item): bool
    {
        // Find product by SKU (WooCommerce example)
        $productId = wc_get_product_id_by_sku($item['sku'] ?? '');

        if (!$productId) {
            return false; // Product not found, skip
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Update stock quantity
        if (isset($item['quantity'])) {
            $product->set_stock_quantity($item['quantity']);
            $product->set_stock_status($item['quantity'] > 0 ? 'instock' : 'outofstock');
        }

        // Update price if provided
        if (isset($item['price'])) {
            $product->set_regular_price((string) $item['price']);
        }

        $product->save();

        return true;
    }

    /**
     * Log a message.
     *
     * @param string $message Message to log.
     * @param string $level Log level (info, error, warning).
     */
    protected function log(string $message, string $level = 'info'): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[%s] [%s] %s', strtoupper($level), $this->name, $message));
        }

        do_action('wpjarvis_task_log', $this->name, $message, $level);
    }

    /**
     * Handle task failure.
     *
     * @param \Throwable $e The exception that caused the failure.
     */
    public function handleFailure(\Throwable $e): void
    {
        parent::handleFailure($e);

        // Log failure to custom table or external service
        do_action('wpjarvis_inventory_sync_failed', $e, $this->name);
    }
}
