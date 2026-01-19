<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeMenuCommand - Creates a new admin menu class.
 *
 * Generates a class for creating WordPress admin menu pages
 * with proper capability and position configuration.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeMenuCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:menu
                            {name : The name of the menu class}
                            {--slug= : Menu slug}
                            {--title= : Page title}
                            {--menu-title= : Menu title}
                            {--icon=dashicons-admin-generic : Menu icon}
                            {--position=30 : Menu position}
                            {--capability=manage_options : Required capability}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new admin menu class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Menu';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/admin/menu.stub';
	}

	/**
	 * Get default namespace.
	 *
	 * @param string $rootNamespace The root namespace.
	 * @param string $subPath The sub-path for namespacing.
	 *
	 * @return string The full namespace.
	 */
	protected function getDefaultNamespace( string $rootNamespace, string $subPath = '' ): string {
		$namespace = $rootNamespace . '\\WordPress\\Admin\\Menus';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to a menu.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className  = $nameData['class'];
		$slug       = $this->option( 'slug' ) ?: Str::kebab( $className );
		$title      = $this->option( 'title' ) ?: Str::headline( $className );
		$menuTitle  = $this->option( 'menu-title' ) ?: $title;
		$icon       = $this->option( 'icon' ) ?: 'dashicons-admin-generic';
		$position   = $this->option( 'position' ) ?: '30';
		$capability = $this->option( 'capability' ) ?: 'manage_options';

		$replacements = [
			'{{ menu_slug }}'     => $slug,
			'{{ page_title }}'    => $title,
			'{{ menu_title }}'    => $menuTitle,
			'{{ menu_icon }}'     => $icon,
			'{{ menu_position }}' => $position,
			'{{ capability }}'    => $capability,
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		return $this;
	}

	/**
	 * Tasks after generation.
	 *
	 * @param string $qualifiedName The fully qualified class name.
	 * @param array<string, mixed> $nameData The parsed name data.
	 * @param string $path The file path.
	 *
	 * @return void
	 */
	protected function afterGeneration( string $qualifiedName, array $nameData, string $path ): void {
		$className  = $nameData['class'];
		$slug       = $this->option( 'slug' ) ?: Str::kebab( $className );
		$title      = $this->option( 'title' ) ?: Str::headline( $className );
		$menuTitle  = $this->option( 'menu-title' ) ?: $title;
		$icon       = $this->option( 'icon' ) ?: 'dashicons-admin-generic';
		$position   = $this->option( 'position' ) ?: '30';
		$capability = $this->option( 'capability' ) ?: 'manage_options';
		$namespace  = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Menu Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>  ' . $namespace );
		$this->line( '  <comment>Slug:</comment>       ' . $slug );
		$this->line( '  <comment>Title:</comment>      ' . $title );
		$this->line( '  <comment>Menu Title:</comment> ' . $menuTitle );
		$this->line( '  <comment>Icon:</comment>       ' . $icon );
		$this->line( '  <comment>Position:</comment>   ' . $position );
		$this->line( '  <comment>Capability:</comment> ' . $capability );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This menu will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'Dashboard';
	}
}
