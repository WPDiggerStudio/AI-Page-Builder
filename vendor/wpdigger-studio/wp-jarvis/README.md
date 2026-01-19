# WP Jarvis Framework

A Laravel-first application framework for WordPress plugins.

## Structure

```
wp-jarvis-core/
├── config/                    # Default framework configurations
├── resources/
│   └── stubs/                 # Generator templates
│       ├── console/           # CLI command stubs
│       ├── database/          # Migration & seeder stubs
│       ├── http/              # Controller & middleware stubs
│       ├── models/            # Model stubs
│       ├── modules/           # Module stubs
│       ├── providers/         # Service provider stubs
│       └── wp/                # WordPress-specific stubs
│           ├── admin/         # Admin (columns, menu, settings)
│           ├── blocks/        # Gutenberg blocks
│           ├── content/       # CPT, taxonomy, metabox
│           ├── frontend/      # Shortcodes, widgets
│           └── rest/          # REST API controllers
├── src/
│   ├── Application.php        # Main IoC container
│   ├── Bootstrap/             # Application bootstrapper
│   ├── Console/               # CLI kernel & commands
│   │   └── Commands/
│   │       └── Generators/    # make:* commands
│   ├── Contracts/             # Interfaces
│   ├── Database/              # Database abstractions
│   ├── Exceptions/            # Exception handling
│   ├── Foundation/            # Base classes (ServiceProvider)
│   ├── Http/                  # HTTP layer
│   ├── Providers/             # Core service providers
│   ├── Support/               # Helpers & facades
│   │   └── Facades/
│   └── WP/                    # WordPress integrations
│       ├── Admin/             # Menu, Settings, Columns
│       ├── Assets/            # Script/style enqueue
│       ├── Blocks/            # Gutenberg blocks
│       ├── Cache/             # Transient cache
│       ├── Content/           # PostType, Taxonomy, Metabox
│       ├── Cron/              # WP-Cron scheduler
│       ├── Frontend/          # Shortcode, Widget
│       ├── Logging/           # WordPress logger
│       └── REST/              # REST API controllers
└── composer.json
```

## Installation

```bash
composer require wpjarvis/framework
```

## Usage

### Post Types

```php
use WPJarvis\Framework\WP\Content\PostType;

PostType::make('portfolio')
    ->labels('Portfolio', 'Portfolios')
    ->icon('dashicons-portfolio')
    ->supports(['title', 'editor', 'thumbnail'])
    ->hasArchive()
    ->showInRest()
    ->register();
```

### Taxonomies

```php
use WPJarvis\Framework\WP\Content\Taxonomy;

Taxonomy::category('portfolio_category')
    ->labels('Category', 'Categories')
    ->forPostTypes('portfolio')
    ->showInRest()
    ->register();
```

### Metaboxes

```php
use WPJarvis\Framework\WP\Content\Metabox;

Metabox::make('portfolio_details', 'Details')
    ->forPostTypes('portfolio')
    ->text('client', 'Client Name')
    ->url('website', 'Website URL')
    ->date('completed', 'Completion Date')
    ->register();
```

### Admin Menus

```php
use WPJarvis\Framework\WP\Admin\Menu;

Menu::make()
    ->page('my-plugin', 'My Plugin')
    ->icon('dashicons-admin-generic')
    ->position(30)
    ->render(fn() => view('admin.dashboard'))
    ->subpage('my-plugin-settings', 'Settings', fn() => view('admin.settings'))
    ->register();
```

### Settings Pages

```php
use WPJarvis\Framework\WP\Admin\Settings;

Settings::make('my-settings', 'My Settings')
    ->underSettings()
    ->section('api', 'API Configuration')
    ->text('api_key', 'API Key')
    ->checkbox('debug', 'Enable Debug Mode')
    ->register();
```

### Admin Columns

```php
use WPJarvis\Framework\WP\Admin\Columns;

Columns::for('portfolio')
    ->thumbnail('image', 'Image')
    ->meta('client', 'Client', '_client')
    ->taxonomy('categories', 'Categories', 'portfolio_category')
    ->sortable('client', '_client')
    ->remove('comments')
    ->register();
```

### Shortcodes

```php
use WPJarvis\Framework\WP\Frontend\Shortcode;

Shortcode::make('greeting')
    ->defaults(['name' => 'World'])
    ->render(fn($atts) => "<p>Hello, {$atts['name']}!</p>")
    ->register();
```

### Gutenberg Blocks

```php
use WPJarvis\Framework\WP\Blocks\Block;

Block::make('callout', 'Callout Box')
    ->namespace('my-plugin')
    ->category('design')
    ->textAttribute('title', 'Default Title')
    ->booleanAttribute('showIcon', true)
    ->render(fn($attrs) => "<div class='callout'>{$attrs['title']}</div>")
    ->register();
```

### REST API

```php
use WPJarvis\Framework\WP\REST\Controller;

class ItemsController extends Controller
{
    protected string $namespace = 'my-plugin/v1';
    protected string $rest_base = 'items';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => [$this, 'canRead'],
        ]);
    }

    public function index($request)
    {
        return $this->success(['items' => []]);
    }
}
```

### Cron/Scheduled Tasks

```php
use WPJarvis\Framework\WP\Cron\Scheduler;

Scheduler::daily('my_daily_task', function() {
    // Run daily cleanup
});

Scheduler::hourly('my_hourly_sync', function() {
    // Sync data every hour
});
```

### Cache (Transients)

```php
use WPJarvis\Framework\WP\Cache\TransientStore;

$cache = TransientStore::make('my_plugin_');

// Store for 1 hour
$cache->set('api_response', $data, 3600);

// Get with default
$data = $cache->get('api_response', []);

// Remember pattern
$data = $cache->remember('expensive_query', 3600, fn() => expensive_operation());
```

### Assets

```php
use WPJarvis\Framework\WP\Assets\Enqueue;

Enqueue::script('my-app', plugins_url('build/app.js', __FILE__), ['jquery']);
Enqueue::style('my-styles', plugins_url('build/app.css', __FILE__));
Enqueue::localize('my-app', 'MyApp', ['ajax_url' => admin_url('admin-ajax.php')]);
Enqueue::registerFrontend();
```

## Service Providers

```php
use WPJarvis\Framework\Foundation\ServiceProvider;

class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('my-service', fn() => new MyService());
    }

    public function boot(): void
    {
        // Register hooks, post types, etc.
    }
}
```

## License

MIT
