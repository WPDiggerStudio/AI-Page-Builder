<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeTaxonomyCommand - Creates a new taxonomy class.
 *
 * Generates a taxonomy class with full builder options
 * including hierarchical/tag modes, REST API support, and admin columns.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeTaxonomyCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:taxonomy
                            {name : The name of taxonomy class}
                            {--slug= : Custom slug for taxonomy}
                            {--singular= : Singular label}
                            {--plural= : Plural label}
                            {--post-types=* : Associated post types (defaults to post)}
                            {--hierarchical : Make taxonomy hierarchical (category-like)}
                            {--tag : Make taxonomy non-hierarchical (tag-like)}
                            {--show-in-rest : Show in REST API}
                            {--show-admin-column : Show admin column}
                            {--rewrite=* : Rewrite rules (format: slug:value)}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new taxonomy class with full builder options';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Taxonomy';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/content/taxonomy.stub';
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
		$namespace = rtrim( $rootNamespace, '\\' ) . '\\WordPress\\Taxonomies';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to taxonomy.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className = $nameData['class'];
		$slug      = $this->option( 'slug' ) ?: Str::snake( $className );
		$singular  = $this->option( 'singular' ) ?: Str::headline( $className );
		$plural    = $this->option( 'plural' ) ?: Str::plural( $singular );

		$postTypes = $this->option( 'post-types' );
		if ( empty( $postTypes ) ) {
			$postTypes = [ 'post' ];
		}

		$hierarchical = $this->optionBool( 'hierarchical' );
		$tag          = $this->optionBool( 'tag' );
		if ( $hierarchical && $tag ) {
			throw new \InvalidArgumentException( 'Cannot use both --hierarchical and --tag options' );
		}

		$showInRest      = $this->optionBool( 'show-in-rest' );
		$showAdminColumn = $this->optionBool( 'show-admin-column' );
		$rewrite         = $this->option( 'rewrite' );

		$hierarchicalString    = $hierarchical ? 'true' : 'false';
		$showInRestString      = $showInRest ? 'true' : 'false';
		$showAdminColumnString = $showAdminColumn ? 'true' : 'false';

		// Build rewrite rules string
		$rewriteString = 'true';
		if ( $rewrite ) {
			$rewriteString = "['slug' => '{$slug}']";
		}

		$replacements = [
			'{{ taxonomy_slug }}'     => $slug,
			'{{ singular_label }}'    => $singular,
			'{{ plural_label }}'      => $plural,
			'{{ singular_lower }}'    => strtolower( $singular ),
			'{{ plural_lower }}'      => strtolower( $plural ),
			'{{ post_types }}'        => "['" . implode( "', '", $postTypes ) . "']",
			'{{ hierarchical }}'      => $hierarchicalString,
			'{{ show_in_rest }}'      => $showInRestString,
			'{{ show_admin_column }}' => $showAdminColumnString,
			'{{ rewrite }}'           => $rewriteString,
			'{{ const_slug }}'        => strtoupper( Str::snake( $className ) ),
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
		$className    = $nameData['class'];
		$slug         = $this->option( 'slug' ) ?: Str::snake( $className );
		$singular     = $this->option( 'singular' ) ?: Str::headline( $className );
		$plural       = $this->option( 'plural' ) ?: Str::plural( $singular );
		$namespace    = $this->getNamespace( $qualifiedName );
		$hierarchical = $this->optionBool( 'hierarchical' );
		$postTypes    = $this->option( 'post-types' ) ?: [ 'post' ];

		$this->newLine();
		$this->line( '<info>Taxonomy Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>   ' . $namespace );
		$this->line( '  <comment>Slug:</comment>       ' . $slug );
		$this->line( '  <comment>Singular:</comment>    ' . $singular );
		$this->line( '  <comment>Plural:</comment>      ' . $plural );
		$this->line( '  <comment>Type:</comment>        ' . ( $hierarchical ? 'Hierarchical (category-like)' : 'Non-hierarchical (tag-like)' ) );
		$this->line( '  <comment>Post Types:</comment>   ' . implode( ', ', $postTypes ) );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This taxonomy will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'Category';
	}
}
