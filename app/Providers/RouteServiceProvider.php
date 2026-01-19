<?php

declare(strict_types=1);

namespace BraCalculator\App\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * Route Service Provider
 *
 * Handles route registration and configuration.
 *
 * @package BraCalculator\App\Providers
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * REST API namespace.
     *
     * @var string
     */
    protected string $namespace = 'bra-calculator/v1';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutes();
    }

    /**
     * Load routes.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        // Load web routes
        $webRoutes = $this->app->routesPath('web.php');
        if (file_exists($webRoutes)) {
            require $webRoutes;
        }

        // Load admin routes
        $adminRoutes = $this->app->routesPath('admin.php');
        if (file_exists($adminRoutes)) {
            add_action('admin_init', function () use ($adminRoutes) {
                require $adminRoutes;
            });
        }

        // Load API routes
        $apiRoutes = $this->app->routesPath('api.php');
        if (file_exists($apiRoutes)) {
            add_action('rest_api_init', function () use ($apiRoutes) {
                require $apiRoutes;
            });
        }
    }
}
