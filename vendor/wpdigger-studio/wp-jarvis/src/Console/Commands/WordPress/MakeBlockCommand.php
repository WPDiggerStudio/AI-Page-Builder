<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeBlockCommand - Creates a new Gutenberg block with block. Json-first architecture.
 *
 * Generates a complete block folder with:
 * - PHP class file
 * - block.json metadata
 * - render.php template
 * - Optional: index.js, edit.js, style.css, editor.css for editor UI
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeBlockCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:block
                            {name : The name of block class}
                            {--slug= : Block slug (defaults to kebab-case class name)}
                            {--title= : Block title (defaults to headline class name)}
                            {--description= : Block description}
                            {--category=widgets : Block category}
                            {--icon=block-default : Block icon}
                            {--attribute=* : Add attribute definitions (format: name:type:default)}
                            {--support=* : Add block supports (e.g., align, anchor, html)}
                            {--keyword=* : Add search keywords}
                            {--namespace=wpjarvis : Block namespace}
                            {--with-editor : Generate JavaScript editor files (index.js, edit.js, CSS)}
                            {--no-render : Skip render.php generation (for static blocks)}
                            {--dry-run : Preview generated code without creating files}
                            {--force : Overwrite existing files}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Gutenberg block with block.json-first architecture';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Block';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \JsonException
	 */
	public function handle(): int {
		$name      = $this->argument( 'name' );
		$nameData  = $this->parseName( $name );
		$className = $nameData['class'];

		// Prepare block metadata
		$slug        = $this->option( 'slug' ) ?: Str::kebab( $className );
		$title       = $this->option( 'title' ) ?: Str::headline( $className );
		$description = $this->option( 'description' ) ?: "A custom {$title} block.";
		$category    = $this->option( 'category' ) ?: 'widgets';
		$icon        = $this->option( 'icon' ) ?: 'block-default';
		// Block type names must be all lowercase
		$blockNamespace = strtolower( wpj_config( 'app.slug' ) ?: 'wpjarvis' );
		$withEditor     = $this->optionBool( 'with-editor' );
		$noRender       = $this->optionBool( 'no-render' );
		$force          = $this->optionBool( 'force' );
		$dryRun         = $this->optionBool( 'dry-run' );

		// Build the block folder path
		$blockDir = $this->getBlockDirectory( $className );

		if ( ! $dryRun && ! $force && is_dir( $blockDir ) ) {
			$this->error( "Block folder already exists: {$blockDir}" );
			$this->line( '<comment>Use --force to overwrite.</comment>' );

			return 1;
		}

		// Prepare replacements
		$replacements = $this->buildReplacements( $nameData, [
			'block_slug'            => $slug,
			'block_title'           => $title,
			'block_description'     => $description,
			'block_category'        => $category,
			'block_icon'            => $icon,
			'block_namespace'       => $blockNamespace,
			'full_block_name'       => $blockNamespace . '/' . $slug,
			'textdomain'            => $this->getTextDomain(),
			'block_keywords_json'   => $this->buildKeywordsJson( $slug ),
			'block_supports_json'   => $this->buildSupportsJson(),
			'block_attributes_json' => $this->buildAttributesJson(),
		] );

		if ( $dryRun ) {
			return $this->showDryRun( $replacements, $withEditor, $noRender );
		}

		// Determine paths:
		// - PHP class file goes in: app/WordPress/Blocks/ClassName.php (for AutoDiscovery)
		// - Assets go in: app/WordPress/Blocks/ClassName/ subfolder
		$blocksDir = $this->getPluginPath() . '/app/WordPress/Blocks';
		$assetsDir = $blocksDir . '/' . $className;

		// Create directories if needed
		if ( ! is_dir( $blocksDir ) && ! mkdir( $blocksDir, 0755, true ) && ! is_dir( $blocksDir ) ) {
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $blocksDir ) );
		}
		if ( ! is_dir( $assetsDir ) && ! mkdir( $assetsDir, 0755, true ) && ! is_dir( $assetsDir ) ) {
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $assetsDir ) );
		}

		// Generate files
		$filesCreated = [];

		// 1. PHP class file (in parent Blocks folder for AutoDiscovery)
		$phpContent = $this->generateFromStub( 'wp/blocks/block.stub', $replacements );
		$phpPath    = $blocksDir . '/' . $className . '.php';
		file_put_contents( $phpPath, $phpContent );
		$filesCreated[] = $className . '.php';

		// 2. block.json (in assets subfolder)
		$blockJsonContent = $this->generateBlockJson( $replacements, $withEditor, $noRender );
		$blockJsonPath    = $assetsDir . '/block.json';
		file_put_contents( $blockJsonPath, $blockJsonContent );
		$filesCreated[] = $className . '/block.json';

		// 3. render.php (unless --no-render, in the assets' subfolder)
		if ( ! $noRender ) {
			$renderContent = $this->generateFromStub( 'wp/blocks/render.php.stub', $replacements );
			$renderPath    = $assetsDir . '/render.php';
			file_put_contents( $renderPath, $renderContent );
			$filesCreated[] = $className . '/render.php';
		}

		// 4. index.js (ALWAYS required for a block to appear in the editor inserter)
		$indexJsContent = $this->generateFromStub( 'wp/blocks/index.js.stub', $replacements );
		file_put_contents( $assetsDir . '/index.js', $indexJsContent );
		$filesCreated[] = $className . '/index.js';

		// 5. Additional editor files (if --with-editor)
		if ( $withEditor ) {
			// edit.js - separate edit component
			$editJsContent = $this->generateFromStub( 'wp/blocks/edit.js.stub', $replacements );
			file_put_contents( $assetsDir . '/edit.js', $editJsContent );
			$filesCreated[] = $className . '/edit.js';

			// style.css
			$styleCssContent = $this->generateFromStub( 'wp/blocks/style.css.stub', $replacements );
			file_put_contents( $assetsDir . '/style.css', $styleCssContent );
			$filesCreated[] = $className . '/style.css';

			// editor.css
			$editorCssContent = $this->generateFromStub( 'wp/blocks/editor.css.stub', $replacements );
			file_put_contents( $assetsDir . '/editor.css', $editorCssContent );
			$filesCreated[] = $className . '/editor.css';
		}

		// Output success message
		$this->info( "Block [{$this->getRelativeBlockPath($className)}] created successfully." );
		$this->printGenerationSummary( $className, $nameData, $assetsDir, $filesCreated, $withEditor );

		return 0;
	}

	/**
	 * Parse the given name into parts.
	 *
	 * @param string $name The raw name.
	 *
	 * @return array<string, mixed> The parsed name data.
	 */
	protected function parseName( string $name ): array {
		$name    = str_replace( '/', '\\', $name );
		$parts   = explode( '\\', $name );
		$class   = array_pop( $parts );
		$subPath = implode( '\\', $parts );

		return [
			'class'    => $class,
			'subPath'  => $subPath,
			'fullPath' => $name,
		];
	}

	/**
	 * Get the block directory path.
	 *
	 * @param string $className The class name.
	 *
	 * @return string The block directory path.
	 */
	protected function getBlockDirectory( string $className ): string {
		$basePath = $this->getPluginPath() . '/app/WordPress/Blocks';

		return $basePath . '/' . $className;
	}

	/**
	 * Get a relative path for display.
	 *
	 * @param string $className The class name.
	 *
	 * @return string The relative path.
	 */
	protected function getRelativeBlockPath( string $className ): string {
		return 'app/WordPress/Blocks/' . $className;
	}

	/**
	 * Build replacements array.
	 *
	 * @param array<string, mixed> $nameData The parsed name data.
	 * @param array<string, string> $extra Extra replacements.
	 *
	 * @return array<string, string> The replacements.
	 */
	protected function buildReplacements( array $nameData, array $extra = [] ): array {
		$rootNamespace = $this->getRootNamespace();
		$namespace     = $rootNamespace . '\\WordPress\\Blocks';

		$replacements = [
			'{{ namespace }}' => $namespace,
			'{{ class }}'     => $nameData['class'],
		];

		// Add extra replacements with {{ key }} format
		foreach ( $extra as $key => $value ) {
			$replacements[ '{{ ' . $key . ' }}' ] = $value;
		}

		return $replacements;
	}

	/**
	 * Get the plugin's root namespace.
	 *
	 * @return string The root namespace.
	 * @throws \JsonException
	 */
	protected function getRootNamespace(): string {
		// Try to get from composer.json
		$composerPath = $this->getPluginPath() . '/composer.json';
		if ( file_exists( $composerPath ) ) {
			$composer = json_decode( file_get_contents( $composerPath ), true, 512, JSON_THROW_ON_ERROR );
			if ( isset( $composer['autoload']['psr-4'] ) ) {
				$namespaces = array_keys( $composer['autoload']['psr-4'] );
				if ( ! empty( $namespaces ) ) {
					return rtrim( $namespaces[0], '\\' );
				}
			}
		}

		return 'App';
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string The plugin path.
	 */
	protected function getPluginPath(): string {
		return getcwd();
	}

	/**
	 * Get the text domain.
	 *
	 * @return string The text domain.
	 */
	protected function getTextDomain(): string {
		$pluginPath = $this->getPluginPath();

		return basename( $pluginPath );
	}

	/**
	 * Build keywords JSON.
	 *
	 * @param string $slug The block slug.
	 *
	 * @return string JSON array of keywords.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException|\JsonException
	 */
	protected function buildKeywordsJson( string $slug ): string {
		$keywords = $this->option( 'keyword' );
		if ( empty( $keywords ) ) {
			$keywords = [ $slug, 'custom', wpj_config( 'app.slug' ) ];
		}

		return json_encode( $keywords, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build supports JSON.
	 *
	 * @return string JSON object of supports.
	 * @throws \JsonException
	 */
	protected function buildSupportsJson(): string {
		$supports = [
			'html'   => false,
			'align'  => [ 'wide', 'full' ],
			'anchor' => true,
		];

		$customSupports = $this->option( 'support' );
		if ( ! empty( $customSupports ) ) {
			foreach ( $customSupports as $support ) {
				$parts            = explode( ':', $support, 2 );
				$key              = $parts[0];
				$value            = isset( $parts[1] ) ? $this->parseJsonValue( $parts[1] ) : true;
				$supports[ $key ] = $value;
			}
		}

		return json_encode( $supports, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build attributes JSON.
	 *
	 * @return string JSON object of attributes.
	 * @throws \JsonException
	 */
	protected function buildAttributesJson(): string {
		$attributes = [];

		$customAttributes = $this->option( 'attribute' );
		if ( ! empty( $customAttributes ) ) {
			foreach ( $customAttributes as $attr ) {
				$parts = explode( ':', $attr, 3 );
				if ( count( $parts ) < 2 ) {
					continue;
				}

				$name    = $parts[0];
				$type    = $this->mapAttributeType( $parts[1] );
				$default = isset( $parts[2] ) ? $this->parseJsonValue( $parts[2] ) : null;

				$attributes[ $name ] = [ 'type' => $type ];
				if ( $default !== null ) {
					$attributes[ $name ]['default'] = $default;
				}
			}
		}

		return json_encode( (object) $attributes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Map attribute type to Gutenberg type.
	 *
	 * @param string $type The input type.
	 *
	 * @return string The Gutenberg attribute type.
	 */
	protected function mapAttributeType( string $type ): string {
		return match ( strtolower( $type ) ) {
			'bool', 'boolean', 'checkbox' => 'boolean',
			'number', 'int', 'integer', 'float' => 'number',
			'array', 'list' => 'array',
			'object', 'json' => 'object',
			default => 'string',
		};
	}

	/**
	 * Parse a JSON-like value from string.
	 *
	 * @param string $value The string value.
	 *
	 * @return mixed The parsed value.
	 * @throws \JsonException
	 */
	protected function parseJsonValue( string $value ): mixed {
		// Try JSON decode first
		$decoded = json_decode( $value, true, 512, JSON_THROW_ON_ERROR );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded;
		}

		// Handle boolean strings
		if ( strtolower( $value ) === 'true' ) {
			return true;
		}
		if ( strtolower( $value ) === 'false' ) {
			return false;
		}

		// Handle numeric strings
		if ( is_numeric( $value ) ) {
			return str_contains( $value, '.' ) ? (float) $value : (int) $value;
		}

		return $value;
	}

	/**
	 * Generate content from stub file.
	 *
	 * @param string $stubPath Relative stub path.
	 * @param array<string, string> $replacements Replacements.
	 *
	 * @return string The generated content.
	 */
	protected function generateFromStub( string $stubPath, array $replacements ): string {
		$fullPath = $this->getStubsPath() . '/' . $stubPath;

		if ( ! file_exists( $fullPath ) ) {
			throw new \RuntimeException( "Stub file not found: {$fullPath}" );
		}

		$content = file_get_contents( $fullPath );

		foreach ( $replacements as $search => $replace ) {
			$content = str_replace( $search, $replace, $content );
		}

		return $content;
	}

	/**
	 * Generate block.json content.
	 *
	 * @param array<string, string> $replacements The replacements.
	 * @param bool $withEditor Whether to include editor scripts.
	 * @param bool $noRender Whether to skip render.
	 *
	 * @return string The block.json content.
	 * @throws \JsonException
	 */
	protected function generateBlockJson( array $replacements, bool $withEditor, bool $noRender ): string {
		// Decode attributes - keep as an object to ensure {} not [] when empty
		$attributes = json_decode( $replacements['{{ block_attributes_json }}'], true, 512, JSON_THROW_ON_ERROR );

		$blockJson = [
			'$schema'     => 'https://schemas.wp.org/trunk/block.json',
			'apiVersion'  => 3,
			'name'        => $replacements['{{ block_namespace }}'] . '/' . $replacements['{{ block_slug }}'],
			'version'     => '1.0.0',
			'title'       => $replacements['{{ block_title }}'],
			'category'    => $replacements['{{ block_category }}'],
			'icon'        => $replacements['{{ block_icon }}'],
			'description' => $replacements['{{ block_description }}'],
			'keywords'    => json_decode( $replacements['{{ block_keywords_json }}'], true, 512, JSON_THROW_ON_ERROR ),
			'supports'    => json_decode( $replacements['{{ block_supports_json }}'], true, 512, JSON_THROW_ON_ERROR ),
			'textdomain'  => $replacements['{{ textdomain }}'],
			'attributes'  => empty( $attributes ) ? new \stdClass() : $attributes,
		];

		// Add render only if not skipped
		if ( ! $noRender ) {
			$blockJson['render'] = 'file:./render.php';
		}

		// editorScript is ALWAYS required for a block to appear in the editor
		$blockJson['editorScript'] = 'file:./index.js';

		// Add extra editor assets if --with-editor
		if ( $withEditor ) {
			$blockJson['editorStyle'] = 'file:./editor.css';
			$blockJson['style']       = 'file:./style.css';
		}

		return json_encode( $blockJson, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Get the stubs' path.
	 *
	 * @return string The stubs' path.
	 */
	protected function getStubsPath(): string {
		// Framework stubs path
		return dirname( __DIR__, 4 ) . '/resources/stubs';
	}

	/**
	 * Show dry run output.
	 *
	 * @param array<string, string> $replacements The replacements.
	 * @param bool $withEditor Whether to include editor files.
	 * @param bool $noRender Whether to skip render.
	 *
	 * @return int Exit code.
	 * @throws \JsonException
	 */
	protected function showDryRun( array $replacements, bool $withEditor, bool $noRender ): int {
		$this->info( 'Dry run - files that would be created:' );
		$this->newLine();

		$className = $replacements['{{ class }}'];
		$this->line( "  <comment>{$className}/</comment>" );
		$this->line( "    ├── {$className}.php" );
		$this->line( "    ├── block.json" );

		if ( ! $noRender ) {
			$this->line( "    ├── render.php" );
		}

		if ( $withEditor ) {
			$this->line( "    ├── index.js" );
			$this->line( "    ├── edit.js" );
			$this->line( "    ├── style.css" );
			$this->line( "    └── editor.css" );
		}

		$this->newLine();
		$this->line( '<info>block.json preview:</info>' );
		$this->line( $this->generateBlockJson( $replacements, $withEditor, $noRender ) );

		return 0;
	}

	/**
	 * Print summary after block generation.
	 *
	 * @param string $className The class name.
	 * @param array<string, mixed> $nameData The parsed name data.
	 * @param string $blockDir The block directory.
	 * @param array<string> $filesCreated List of created files.
	 * @param bool $withEditor Whether editor files were created.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function printGenerationSummary(
		string $className,
		array $nameData,
		string $blockDir,
		array $filesCreated,
		bool $withEditor
	): void {
		$slug           = $this->option( 'slug' ) ?: Str::kebab( $className );
		$title          = $this->option( 'title' ) ?: Str::headline( $className );
		$blockNamespace = strtolower( wpj_config( 'app.slug' ) ?: 'wpjarvis' );

		$this->newLine();
		$this->line( '<info>Block Details:</info>' );
		$this->line( '  <comment>Name:</comment>       ' . $blockNamespace . '/' . $slug );
		$this->line( '  <comment>Title:</comment>      ' . $title );
		$this->line( '  <comment>Directory:</comment>  ' . $this->getRelativeBlockPath( $className ) );

		$this->newLine();
		$this->line( '<info>Files created:</info>' );
		foreach ( $filesCreated as $file ) {
			$this->line( '  <comment>•</comment> ' . $file );
		}

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This block will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );

		if ( $withEditor ) {
			$this->newLine();
			$this->line( '<info>Next steps for editor UI:</info>' );
			$this->line( '  1. Run <comment>npm install</comment> if not already done' );
			$this->line( '  2. Run <comment>npm run build</comment> to compile JavaScript' );
			$this->line( '  3. Customize <comment>edit.js</comment> for your block editor UI' );
		}
	}

	/**
	 * Check if an option is set to true.
	 *
	 * @param string $key The option name.
	 *
	 * @return bool Whether the option is true.
	 */
	protected function optionBool( string $key ): bool {
		return (bool) $this->option( $key );
	}

	/**
	 * Get a stub file path (for BaseCommand compatibility).
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/blocks/block.stub';
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
		$namespace = $rootNamespace . '\\WordPress\\Blocks';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'HeroSection';
	}
}
