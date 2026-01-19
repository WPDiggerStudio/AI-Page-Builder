<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeShortcodeCommand - Creates a new shortcode class with full-featured integrations.
 *
 * Generates a shortcode class with:
 * - Typed attribute schema
 * - Optional TinyMCE editor button
 * - Optional Elementor widget adapter
 * - Optional WPBakery element adapter
 * - Optional view template
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeShortcodeCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:shortcode
                            {name : The name of shortcode class}
                            {--tag= : Shortcode tag (defaults to snake_case class name)}
                            {--allow-content : Enable content support for the shortcode}
                            {--attr=* : Add attribute definitions (format: type:name:default or type:name:default:description)}
                            {--editor-button : Generate TinyMCE button integration}
                            {--elementor : Generate Elementor widget adapter}
                            {--wpbakery : Generate WPBakery element adapter}
                            {--all-integrations : Generate all integrations (editor, Elementor, WPBakery)}
                            {--with-view : Generate a Blade view template}
                            {--simple : Generate simple class without AbstractShortcode base}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new shortcode class with optional editor, Elementor, and WPBakery integrations';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Shortcode';

	/**
	 * Files generated during this command.
	 *
	 * @var array<string, string>
	 */
	private array $generatedFiles = [];

	/**
	 * Execute the console command.
	 *
	 * @return int
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \JsonException|\Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	public function handle(): int {
		$name = $this->getNameInput();

		if ( $this->isReservedName( $name ) ) {
			$this->error( 'The name "' . $name . '" is reserved by PHP.' );

			return self::FAILURE;
		}

		$nameData      = $this->parseNameInput( $name );
		$qualifiedName = $this->qualifyClass( $nameData['class'], $nameData['path'] );
		$path          = $this->getPath( $qualifiedName );

		// Determine integration flags
		$allIntegrations = $this->optionBool( 'all-integrations' );
		$withEditor      = $allIntegrations || $this->optionBool( 'editor-button' );
		$withElementor   = $allIntegrations || $this->optionBool( 'elementor' );
		$withWPBakery    = $allIntegrations || $this->optionBool( 'wpbakery' );
		$withView        = $this->optionBool( 'with-view' );
		$simpleMode      = $this->optionBool( 'simple' );

		// Check if the main file exists
		if ( $this->alreadyExists( $path ) && ! $this->optionBool( 'force' ) ) {
			$this->error( $this->type . ' already exists! Use --force to overwrite.' );

			return self::FAILURE;
		}

		// Dry run mode
		if ( $this->isDryRun() ) {
			$this->displayDryRunOutput( $nameData, $qualifiedName, $path, $withEditor, $withElementor, $withWPBakery, $withView, $simpleMode );

			return self::SUCCESS;
		}

		// Generate the main shortcode class
		$this->makeDirectory( $path );
		$this->files->put( $path, $this->buildClass( $qualifiedName, $nameData ) );
		$this->generatedFiles['shortcode'] = $path;

		$this->info( sprintf( '%s [%s] created successfully.', $this->type, $this->getRelativePath( $path ) ) );

		// Generate view template
		if ( $withView ) {
			$this->generateViewTemplate( $nameData );
		}

		// Generate Elementor widget
		if ( $withElementor && ! $simpleMode ) {
			$this->generateElementorWidget( $nameData, $qualifiedName );
		}

		// Generate WPBakery element
		if ( $withWPBakery && ! $simpleMode ) {
			$this->generateWPBakeryElement( $nameData, $qualifiedName );
		}

		// Generate TinyMCE button JS
//		if ( $withEditor && ! $simpleMode ) {
//			$this->generateEditorButton( $nameData );
//		}

		// Display after-generation information
		$this->afterGeneration( $qualifiedName, $nameData, $path );

		return self::SUCCESS;
	}

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		if ( $this->optionBool( 'simple' ) ) {
			return 'wp/frontend/shortcode.simple.stub';
		}

		return 'wp/frontend/shortcode.stub';
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
		$namespace = $rootNamespace . '\\WordPress\\Shortcodes';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to shortcode.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className    = $nameData['class'];
		$tag          = $this->option( 'tag' ) ?: Str::snake( $className );
		$allowContent = $this->optionBool( 'allow-content' );

		// Build field definitions (Metabox-compatible format)
		$fieldDefs   = $this->buildFieldDefinitions();
		$defaultDefs = $this->buildDefaultDefinitions();

		// Build a shortcode usage example
		$usageExample = $allowContent
			? "[{$tag}]Your content here[/{$tag}]"
			: "or [{$tag} title=\"Example\" /]";

		$replacements = [
			'{{ shortcode_tag }}'          => $tag,
			'{{ allow_content }}'          => $allowContent ? 'true' : 'false',
			'{{ field_definitions }}'      => $fieldDefs,
			'{{ default_definitions }}'    => $defaultDefs,
			'{{ shortcode_with_content }}' => $usageExample,
			'{{ title }}'                  => $nameData['title'],
			'{{ description }}'            => sprintf( 'Renders the [%s] shortcode.', $tag ),
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		return $this;
	}

	/**
	 * Build field definitions in Metabox-compatible format.
	 *
	 * @return string Formatted field definitions.
	 */
	private function buildFieldDefinitions(): string {
		$attrs = $this->option( 'attr' );
		if ( empty( $attrs ) ) {
			return '';
		}

		$definitions = [];
		foreach ( $attrs as $attr ) {
			$def = $this->parseFieldDefinition( $attr );
			if ( $def !== null ) {
				$definitions[] = $def;
			}
		}

		return implode( "\n", $definitions );
	}

	/**
	 * Build default definitions for a simple mode.
	 *
	 * @return string Formatted default definitions.
	 */
	private function buildDefaultDefinitions(): string {
		$attrs = $this->option( 'attr' );
		if ( empty( $attrs ) ) {
			return '';
		}

		$defaults = [];
		foreach ( $attrs as $attr ) {
			$parts = explode( ':', $attr, 4 );
			if ( count( $parts ) >= 2 ) {
				$name       = $parts[1];
				$default    = $parts[2] ?? '';
				$defaults[] = "        '{$name}' => '{$default}',";
			}
		}

		return implode( "\n", $defaults );
	}

	/**
	 * Parse a single field definition in Metabox-compatible format.
	 *
	 * Format: type:name:default:label
	 *
	 * @param string $attr The attribute string.
	 *
	 * @return string|null Formatted definition or null.
	 */
	private function parseFieldDefinition( string $attr ): ?string {
		$parts = array_map( 'trim', explode( ':', $attr, 4 ) );

		if ( count( $parts ) < 2 ) {
			$this->warn( "Invalid attribute format: '{$attr}'. Use type:name:default:label" );

			return null;
		}

		[ $type, $name, $default, $label ] = array_pad( $parts, 4, null );

		// Map input types to Field System types
		$fieldType = $this->mapToFieldType( $type );
		$default   = $default ?? $this->getDefaultForType( $type );
		$label     = $label ?? ucfirst( str_replace( '_', ' ', $name ) );

		// Validate type
		$validTypes = [ 'string', 'text', 'textarea', 'html', 'wysiwyg', 'integer', 'number', 'float', 'boolean', 'bool', 'checkbox', 'select', 'color', 'image', 'media', 'url', 'email' ];
		if ( ! in_array( $type, $validTypes, true ) ) {
			$this->warn( "Unknown attribute type '{$type}'. Valid types: " . implode( ', ', $validTypes ) );
		}

		// Format default value
		$defaultFormatted = $this->formatDefaultValue( $default, $type );

		// Escape values for PHP strings
		$safeName  = str_replace( [ '\\', '\'' ], [ '\\\\', '\\\'' ], (string) $name );
		$safeLabel = str_replace( [ '\\', '\'' ], [ '\\\\', '\\\'' ], (string) $label );

		// Generate Metabox-style field definition
		$definition = "\t\t\t[\n";
		$definition .= "\t\t\t\t'type'    => '{$fieldType}',\n";
		$definition .= "\t\t\t\t'id'      => '{$safeName}',\n";
		$definition .= "\t\t\t\t'label'   => __( '{$safeLabel}', '{{ textdomain }}' ),\n";
		$definition .= "\t\t\t\t'default' => {$defaultFormatted},\n";
		$definition .= "\t\t\t],";

		return $definition;
	}

	/**
	 * Map the input type to a Field System type.
	 *
	 * @param string $type The input type.
	 *
	 * @return string The Field System type.
	 */
	private function mapToFieldType( string $type ): string {
		return match ( $type ) {
			'string', 'text' => 'text',
			'textarea' => 'textarea',
			'html', 'wysiwyg' => 'wysiwyg',
			'integer', 'int', 'number', 'float' => 'number',
			'boolean', 'bool', 'checkbox' => 'checkbox',
			'select' => 'select',
			'color' => 'color',
			'image', 'media' => 'image',
			'url' => 'url',
			'email' => 'email',
			default => 'text',
		};
	}

	/**
	 * Get the default value for a type.
	 *
	 * @param string $type The type.
	 *
	 * @return string Default value.
	 */
	private function getDefaultForType( string $type ): string {
		return match ( $type ) {
			'integer', 'number', 'float' => '0',
			'boolean', 'bool' => 'false',
			default => '',
		};
	}

	/**
	 * Format default value for PHP output.
	 *
	 * @param string $value The value.
	 * @param string $type The type.
	 *
	 * @return string Formatted value.
	 */
	private function formatDefaultValue( string $value, string $type ): string {
		return match ( $type ) {
			'integer' => (string) (int) $value,
			'number', 'float' => (string) (float) $value,
			'boolean', 'bool' => in_array( strtolower( $value ), [ 'true', '1', 'yes' ], true ) ? 'true' : 'false',
			default => "'{$value}'",
		};
	}

	/**
	 * Generate view template.
	 *
	 * @param array<string, mixed> $nameData Name data.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	private function generateViewTemplate( array $nameData ): void {
		$tag      = $this->option( 'tag' ) ?: Str::snake( $nameData['class'] );
		$viewPath = $this->app->basePath( "resources/views/shortcodes/{$tag}.blade.php" );

		if ( $this->alreadyExists( $viewPath ) && ! $this->optionBool( 'force' ) ) {
			$this->warn( "View template already exists: {$this->getRelativePath($viewPath)}" );

			return;
		}

		$stub = $this->files->get( $this->resolveStubPath( 'wp/frontend/shortcode-view.blade.stub' ) );
		$stub = str_replace(
			[ '{{ shortcode_tag }}', '{{ class }}', '{{ namespace }}' ],
			[ $tag, $nameData['class'], $this->getDefaultNamespace( $this->rootNamespace(), $nameData['path'] ) ],
			$stub
		);

		$this->makeDirectory( $viewPath );
		$this->files->put( $viewPath, $stub );
		$this->generatedFiles['view'] = $viewPath;

		$this->info( "View template [{$this->getRelativePath($viewPath)}] created." );
	}

	/**
	 * Generate Elementor widget adapter.
	 *
	 * @param array<string, mixed> $nameData Name data.
	 * @param string $qualifiedName Qualified class name.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	private function generateElementorWidget( array $nameData, string $qualifiedName ): void {
		$tag           = $this->option( 'tag' ) ?: Str::snake( $nameData['class'] );
		$namespace     = str_replace( '\\\\', '\\', $this->getDefaultNamespace( $this->rootNamespace(), $nameData['path'] ) );
		$elementorPath = str_replace( '.php', '', $this->getPath( $qualifiedName ) );
		$elementorPath = dirname( $elementorPath ) . '/Elementor/' . $nameData['class'] . 'Widget.php';

		if ( $this->alreadyExists( $elementorPath ) && ! $this->optionBool( 'force' ) ) {
			$this->warn( "Elementor widget already exists: {$this->getRelativePath($elementorPath)}" );

			return;
		}

		$stub = $this->files->get( $this->resolveStubPath( 'wp/frontend/shortcode-elementor.stub' ) );

		$replacements = [
			'{{ namespace }}'      => $namespace,
			'{{ class }}'          => str_replace( '\\\\', '\\', $nameData['class'] ),
			'{{ shortcode_fqcn }}' => str_replace( '\\\\', '\\', $qualifiedName ),
			'{{ widget_name }}'    => wpj_config( 'app.slug' ) . '_' . $tag,
			'{{ shortcode_tag }}'  => $tag,
			'{{ title }}'          => $nameData['title'],
			'{{ slug }}'           => $nameData['slug'],
			'{{ textdomain }}'     => $this->getTextDomain(),
			'{{ category }}'       => 'general',
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		$this->makeDirectory( $elementorPath );
		$this->files->put( $elementorPath, $stub );
		$this->generatedFiles['elementor'] = $elementorPath;

		$this->info( "Elementor widget [{$this->getRelativePath($elementorPath)}] created." );
	}

	/**
	 * Generate WPBakery element adapter.
	 *
	 * @param array<string, mixed> $nameData Name data.
	 * @param string $qualifiedName Qualified class name.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	private function generateWPBakeryElement( array $nameData, string $qualifiedName ): void {
		$namespace    = $this->getNamespace( $qualifiedName );
		$wpbakeryPath = str_replace( '.php', '', $this->getPath( $qualifiedName ) );
		$wpbakeryPath = dirname( $wpbakeryPath ) . '/WPBakery/' . $nameData['class'] . 'Element.php';

		if ( $this->alreadyExists( $wpbakeryPath ) && ! $this->optionBool( 'force' ) ) {
			$this->warn( "WPBakery element already exists: {$this->getRelativePath($wpbakeryPath)}" );

			return;
		}

		$stub = $this->files->get( $this->resolveStubPath( 'wp/frontend/shortcode-wpbakery.stub' ) );

		$replacements = [
			'{{ namespace }}'      => $namespace,
			'{{ class }}'          => str_replace( '\\\\', '\\', $nameData['class'] ),
			'{{ shortcode_fqcn }}' => str_replace( '\\\\', '\\', $qualifiedName ),
			'{{ textdomain }}'     => $this->getTextDomain(),
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		$this->makeDirectory( $wpbakeryPath );
		$this->files->put( $wpbakeryPath, $stub );
		$this->generatedFiles['wpbakery'] = $wpbakeryPath;

		$this->info( "WPBakery element [{$this->getRelativePath($wpbakeryPath)}] created." );
	}

	/**
	 * Generate TinyMCE editor button.
	 *
	 * @param array<string, mixed> $nameData Name data.
	 *
	 * @return void
	 * @throws \JsonException
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function generateEditorButton( array $nameData ): void {
		$tag    = $this->option( 'tag' ) ?: Str::snake( $nameData['class'] );
		$jsPath = $this->app->basePath( "resources/js/editor/{$tag}-button.js" );

		if ( $this->alreadyExists( $jsPath ) && ! $this->optionBool( 'force' ) ) {
			$this->warn( "Editor button JS already exists: {$this->getRelativePath($jsPath)}" );

			return;
		}

		$stub = $this->files->get( $this->resolveStubPath( 'wp/frontend/shortcode-editor.js.stub' ) );

		// Build attributes JSON for JS
		$attributesJson = $this->buildAttributesJsonForJs();

		$replacements = [
			'{{ shortcode_tag }}'    => $tag,
			'{{ class }}'            => $nameData['class'],
			'{{ namespace }}'        => str_replace( '\\\\', '\\', $this->getDefaultNamespace( $this->rootNamespace(), $nameData['path'] ) ),
			'{{ title }}'            => $nameData['title'],
			'{{ allow_content_js }}' => $this->optionBool( 'allow-content' ) ? 'true' : 'false',
			'{{ attributes_json }}'  => $attributesJson,
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		$this->makeDirectory( $jsPath );
		$this->files->put( $jsPath, $stub );
		$this->generatedFiles['editor'] = $jsPath;

		$this->info( "Editor button JS [{$this->getRelativePath($jsPath)}] created." );
	}

	/**
	 * Build attributes JSON for JavaScript.
	 *
	 * @return string JSON string.
	 * @throws \JsonException
	 */
	private function buildAttributesJsonForJs(): string {
		$attrs  = $this->option( 'attr' );
		$schema = [
			'id'    => [ 'type' => 'string', 'default' => '', 'description' => 'CSS ID' ],
			'class' => [ 'type' => 'string', 'default' => '', 'description' => 'CSS classes' ],
			'title' => [ 'type' => 'string', 'default' => '', 'description' => 'Title' ],
		];

		if ( ! empty( $attrs ) ) {
			foreach ( $attrs as $attr ) {
				$parts = explode( ':', $attr, 4 );
				if ( count( $parts ) >= 2 ) {
					$schema[ $parts[1] ] = [
						'type'        => $parts[0],
						'default'     => $parts[2] ?? '',
						'description' => $parts[3] ?? ucfirst( str_replace( '_', ' ', $parts[1] ) ),
					];
				}
			}
		}

		return json_encode( $schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT );
	}

	/**
	 * Display dry run output.
	 *
	 * @param array<string, mixed> $nameData Name data.
	 * @param string $qualifiedName Qualified name.
	 * @param string $path Main file path.
	 * @param bool $withEditor Generate editor button.
	 * @param bool $withElementor Generate Elementor widget.
	 * @param bool $withWPBakery Generate WPBakery element.
	 * @param bool $withView Generate view template.
	 * @param bool $simpleMode Use simple mode.
	 *
	 * @return void
	 */
	private function displayDryRunOutput(
		array $nameData,
		string $qualifiedName,
		string $path,
		bool $withEditor,
		bool $withElementor,
		bool $withWPBakery,
		bool $withView,
		bool $simpleMode
	): void {
		$tag = $this->option( 'tag' ) ?: Str::snake( $nameData['class'] );

		$this->info( '[DRY RUN] Would create the following files:' );
		$this->newLine();

		$this->line( '<comment>Main Shortcode:</comment>' );
		$this->line( "  • {$this->getRelativePath($path)}" );

		if ( $withView ) {
			$this->line( '<comment>View Template:</comment>' );
			$this->line( "  • resources/views/shortcodes/{$tag}.blade.php" );
		}

		if ( $withElementor && ! $simpleMode ) {
			$this->line( '<comment>Elementor Widget:</comment>' );
			$dir = dirname( $this->getRelativePath( $path ) );
			$this->line( "  • {$dir}/Elementor/{$nameData['class']}Widget.php" );
		}

		if ( $withWPBakery && ! $simpleMode ) {
			$this->line( '<comment>WPBakery Element:</comment>' );
			$dir = dirname( $this->getRelativePath( $path ) );
			$this->line( "  • {$dir}/WPBakery/{$nameData['class']}Element.php" );
		}

		if ( $withEditor && ! $simpleMode ) {
			$this->line( '<comment>Editor Button JS:</comment>' );
			$this->line( "  • resources/js/editor/{$tag}-button.js" );
		}

		$this->newLine();
		$this->displayGeneratedCode( $qualifiedName, $nameData, $path );
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
		$tag          = $this->option( 'tag' ) ?: Str::snake( $nameData['class'] );
		$allowContent = $this->optionBool( 'allow-content' );
		$simpleMode   = $this->optionBool( 'simple' );
		$namespace    = $this->getNamespace( $qualifiedName );

		// Beautiful separator
		$this->newLine();
		$this->line( '<fg=green>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>' );
		$this->newLine();

		// Auto-discovery notice
		$this->line( '<fg=cyan>✓ AUTO-DISCOVERY ENABLED</>' );
		$this->line( '  Shortcodes in <comment>app/WordPress/Shortcodes/</comment> are automatically registered.' );
		$this->line( '  No manual registration needed!' );
		$this->newLine();

		// Generated files summary table
		$this->line( '<fg=yellow>┌─────────────────────────────────────────────────────────────────────────────┐</>' );
		$this->line( '<fg=yellow>│</> <fg=white;options=bold>Generated Files</>                                                           <fg=yellow>│</>' );
		$this->line( '<fg=yellow>├─────────────────────────────────────────────────────────────────────────────┤</>' );
		$this->line( '<fg=yellow>│</> <fg=green>✓</> Shortcode     <fg=gray>' . str_pad( $this->getRelativePath( $path ), 55 ) . '</> <fg=yellow>│</>' );

		if ( isset( $this->generatedFiles['elementor'] ) ) {
			$this->line( '<fg=yellow>│</> <fg=green>✓</> Elementor     <fg=gray>' . str_pad( $this->getRelativePath( $this->generatedFiles['elementor'] ), 55 ) . '</> <fg=yellow>│</>' );
		}
		if ( isset( $this->generatedFiles['wpbakery'] ) ) {
			$this->line( '<fg=yellow>│</> <fg=green>✓</> WPBakery      <fg=gray>' . str_pad( $this->getRelativePath( $this->generatedFiles['wpbakery'] ), 55 ) . '</> <fg=yellow>│</>' );
		}
		if ( isset( $this->generatedFiles['editor'] ) ) {
			$this->line( '<fg=yellow>│</> <fg=green>✓</> Editor JS     <fg=gray>' . str_pad( $this->getRelativePath( $this->generatedFiles['editor'] ), 55 ) . '</> <fg=yellow>│</>' );
		}
		if ( isset( $this->generatedFiles['view'] ) ) {
			$this->line( '<fg=yellow>│</> <fg=green>✓</> View          <fg=gray>' . str_pad( $this->getRelativePath( $this->generatedFiles['view'] ), 55 ) . '</> <fg=yellow>│</>' );
		}

		$this->line( '<fg=yellow>└─────────────────────────────────────────────────────────────────────────────┘</>' );
		$this->newLine();

		// Usage example
		$this->line( '<fg=white;options=bold>Usage:</>' );
		$this->newLine();
		$usage = $allowContent
			? "  <fg=cyan>[{$tag}]</>Your content here<fg=cyan>[/{$tag}]</>"
			: "  <fg=cyan>[{$tag} title=\"Example\"]</>";
		$this->line( $usage );
		$this->newLine();

		// Show Elementor registration if generated
		if ( isset( $this->generatedFiles['elementor'] ) ) {
			$this->line( '<fg=magenta>Elementor Registration:</> <comment>(optional - already auto-discoverable)</comment>' );
			$this->line( "  use {$namespace}\\Elementor\\{$nameData['class']}Widget;" );
			$this->line( '  $widgets_manager->register(new ' . $nameData['class'] . 'Widget());' );
			$this->newLine();
		}

		// Show WPBakery registration if generated
		if ( isset( $this->generatedFiles['wpbakery'] ) ) {
			$this->line( '<fg=blue>WPBakery Registration:</> <comment>(in vc_before_init hook)</comment>' );
			$this->line( "  use {$namespace}\\WPBakery\\{$nameData['class']}Element;" );
			$this->line( '  (new ' . $nameData['class'] . 'Element())->register();' );
			$this->newLine();
		}

		$this->line( '<fg=green>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>' );
		$this->newLine();
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'FeatureCard';
	}
}
