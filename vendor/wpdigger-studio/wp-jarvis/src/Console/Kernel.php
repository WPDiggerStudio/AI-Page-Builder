<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WPJarvis\Framework\Application;
use WPJarvis\Framework\Contracts\Console\Kernel as KernelContract;

/**
 * Console Kernel
 *
 * Handles CLI commands for WP Jarvis framework.
 *
 * @package WPJarvis\Framework\Console
 */
class Kernel implements KernelContract
{
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	private Application $app;

	/**
	 * The Artisan application instance.
	 *
	 * @var Artisan|null
	 */
	private ?Artisan $artisan = null;

	/**
	 * The event dispatcher.
	 *
	 * @var Dispatcher
	 */
	private Dispatcher $events;

	/**
	 * The commands to register, organized by category.
	 *
	 * @var array<string, array<class-string>>
	 */
	private array $commands = [
		'app' => [
			Commands\App\AppInfoCommand::class,
			Commands\App\KeyGenerateCommand::class,
		],
		'cache' => [
			Commands\Cache\CacheClearCommand::class,
			Commands\Cache\ConfigCacheCommand::class,
			Commands\Cache\RouteCacheCommand::class,
			Commands\Cache\EventCacheCommand::class,
		],
		'database' => [
			Commands\Database\MakeMigrationCommand::class,
			Commands\Database\MakeModelCommand::class,
			Commands\Database\MakeSeederCommand::class,
			Commands\Database\MigrateCommand::class,
			Commands\Database\MigrateRollbackCommand::class,
			Commands\Database\MigrateRefreshCommand::class,
			Commands\Database\MigrateStatusCommand::class,
			Commands\Database\DbSeedCommand::class,
		],
		'event' => [
			Commands\Event\MakeEventCommand::class,
			Commands\Event\MakeListenerCommand::class,
			Commands\Event\MakeSubscriberCommand::class,
		],
		'http' => [
			Commands\Http\MakeControllerCommand::class,
			Commands\Http\MakeMiddlewareCommand::class,
		],
		'mail' => [
			Commands\Mail\MakeMailCommand::class,
		],
		'provider' => [
			Commands\Provider\MakeProviderCommand::class,
		],
		'queue' => [
			Commands\Queue\MakeJobCommand::class,
		],
		'schedule' => [
			Commands\Schedule\MakeTaskCommand::class,
			Commands\Schedule\MakeKernelCommand::class,
		],
		'wordpress' => [
			Commands\WordPress\MakePostTypeCommand::class,
			Commands\WordPress\MakeTaxonomyCommand::class,
			Commands\WordPress\MakeMetaboxCommand::class,
			Commands\WordPress\MakeShortcodeCommand::class,
			Commands\WordPress\MakeWidgetCommand::class,
			Commands\WordPress\MakeBlockCommand::class,
			Commands\WordPress\MakeSettingsCommand::class,
			Commands\WordPress\MakeMenuCommand::class,
			Commands\WordPress\MakeColumnsCommand::class,
		],
	];

	/**
	 * Category descriptions for command listing.
	 *
	 * @var array<string, string>
	 */
	private array $categoryDescriptions = [
		'app' => 'Application commands',
		'cache' => 'Cache management',
		'database' => 'Database operations',
		'migrate' => 'Database migrations',
		'event' => 'Event & Listener generators',
		'http' => 'HTTP & Middleware generators',
		'mail' => 'Mail generators',
		'provider' => 'Service provider generators',
		'queue' => 'Queue job generators',
		'schedule' => 'Scheduled task generators',
		'wordpress' => 'WordPress-specific generators',
	];

	/**
	 * Category icons for command listing.
	 *
	 * @var array<string, string>
	 */
	private array $categoryIcons = [
		'app' => '[+]',
		'cache' => '[=]',
		'database' => '[db]',
		'migrate' => '[⬆]',
		'event' => '[!]',
		'http' => '[www]',
		'mail' => '[@]',
		'provider' => '[P]',
		'queue' => '[Q]',
		'schedule' => '[T]',
		'wordpress' => '[WP]',
	];

	/**
	 * Create a new console kernel instance.
	 *
	 * @param Application $app
	 * @param Dispatcher $events
	 */
	public function __construct(Application $app, Dispatcher $events)
	{
		$this->app = $app;
		$this->events = $events;
	}

	/**
	 * Bootstrap application.
	 *
	 * @return void
	 */
	public function bootstrap(): void
	{
		$this->app->boot();
	}

	/**
	 * Display the branding banner.
	 *
	 * @param OutputInterface $output
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function displayBanner(OutputInterface $output): void
	{
		$pluginName = $this->app->config('app.name', 'WP Jarvis App');
		$pluginVersion = $this->app->config('app.version', '1.0.0');

		$output->writeln('');
		$output->writeln('<fg=cyan>  ██╗    ██╗██████╗      ██╗ █████╗ ██████╗ ██╗   ██╗██╗███████╗</>');
		$output->writeln('<fg=cyan>  ██║    ██║██╔══██╗     ██║██╔══██╗██╔══██╗██║   ██║██║██╔════╝</>');
		$output->writeln('<fg=blue>  ██║ █╗ ██║██████╔╝     ██║███████║██████╔╝██║   ██║██║███████╗</>');
		$output->writeln('<fg=blue>  ██║███╗██║██╔═══╝ ██   ██║██╔══██║██╔══██╗╚██╗ ██╔╝██║╚════██║</>');
		$output->writeln('<fg=magenta>  ╚███╔███╔╝██║     ╚█████╔╝██║  ██║██║  ██║ ╚████╔╝ ██║███████║</>');
		$output->writeln('<fg=magenta>   ╚══╝╚══╝ ╚═╝      ╚════╝ ╚═╝  ╚═╝╚═╝  ╚═╝  ╚═╝  ╚═╝╚══════╝</>');
		$output->writeln('');
		$output->writeln("  <fg=white>{$pluginName}</> <fg=yellow>v{$pluginVersion}</>");
		$output->writeln('  <fg=gray>WordPress Development Framework</>');
		$output->writeln('');
	}

	/**
	 * List all commands by category.
	 *
	 * @param OutputInterface $output
	 *
	 * @return void
	 */
	public function listCommands(OutputInterface $output): void
	{
		$commands = $this->getCommandsByCategory();
		$table = new Table($output);

		// Display Usage section
		$output->writeln('');
		$output->writeln('<fg=yellow>Usage:</>');
		$output->writeln('  command [options] [arguments]');

		// Display Options section
		$output->writeln('');
		$output->writeln('<fg=yellow>Options:</>');
		$options = [
			'-h, --help' => 'Display help for the given command. When no command is given display help for the list command',
			'--silent' => 'Do not output any message',
			'-q, --quiet' => 'Only errors are displayed. All other output is suppressed',
			'-V, --version' => 'Display this application version',
			'--ansi|--no-ansi' => 'Force (or disable --no-ansi) ANSI output',
			'-n, --no-interaction' => 'Do not ask any interactive question',
			'--env[=ENV]' => 'The environment the command should run under',
			'-v|vv|vvv, --verbose' => 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug',
		];

		foreach ($options as $option => $description) {
			$output->writeln(sprintf('  <fg=green>%-30s</> %s', $option, $description));
		}

		// Display Available commands heading
		$output->writeln('');
		$output->writeln('<fg=yellow>Available commands:</>');

		foreach ($commands as $category => $categoryCommands) {
			$icon = $this->categoryIcons[$category] ?? '[*]';
			$description = $this->categoryDescriptions[$category] ?? ucfirst($category);

			$output->writeln('');
			$output->writeln("<fg=white;options=bold>{$icon} {$description}</>");

			$table->setHeaders(['Command', 'Description']);
			$table->setRows([]);

			foreach ($categoryCommands as $command) {
				if (class_exists($command)) {
					// Resolve command through the container to get proper instance
					try {
						$instance = $this->app->make($command);
						$name = $instance->getName();
						$desc = $instance->getDescription() ?? '';

						$table->addRow(["<fg=green>{$name}</>", $desc]);
					} catch (\Throwable $e) {
						// Skip commands that can't be resolved
						continue;
					}
				}
			}

			$table->render();
		}

		$output->writeln('');
		$output->writeln('<fg=gray>Run any command with <fg=white;options=bold>php wp-jarvis <command></> for help.</>');
	}

	/**
	 * Handle an incoming console command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface|null $output
	 *
	 * @return int
	 */
	public function handle($input, $output = null): int
	{
		try {
			$this->bootstrap();

			return $this->getArtisan()->run($input, $output);
		} catch (\Throwable $e) {
			$output?->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}

	/**
	 * Terminate application.
	 *
	 * @param InputInterface $input
	 * @param int $status
	 *
	 * @return void
	 */
	public function terminate($input, $status): void
	{
		$this->app->terminate();
	}

	/**
	 * Get Artisan application instance.
	 *
	 * @return Artisan
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getArtisan(): Artisan
	{
		if ($this->artisan === null) {
			$this->artisan = new Artisan($this->app, $this->events, $this->app->version());
			$this->artisan->setName('wp-jarvis');
			$this->artisan->resolveCommands($this->getCommands());
		}

		return $this->artisan;
	}

	/**
	 * Get all registered commands.
	 *
	 * @return array<class-string>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getCommands(): array
	{
		$commands = [];

		foreach ($this->commands as $category => $categoryCommands) {
			foreach ($categoryCommands as $command) {
				if (class_exists($command)) {
					$commands[] = $command;
				}
			}
		}

		// Load additional commands from config
		if ($this->app->bound('config')) {
			$config = $this->app->make('config');
			$configCommands = $config->get('commands', []);
			$commands = array_merge($commands, $configCommands);
		}

		return $commands;
	}

	/**
	 * Get commands grouped by category.
	 *
	 * @return array<string, array<class-string>>
	 */
	public function getCommandsByCategory(): array
	{
		return $this->commands;
	}

	/**
	 * Register a command.
	 *
	 * @param class-string $command
	 * @param string $category
	 *
	 * @return void
	 */
	public function command(string $command, string $category = 'app'): void
	{
		$category = strtolower($category);

		if (!isset($this->commands[$category])) {
			$this->commands[$category] = [];
		}

		$this->commands[$category][] = $command;
	}

	/**
	 * Run an Artisan console command by name.
	 *
	 * @param string $command
	 * @param array<string, mixed> $parameters
	 *
	 * @return int
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function call(string $command, array $parameters = []): int
	{
		return $this->getArtisan()->call($command, $parameters);
	}

	/**
	 * Queue an Artisan console command.
	 *
	 * Note: Queue dispatching is not supported in WP Jarvis CLI.
	 * Use WordPress cron or the scheduling system instead.
	 *
	 * @param string $command
	 * @param array<string, mixed> $parameters
	 *
	 * @return void
	 * @throws \RuntimeException Always throws as queue is not supported.
	 */
	public function queue($command, array $parameters = []): void
	{
		throw new \RuntimeException(
			'Queue dispatching is not supported in WP Jarvis CLI. ' .
			'Use WordPress cron or the Task Scheduler instead.'
		);
	}

	/**
	 * Get all commands registered with the console.
	 *
	 * @return array<string, \Illuminate\Console\Command>
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function all(): array
	{
		return $this->getArtisan()->all();
	}

	/**
	 * Get output for the last run command.
	 *
	 * @return string
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function output(): string
	{
		return $this->getArtisan()->output();
	}

	/**
	 * Add a category of commands.
	 *
	 * @param string $category
	 * @param array<class-string> $commands
	 *
	 * @return void
	 */
	public function addCategory(string $category, array $commands): void
	{
		$category = strtolower($category);

		if (!isset($this->commands[$category])) {
			$this->commands[$category] = [];
		}

		$this->commands[$category] = array_merge(
			$this->commands[$category],
			$commands
		);
	}

	/**
	 * Get available command categories.
	 *
	 * @return array<string>
	 */
	public function getCategories(): array
	{
		return array_keys($this->commands);
	}

	/**
	 * Get category description.
	 *
	 * @param string $category
	 *
	 * @return string
	 */
	public function getCategoryDescription(string $category): string
	{
		return $this->categoryDescriptions[$category] ?? ucfirst($category);
	}

	/**
	 * Get category icon.
	 *
	 * @param string $category
	 *
	 * @return string
	 */
	public function getCategoryIcon(string $category): string
	{
		return $this->categoryIcons[$category] ?? '📌';
	}
}
