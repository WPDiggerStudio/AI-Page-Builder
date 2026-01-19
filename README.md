# WP Jarvis

A Laravel-first application framework for WordPress. Build powerful WordPress plugins with Laravel's elegance and power.

## Features

- **Laravel Service Container** - Full IoC container with dependency injection
- **Service Providers** - Modular service registration and bootstrapping
- **Fluent Builders** - Easy APIs for CPTs, taxonomies, metaboxes, settings, menus, and more
- **Blade Views** - Laravel's powerful templating engine
- **Validation** - Laravel-style validation with WordPress-specific rules
- **Events & Listeners** - Decouple your application logic
- **CLI Commands** - Generate boilerplate with Artisan-like commands
- **React & Vue Support** - First-class frontend framework integration

## Requirements

- PHP 8.1+
- WordPress 6.0+
- Composer

## Installation

### 1. Clone or Download

```bash
cd wp-content/plugins
git clone https://github.com/wpjarvis/wp-jarvis.git
```

### 2. Install Dependencies

```bash
cd wp-jarvis
composer install
```

### 3. Setup Environment (Optional)

```bash
cp .env.example .env
```

### 4. Install Frontend Dependencies (Optional)

```bash
npm install
npm run build
```

### 5. Activate the Plugin

Go to WordPress Admin > Plugins and activate "WP Jarvis".

## Directory Structure

```
wp-jarvis/
├── app/                    # Application code
│   ├── Modules/           # Feature modules
│   │   └── Portfolio/     # Example module
│   ├── Providers/         # Service providers
│   ├── helpers.php        # App helper functions
│   └── Plugin.php         # Main plugin class
├── bootstrap/             # Bootstrap files
│   └── cache/             # Cached config
├── config/                # Configuration files
│   ├── app.php
│   ├── database.php
│   └── requirements.php
├── database/              # Migrations & seeds
├── public/                # Public assets
│   └── build/             # Compiled assets
├── resources/             # Views, JS, CSS
│   ├── css/
│   ├── js/
│   │   └── components/
│   └── views/
├── routes/                # Route definitions
│   ├── admin.php
│   ├── api.php
│   └── web.php
├── storage/               # Storage directory
│   ├── framework/
│   └── logs/
├── composer.json
├── package.json
├── plugin.php             # Plugin entry point
└── vite.config.js         # Vite configuration
```

## Usage

### Custom Post Type Builder

```php
use WPJarvis\Framework\WordPress\PostTypes\PostTypeBuilder;

PostTypeBuilder::make('portfolio')
    ->labels('Portfolio Item', 'Portfolio')
    ->icon('dashicons-portfolio')
    ->supports(['title', 'editor', 'thumbnail'])
    ->hasArchive(true)
    ->rest(true)
    ->register();
```

### Taxonomy Builder

```php
use WPJarvis\Framework\WordPress\Taxonomies\TaxonomyBuilder;

// Category-like (hierarchical)
TaxonomyBuilder::category('portfolio_category')
    ->labels('Category', 'Categories')
    ->forPostTypes('portfolio')
    ->register();

// Tag-like (non-hierarchical)
TaxonomyBuilder::tag('portfolio_tag')
    ->labels('Tag', 'Tags')
    ->forPostTypes('portfolio')
    ->register();
```

### Metabox Builder

```php
use WPJarvis\Framework\WordPress\Metaboxes\MetaboxBuilder;

MetaboxBuilder::make('portfolio_details', 'Portfolio Details')
    ->forPostTypes('portfolio')
    ->text('client_name', 'Client Name')
    ->url('project_url', 'Project URL')
    ->date('completion_date', 'Completion Date')
    ->select('status', 'Status', [
        'active' => 'Active',
        'completed' => 'Completed',
    ])
    ->checkbox('featured', 'Featured Project')
    ->media('gallery_image', 'Gallery Image')
    ->register();
```

### Settings Page Builder

```php
use WPJarvis\Framework\WordPress\Settings\SettingsPageBuilder;

SettingsPageBuilder::make('my-settings', 'My Settings')
    ->underSettings()
    ->section('general', 'General Settings')
    ->text('api_key', 'API Key')
    ->checkbox('enabled', 'Enable Feature')
    ->select('mode', 'Mode', [
        'basic' => 'Basic',
        'advanced' => 'Advanced',
    ])
    ->register();
```

### Admin Menu Builder

```php
use WPJarvis\Framework\WordPress\Admin\MenuBuilder;

MenuBuilder::make()
    ->menu('my-plugin', 'My Plugin')
    ->icon('dashicons-admin-generic')
    ->position(30)
    ->callback([$this, 'renderDashboard'])
    ->submenu('my-plugin-settings', 'Settings', [$this, 'renderSettings'])
    ->register();
```

### Admin Columns Manager

```php
use WPJarvis\Framework\WordPress\Admin\ColumnsManager;

ColumnsManager::for('portfolio')
    ->thumbnail('image', 'Image', [60, 60])
    ->after('title', 'client', 'Client', function ($postId) {
        echo get_post_meta($postId, '_client', true) ?: '—';
    })
    ->boolean('featured', 'Featured', '_featured', '★', '—')
    ->taxonomy('categories', 'Categories', 'portfolio_category')
    ->sortable('client', '_client')
    ->remove('comments')
    ->register();
```

### Shortcode Builder

```php
use WPJarvis\Framework\WordPress\Shortcodes\ShortcodeBuilder;

ShortcodeBuilder::make('my_shortcode')
    ->defaults(['title' => 'Hello', 'class' => ''])
    ->render(function ($atts, $content) {
        return '<div class="' . esc_attr($atts['class']) . '">'
             . '<h3>' . esc_html($atts['title']) . '</h3>'
             . do_shortcode($content)
             . '</div>';
    })
    ->register();
```

### Gutenberg Block Builder

```php
use WPJarvis\Framework\WordPress\Blocks\BlockBuilder;

BlockBuilder::make('my-block', 'My Block')
    ->namespace('wpjarvis')
    ->category('common')
    ->icon('block-default')
    ->textAttribute('title', 'Default Title')
    ->booleanAttribute('showBorder', false)
    ->render(function ($attributes) {
        return '<div>' . esc_html($attributes['title']) . '</div>';
    })
    ->register();
```

## CLI Commands

WP Jarvis provides Artisan-like CLI commands for scaffolding:

```bash
# Generate a service provider
php jarvis make:provider MyServiceProvider

# Generate a custom post type
php jarvis make:posttype Portfolio

# Generate a taxonomy
php jarvis make:taxonomy PortfolioCategory --hierarchical

# Generate a metabox
php jarvis make:metabox PortfolioDetails --post-type=portfolio

# Generate a settings page
php jarvis make:settings MySettings

# Generate an admin menu
php jarvis make:menu MyMenu

# Generate admin columns
php jarvis make:columns PortfolioColumns --post-type=portfolio

# Generate a shortcode
php jarvis make:shortcode MyShortcode

# Generate a widget
php jarvis make:widget MyWidget

# Generate a Gutenberg block
php jarvis make:block MyBlock --dynamic

# Generate a complete module
php jarvis make:module Portfolio --full

# Generate a controller
php jarvis make:controller PortfolioController --resource

# Generate a model
php jarvis make:model Portfolio --migration --controller

# Clear cache
php jarvis cache:clear

# Show app info
php jarvis app:info
```

## Creating a Module

Modules are self-contained features with their own service provider:

```bash
php jarvis make:module Blog --full
```

This creates:
```
app/Modules/Blog/
├── Providers/
│   └── BlogServiceProvider.php
├── Http/
│   └── Controllers/
├── Models/
└── Views/
```

Register the module in `config/app.php`:

```php
'providers' => [
    // ...
    BraCalculator\App\Modules\Blog\Providers\BlogServiceProvider::class,
],
```

## Frontend Integration

### React

1. Create a mount point in your admin page:

```php
echo '<div data-wpjarvis-react="Dashboard" data-props=\'{"user":"admin"}\'></div>';
```

2. Enqueue the React script:

```php
wp_enqueue_script('wpjarvis-react', plugins_url('public/build/js/react-app.js', WPJARVIS_FILE));
```

### Vue

1. Create a mount point:

```php
echo '<div data-wpjarvis-vue="Settings" data-props=\'{"settings":[]}\' ></div>';
```

2. Enqueue the Vue script:

```php
wp_enqueue_script('wpjarvis-vue', plugins_url('public/build/js/vue-app.js', WPJARVIS_FILE));
```

### Building Assets

```bash
# Development with hot reload
npm run dev

# Production build
npm run build
```

## Service Providers

Create a service provider to register services:

```php
<?php

namespace BraCalculator\App\Providers;

use WPJarvis\Framework\Foundation\BaseServiceProvider;

class MyServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register bindings
        $this->app->singleton('my-service', function () {
            return new MyService();
        });
    }

    public function boot(): void
    {
        // Bootstrap services
    }
}
```

## Configuration

All configuration files are in the `config/` directory:

- `app.php` - Application settings, providers, aliases
- `database.php` - Database connections
- `requirements.php` - Plugin requirements

Access config values:

```php
$value = config('app.name');
config(['app.debug' => true]);
```

## Helpers

WP Jarvis provides Laravel-compatible helper functions:

```php
// Application instance
app();
app('config');

// Configuration
config('app.name');

// Views
view('admin.dashboard', ['data' => $data]);

// Paths
base_path('app');
config_path('app.php');
storage_path('logs');
resource_path('views');

// Utilities
collect([1, 2, 3])->map(fn($n) => $n * 2);
now();
today();
```

## Testing

To run the test suite:

```bash
composer install
vendor/bin/phpunit
```

## License

GPL-2.0-or-later
