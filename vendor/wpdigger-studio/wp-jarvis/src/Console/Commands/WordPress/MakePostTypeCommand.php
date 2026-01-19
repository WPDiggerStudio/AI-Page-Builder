<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakePostTypeCommand - Creates a new custom post-type class.
 *
 * Generates a post-type class with full builder options
 * including REST API support, archive settings, and rewrite rules.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakePostTypeCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:posttype
                            {name : The name of post type class}
                            {--slug= : Custom slug for the post type}
                            {--singular= : Singular label}
                            {--plural= : Plural label}
                            {--icon=dashicons-admin-post : Menu icon}
                            {--supports=* : Supported features (title, editor, thumbnail, excerpt, etc.)}
                            {--public : Make post type public}
                            {--has-archive : Enable archive}
                            {--show-in-rest : Show in REST API}
                            {--rewrite=* : Rewrite rules (format: slug:value)}
                            {--capability-type=post : Capability type (post, page)}
                            {--hierarchical : Make post type hierarchical}
                            {--menu-position= : Menu position (1-100)}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new custom post type class with full builder options';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'PostType';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/content/posttype.stub';
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
		$namespace = rtrim( $rootNamespace, '\\' ) . '\\WordPress\\PostTypes';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to post-type.
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
		$icon      = $this->option( 'icon' ) ?: 'dashicons-admin-post';
		$supports  = $this->option( 'supports' );
		if ( empty( $supports ) ) {
			$supports = [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ];
		}
		$supportsString = "['" . implode( "', '", $supports ) . "']";
		$public         = $this->optionBool( 'public' );
		$hasArchive     = $this->optionBool( 'has-archive' );
		$showInRest     = $this->optionBool( 'show-in-rest' );
		$rewrite        = $this->option( 'rewrite' );
		$capabilityType = $this->option( 'capability-type' ) ?: 'post';
		$hierarchical   = $this->optionBool( 'hierarchical' );
		$menuPosition   = $this->option( 'menu-position' ) ?: '30';

		// Build rewrite rules string
		$rewriteString = 'true';
		if ( $rewrite ) {
			$rewriteString = "['slug' => '{$slug}']";
		}

		$replacements = [
			'{{ posttype_slug }}'   => $slug,
			'{{ singular_label }}'  => $singular,
			'{{ plural_label }}'    => $plural,
			'{{ singular_lower }}'  => strtolower( $singular ),
			'{{ plural_lower }}'    => strtolower( $plural ),
			'{{ icon }}'            => $icon,
			'{{ supports }}'        => $supportsString,
			'{{ public }}'          => $public ? 'true' : 'false',
			'{{ has_archive }}'     => $hasArchive ? 'true' : 'false',
			'{{ show_in_rest }}'    => $showInRest ? 'true' : 'false',
			'{{ rewrite }}'         => $rewriteString,
			'{{ capability_type }}' => $capabilityType,
			'{{ hierarchical }}'    => $hierarchical ? 'true' : 'false',
			'{{ menu_position }}'   => $menuPosition,
			'{{ const_slug }}'      => strtoupper( Str::snake( $className ) ),
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
		$slug      = $this->option( 'slug' ) ?: Str::snake( $className );
		$singular  = $this->option( 'singular' ) ?: Str::headline( $className );
		$plural    = $this->option( 'plural' ) ?: Str::plural( $singular );
		$namespace = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Post Type Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>   ' . $namespace );
		$this->line( '  <comment>Slug:</comment>       ' . $slug );
		$this->line( '  <comment>Singular:</comment>    ' . $singular );
		$this->line( '  <comment>Plural:</comment>      ' . $plural );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This post-type will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'Portfolio';
	}
}
