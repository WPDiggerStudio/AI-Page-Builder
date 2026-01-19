<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeColumnsCommand - Creates a new admin columns class.
 *
 * Generates a class for customizing WordPress admin list table columns
 * for a specific post-type.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeColumnsCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:columns
                            {name : The name of the columns class}
                            {--post-type= : Post type to modify columns for}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new admin list columns class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Columns';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/admin/columns.stub';
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
		$namespace = $rootNamespace . '\\WordPress\\Admin\\Columns';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to columns.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className = $nameData['class'];
		$postType  = $this->option( 'post-type' ) ?: Str::snake( $className );

		$replacements = [
			'{{ post_type }}' => $postType,
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
		$className = $nameData['class'];
		$postType  = $this->option( 'post-type' ) ?: Str::snake( $className );
		$namespace = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Columns Details:</info>' );
		$this->line( '  <comment>Class:</comment>     ' . $className );
		$this->line( '  <comment>Namespace:</comment> ' . $namespace );
		$this->line( '  <comment>Post Type:</comment> ' . $postType );
		$this->line( '  <comment>File:</comment>      ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>These columns will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'PostColumns';
	}
}
