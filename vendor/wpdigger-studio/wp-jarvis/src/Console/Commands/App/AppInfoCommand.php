<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\App;

use Illuminate\Console\Command;

/**
 * App Info Command
 *
 * Displays comprehensive information about the WP Jarvis installation.
 *
 * @package WPJarvis\Framework\Console\Commands
 */
class AppInfoCommand extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:info';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Display comprehensive information about the WP Jarvis installation';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \ReflectionException
	 */
	public function handle(): int {
		$app = $this->laravel;

		// Display header
		$this->newLine();
		$this->line( '<fg=cyan>  ██╗    ██╗██████╗      ██╗ █████╗ ██████╗ ██╗   ██╗██╗███████╗</>' );
		$this->line( '<fg=cyan>  ██║    ██║██╔══██╗     ██║██╔══██╗██╔══██╗██║   ██║██║██╔════╝</>' );
		$this->line( '<fg=blue>  ██║ █╗ ██║██████╔╝     ██║███████║██████╔╝██║   ██║██║███████╗</>' );
		$this->line( '<fg=blue>  ██║███╗██║██╔═══╝ ██   ██║██╔══██║██╔══██╗╚██╗ ██╔╝██║╚════██║</>' );
		$this->line( '<fg=magenta>  ╚███╔███╔╝██║     ╚█████╔╝██║  ██║██║  ██║ ╚████╔╝ ██║███████║</>' );
		$this->line( '<fg=magenta>   ╚══╝╚══╝ ╚═╝      ╚════╝ ╚═╝  ╚═╝╚═╝  ╚═╝  ╚═╝╚══════╝</>' );
		$this->newLine();
		$this->line( "<fg=white>  WP Jarvis App</> <fg=yellow>v1.0.0</>" );
		$this->line( '  <fg=gray>WordPress Development Framework</>' );
		$this->newLine();

		// Display framework information
		$this->info( '<options=bold>Framework Information</>' );

		$this->table(
			[ 'Setting', 'Value' ],
			[
				[ 'Framework Version', $app->version() ],
				[ 'PHP Version', PHP_VERSION ],
				[ 'PHP SAPI', PHP_SAPI ],
				[ 'WordPress Version', function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'N/A' ],
				[ 'WordPress URL', function_exists( 'home_url' ) ? home_url() : 'N/A' ],
				[ 'Environment', $app->environment() ],
				[ 'Debug Mode', $app->isDebug() ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>' ],
				[ 'Running in Console', $app->runningInConsole() ? '<fg=green>Yes</>' : '<fg=red>No</>' ],
			]
		);

		// Display paths
		$this->newLine();
		$this->info( '<options=bold>Paths</>' );

		$this->table(
			[ 'Path', 'Location' ],
			[
				[ 'Base Path', $app->basePath() ],
				[ 'Config Path', $app->configPath() ],
				[ 'Storage Path', $app->storagePath() ],
				[ 'Resources Path', $app->resourcePath() ],
				[ 'Public Path', $app->publicPath() ],
				[ 'Database Path', $app->databasePath() ],
				[ 'Routes Path', $app->routesPath() ],
				[ 'Views Path', $app->viewPath() ],
				[ 'Cache Path', $app->cachePath() ],
				[ 'Logs Path', $app->logPath() ],
			]
		);

		// Display plugin information
		$this->newLine();
		$this->info( '<options=bold>Plugin Information</>' );

		$pluginName     = $app->make( 'config' )->get( 'app.name', 'WP Jarvis App' );
		$pluginVersion  = $app->make( 'config' )->get( 'app.version', '1.0.0' );
		$pluginSlug     = $app->make( 'config' )->get( 'app.slug', 'wp-jarvis' );
		$pluginFile     = defined( 'WPJARVIS_FILE' ) ? WPJARVIS_FILE : 'N/A';
		$pluginBasename = defined( 'WPJARVIS_BASENAME' ) ? WPJARVIS_BASENAME : 'N/A';

		$this->table(
			[ 'Setting', 'Value' ],
			[
				[ 'Plugin Name', $pluginName ],
				[ 'Plugin Version', $pluginVersion ],
				[ 'Plugin Slug', $pluginSlug ],
				[ 'Plugin File', $pluginFile ],
				[ 'Plugin Basename', $pluginBasename ],
			]
		);

		// Display WordPress paths
		$this->newLine();
		$this->info( '<options=bold>WordPress Paths</>' );

		$wpPaths = [
			[ 'WP Content Dir', defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : 'N/A' ],
			[ 'WP Plugins Dir', defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : 'N/A' ],
			[ 'WP Themes Dir', defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/themes' : 'N/A' ],
			[ 'WP Uploads Dir', function_exists( 'wp_upload_dir' ) ? wp_upload_dir()['basedir'] : 'N/A' ],
		];

		$this->table(
			[ 'Path', 'Location' ],
			$wpPaths
		);

		return 0;
	}
}
