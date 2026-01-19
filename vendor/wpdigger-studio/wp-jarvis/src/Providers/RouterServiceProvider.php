<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Routing\RestRouter;
use WPJarvis\Framework\Routing\AdminRouter;

/**
 * Router Service Provider
 *
 * Registers REST API routes and admin routes.
 *
 * @package WPJarvis\Framework\Providers
 */
class RouterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind REST Router
        $this->app->singleton('router.rest', function ($app) {
            return new RestRouter($app);
        });

        // Bind Admin Router
        $this->app->singleton('router.admin', function ($app) {
            return new AdminRouter($app);
        });

        // Bind default 'router' to RestRouter for Facade usage
        // Note: In standard Laravel, 'router' manages all routes.
        // Here, we default to REST as it maps closest to Route::get().
        $this->app->singleton('router', function ($app) {
            return $app->make('router.rest');
        });
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register REST API routes on 'rest_api_init'
        add_action('rest_api_init', function () {
            $this->loadApiRoutes();
        });

        // Register admin routes/menus on 'admin_init' or 'admin_menu'
        // Using 'admin_menu' is usually better for pages, 'admin_init' for AJAX
        add_action('admin_menu', function () {
            $this->loadAdminRoutes();
        });
    }

    /**
     * Load API routes from routes/api.php
     */
    protected function loadApiRoutes(): void
    {
        $file = $this->app->routesPath('api.php');
        if (file_exists($file)) {
            $router = $this->app->make('router.rest');
            // We can also bind $router to a variable for the file to use
            require $file;

            // After loading the file, register the collected routes
            $router->registerRoutes();
        }
    }

    /**
     * Load Admin routes from routes/admin.php
     */
    protected function loadAdminRoutes(): void
    {
        $file = $this->app->routesPath('admin.php');
        if (file_exists($file)) {
            // $router = $this->app->make('router.admin');
            require $file;
        }
    }
}
