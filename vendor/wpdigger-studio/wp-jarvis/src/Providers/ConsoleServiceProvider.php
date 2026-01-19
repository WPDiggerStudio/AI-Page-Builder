<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;

/**
 * ConsoleServiceProvider
 *
 * Registers all console commands for the framework.
 */
class ConsoleServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 */
	protected bool $defer = true;

	/**
	 * The commands to register.
	 *
	 * @var array<string, string>
	 */
	protected array $commandClasses = [
		// App Commands
		'command.app.info' => \WPJarvis\Framework\Console\Commands\App\AppInfoCommand::class,
		'command.key.generate' => \WPJarvis\Framework\Console\Commands\App\KeyGenerateCommand::class,

		// Cache Commands
		'command.cache.clear' => \WPJarvis\Framework\Console\Commands\Cache\CacheClearCommand::class,

		// Database Commands
		'command.make.migration' => \WPJarvis\Framework\Console\Commands\Database\MakeMigrationCommand::class,
		'command.make.model' => \WPJarvis\Framework\Console\Commands\Database\MakeModelCommand::class,
		'command.migrate' => \WPJarvis\Framework\Console\Commands\Database\MigrateCommand::class,
		'command.migrate:rollback' => \WPJarvis\Framework\Console\Commands\Database\MigrateRollbackCommand::class,
		'command.migrate:refresh' => \WPJarvis\Framework\Console\Commands\Database\MigrateRefreshCommand::class,
		'command.migrate:status' => \WPJarvis\Framework\Console\Commands\Database\MigrateStatusCommand::class,
		'db:seed' => \WPJarvis\Framework\Console\Commands\Database\DbSeedCommand::class,
		'command.make.seeder' => \WPJarvis\Framework\Console\Commands\Database\MakeSeederCommand::class,

		// Event Commands
		'command.make.event' => \WPJarvis\Framework\Console\Commands\Event\MakeEventCommand::class,
		'command.make.listener' => \WPJarvis\Framework\Console\Commands\Event\MakeListenerCommand::class,

		// Http Commands
		'command.make.controller' => \WPJarvis\Framework\Console\Commands\Http\MakeControllerCommand::class,
		'command.make.middleware' => \WPJarvis\Framework\Console\Commands\Http\MakeMiddlewareCommand::class,

		// Mail Commands
		'command.make.mail' => \WPJarvis\Framework\Console\Commands\Mail\MakeMailCommand::class,


		// Provider Commands
		'command.make.provider' => \WPJarvis\Framework\Console\Commands\Provider\MakeProviderCommand::class,

		// Queue Commands
		'command.make.job' => \WPJarvis\Framework\Console\Commands\Queue\MakeJobCommand::class,

		// Schedule Commands
		'command.make.task' => \WPJarvis\Framework\Console\Commands\Schedule\MakeTaskCommand::class,
		'command.make.kernel' => \WPJarvis\Framework\Console\Commands\Schedule\MakeKernelCommand::class,

		// WordPress Commands
		'command.make.posttype' => \WPJarvis\Framework\Console\Commands\WordPress\MakePostTypeCommand::class,
		'command.make.taxonomy' => \WPJarvis\Framework\Console\Commands\WordPress\MakeTaxonomyCommand::class,
		'command.make.metabox' => \WPJarvis\Framework\Console\Commands\WordPress\MakeMetaboxCommand::class,
		'command.make.shortcode' => \WPJarvis\Framework\Console\Commands\WordPress\MakeShortcodeCommand::class,
		'command.make.widget' => \WPJarvis\Framework\Console\Commands\WordPress\MakeWidgetCommand::class,
		'command.make.block' => \WPJarvis\Framework\Console\Commands\WordPress\MakeBlockCommand::class,
		'command.make.settings' => \WPJarvis\Framework\Console\Commands\WordPress\MakeSettingsCommand::class,
		'command.make.menu' => \WPJarvis\Framework\Console\Commands\WordPress\MakeMenuCommand::class,
		'command.make.columns' => \WPJarvis\Framework\Console\Commands\WordPress\MakeColumnsCommand::class,
	];

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		foreach ($this->commandClasses as $abstract => $concrete) {
			$this->app->singleton($abstract, function ($app) use ($concrete) {
				return new $concrete($app, $app->make('files'));
			});
		}

		// Store command list for later use
		$this->app->singleton('WpJarvis.commands', function () {
			return array_values($this->commandClasses);
		});
	}

	/**
	 * Bootstrap the service provider.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void
	{
		// Register WP-CLI commands if available
		if (defined('WP_CLI') && \WP_CLI) {
			$this->registerWpCliCommands();
		}
	}

	/**
	 * Register WP-CLI commands.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function registerWpCliCommands(): void
	{
		if (!class_exists('WP_CLI')) {
			return;
		}

		\WP_CLI::add_command('jarvis', function ($args, $assoc_args) {
			$command = $args[0] ?? 'list';

			// Map to artisan command
			if ($this->app->bound('artisan')) {
				$this->app->make('artisan')->call($command, $assoc_args);
			}
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array<string>
	 */
	public function provides(): array
	{
		return array_keys($this->commandClasses);
	}
}
