<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeSettingsCommand - Creates a new settings page class.
 *
 * Generates a settings page class using the Settings builder
 * with Field System integration.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeSettingsCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:settings
                            {name : The name of the settings class}
                            {--slug= : Settings page slug}
                            {--title= : Page title}
                            {--menu-title= : Menu title}
                            {--parent= : Parent menu slug}
                            {--capability=manage_options : Required capability}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new settings page class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Settings';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/admin/settings.stub';
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
		$namespace = $rootNamespace . '\\WordPress\\Admin\\Settings';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to settings.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className   = $nameData['class'];
		$slug        = $this->option( 'slug' ) ?: Str::kebab( $className );
		$title       = $this->option( 'title' ) ?: Str::headline( $className ) . ' Settings';
		$menuTitle   = $this->option( 'menu-title' ) ?: Str::headline( $className );
		$parent      = $this->option( 'parent' ) ?: '';
		$capability  = $this->option( 'capability' ) ?: 'manage_options';
		$optionGroup = Str::snake( $className ) . '_options';

		$replacements = [
			'{{ settings_slug }}'  => $slug,
			'{{ settings_title }}' => $title,
			'{{ menu_title }}'     => $menuTitle,
			'{{ parent_slug }}'    => $parent,
			'{{ capability }}'     => $capability,
			'{{ option_group }}'   => $optionGroup,
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
		$title      = $this->option( 'title' ) ?: Str::headline( $className ) . ' Settings';
		$menuTitle  = $this->option( 'menu-title' ) ?: Str::headline( $className );
		$parent     = $this->option( 'parent' ) ?: 'options-general.php';
		$capability = $this->option( 'capability' ) ?: 'manage_options';
		$namespace  = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Settings Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>  ' . $namespace );
		$this->line( '  <comment>Slug:</comment>       ' . $slug );
		$this->line( '  <comment>Title:</comment>      ' . $title );
		$this->line( '  <comment>Menu Title:</comment> ' . $menuTitle );
		$this->line( '  <comment>Parent:</comment>     ' . ( $parent ?: 'Top-level menu' ) );
		$this->line( '  <comment>Capability:</comment> ' . $capability );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This settings page will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'General';
	}
}
