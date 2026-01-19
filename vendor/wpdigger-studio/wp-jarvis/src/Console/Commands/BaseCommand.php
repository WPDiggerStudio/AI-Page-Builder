<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use WPJarvis\Framework\Application;

/**
 * BaseCommand - Foundation for all generator commands.
 *
 * Provides shared functionality for file generation, stub parsing,
 * namespace resolution, and post-generation tasks.
 *
 * @package WPJarvis\Framework\Console\Commands
 */
abstract class BaseCommand extends Command {
	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected Application $app;

	/**
	 * The filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected Filesystem $files;

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Class';

	/**
	 * Reserved names that cannot be used.
	 *
	 * @var array<string>
	 */
	protected array $reservedNames = [
		'__halt_compiler',
		'abstract',
		'and',
		'array',
		'as',
		'break',
		'callable',
		'case',
		'catch',
		'class',
		'clone',
		'const',
		'continue',
		'declare',
		'default',
		'die',
		'do',
		'echo',
		'else',
		'elseif',
		'empty',
		'enddeclare',
		'endfor',
		'endforeach',
		'endif',
		'endswitch',
		'endwhile',
		'eval',
		'exit',
		'extends',
		'final',
		'finally',
		'fn',
		'for',
		'foreach',
		'function',
		'global',
		'goto',
		'if',
		'implements',
		'include',
		'include_once',
		'instanceof',
		'insteadof',
		'interface',
		'isset',
		'list',
		'match',
		'namespace',
		'new',
		'or',
		'print',
		'private',
		'protected',
		'public',
		'readonly',
		'require',
		'require_once',
		'return',
		'static',
		'switch',
		'throw',
		'trait',
		'try',
		'unset',
		'use',
		'var',
		'while',
		'xor',
		'yield',
	];

	/**
	 * Create a new command instance.
	 *
	 * @param Application $app The application instance.
	 * @param Filesystem $files The filesystem instance.
	 */
	public function __construct( Application $app, Filesystem $files ) {
		parent::__construct();
		$this->app   = $app;
		$this->files = $files;
	}

	/**
	 * Execute console command.
	 *
	 * @return int Command exit code.
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\Illuminate\Contracts\Container\BindingResolutionException
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

		if ( $this->alreadyExists( $path ) && ! $this->option( 'force' ) ) {
			$this->error( $this->type . ' already exists!' );

			return self::FAILURE;
		}

		$this->makeDirectory( $path );

		if ( $this->isDryRun() ) {
			$this->info( sprintf( '[DRY RUN] Would create: %s', $this->getRelativePath( $path ) ) );
			$this->displayGeneratedCode( $qualifiedName, $nameData, $path );

			return self::SUCCESS;
		}

		$this->files->put( $path, $this->buildClass( $qualifiedName, $nameData ) );

		$this->info( sprintf( '%s [%s] created successfully.', $this->type, $this->getRelativePath( $path ) ) );

		$this->afterGeneration( $qualifiedName, $nameData, $path );

		return self::SUCCESS;
	}

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	abstract protected function getStub(): string;

	/**
	 * Get default namespace.
	 *
	 * @param string $rootNamespace The root namespace.
	 * @param string $subPath The sub-path for namespacing.
	 *
	 * @return string The full namespace.
	 */
	abstract protected function getDefaultNamespace( string $rootNamespace, string $subPath = '' ): string;

	/**
	 * Parse name input to extract path and class.
	 *
	 * @param string $name The input name.
	 *
	 * @return array<string, mixed> Parsed name data.
	 */
	protected function parseNameInput( string $name ): array {
		$name      = str_replace( '/', '\\', $name );
		$parts     = explode( '\\', $name );
		$className = array_pop( $parts );
		$subPath   = implode( '\\', $parts );

		$studlyClass = Str::studly( $className );

		return [
			'original'       => $name,
			'class'          => $studlyClass,
			'path'           => $subPath,
			'parts'          => $parts,
			'slug'           => Str::kebab( $className ),
			'snake'          => Str::snake( $className ),
			'camel'          => Str::camel( $className ),
			'studly'         => $studlyClass,
			'title'          => Str::headline( $className ),
			'singular'       => Str::singular( $studlyClass ),
			'plural'         => Str::plural( $studlyClass ),
			'singular_snake' => Str::snake( Str::singular( $className ) ),
			'plural_snake'   => Str::snake( Str::plural( $className ) ),
		];
	}

	/**
	 * Get name input.
	 *
	 * @return string The name argument value.
	 */
	protected function getNameInput(): string {
		return trim( $this->argument( 'name' ) );
	}

	/**
	 * Check if the name is reserved.
	 *
	 * @param string $name The name to check.
	 *
	 * @return bool True if reserved.
	 */
	protected function isReservedName( string $name ): bool {
		return in_array( strtolower( $name ), $this->reservedNames, true );
	}

	/**
	 * Check if a file exists.
	 *
	 * @param string $path The file path.
	 *
	 * @return bool True if a file exists.
	 */
	protected function alreadyExists( string $path ): bool {
		return $this->files->exists( $path );
	}

	/**
	 * Build class content.
	 *
	 * @param string $name The fully qualified class name.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return string The generated class content.
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|\Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function buildClass( string $name, array $nameData ): string {
		$stub = $this->files->get( $this->resolveStubPath( $this->getStub() ) );

		return $this->replaceNamespace( $stub, $name )
		            ->replaceClass( $stub, $name )
		            ->performReplacements( $stub, $nameData )
		            ->finalizeStub( $stub );
	}

	/**
	 * Replace namespace in stub.
	 *
	 * @param string &$stub The stub content.
	 * @param string $name The class name.
	 *
	 * @return static For chaining.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function replaceNamespace( string &$stub, string $name ): static {
		$namespace = $this->getNamespace( $name );

		$searches = [
			'{{ namespace }}',
			'{{namespace}}',
			'DummyNamespace',
			'{{ rootNamespace }}',
			'{{rootNamespace}}',
			'DummyRootNamespace',
		];

		$replacements = [
			$namespace,
			$namespace,
			$namespace,
			$this->rootNamespace(),
			$this->rootNamespace(),
			$this->rootNamespace(),
		];

		$stub = str_replace( $searches, $replacements, $stub );

		return $this;
	}

	/**
	 * Replace the class name in stub.
	 *
	 * @param string &$stub The stub content.
	 * @param string $name The class name.
	 *
	 * @return static For chaining.
	 */
	protected function replaceClass( string &$stub, string $name ): static {
		$class = class_basename( $name );

		$stub = str_replace(
			[ '{{ class }}', '{{class}}', 'DummyClass' ],
			$class,
			$stub
		);

		return $this;
	}

	/**
	 * Perform additional replacements (override in child classes).
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		$replacements = [
			'{{ slug }}'           => $nameData['slug'],
			'{{ snake }}'          => $nameData['snake'],
			'{{ kebab }}'          => $nameData['slug'],
			'{{ camel }}'          => $nameData['camel'],
			'{{ studly }}'         => $nameData['studly'],
			'{{ title }}'          => $nameData['title'],
			'{{ singular }}'       => $nameData['singular'],
			'{{ plural }}'         => $nameData['plural'],
			'{{ singular_snake }}' => $nameData['singular_snake'],
			'{{ plural_snake }}'   => $nameData['plural_snake'],
			'{{ textdomain }}'     => $this->getTextDomain(),
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		return $this;
	}

	/**
	 * Finalize stub content.
	 *
	 * @param string &$stub The stub content.
	 *
	 * @return string The finalized stub.
	 */
	protected function finalizeStub( string &$stub ): string {
		if ( ! str_starts_with( trim( $stub ), '<?php' ) ) {
			$stub = "<?php\n\n" . $stub;
		}

		// Clean up any remaining placeholders
		$stub = preg_replace( '/\{\{\s*\w+\s*\}\}/', '', $stub );

		return $stub;
	}

	/**
	 * Resolve the stub path with fallback.
	 *
	 * @param string $stub The stub name.
	 *
	 * @return string The resolved stub path.
	 * @throws \RuntimeException If stub not found.
	 */
	protected function resolveStubPath( string $stub ): string {
		// Check for custom stubs in the app
		$customPath = $this->app->basePath( 'stubs/' . $stub );

		if ( $this->files->exists( $customPath ) ) {
			return $customPath;
		}

		// Fall back to framework stubs
//		$frameworkPath = $this->app->vendorPath( 'wp-jarvis/framework/resources/stubs/' . $stub );
		$frameworkPath = dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $stub;

		if ( $this->files->exists( $frameworkPath ) ) {
			return $frameworkPath;
		}

		throw new \RuntimeException( "Stub not found: {$stub}" );
	}

	/**
	 * Qualify class name.
	 *
	 * @param string $class The class name.
	 * @param string $subPath The sub-path.
	 *
	 * @return string The fully qualified class name.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function qualifyClass( string $class, string $subPath = '' ): string {
		$rootNamespace = $this->rootNamespace();
		$namespace     = $this->getDefaultNamespace( $rootNamespace, $subPath );

		return $namespace . '\\' . $class;
	}

	/**
	 * Get full namespace.
	 *
	 * @param string $name The class name.
	 *
	 * @return string The namespace.
	 */
	protected function getNamespace( string $name ): string {
		$name = str_replace( '\\\\', '\\', $name ); // turn \\ into \
		$name = ltrim( $name, '\\' );

		$pos = strrpos( $name, '\\' );

		return $pos === false ? '' : substr( $name, 0, $pos );
	}

	/**
	 * Get root namespace.
	 *
	 * @return string The root namespace.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function rootNamespace(): string {
		return $this->app->getNamespace() ?? 'App';
	}

	/**
	 * Get text domain.
	 *
	 * @return string The text domain.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getTextDomain(): string {
		return $this->app->make( 'config' )->get( 'app.textdomain', 'wp-jarvis' );
	}

	/**
	 * Get a file path.
	 *
	 * @param string $name The class name.
	 *
	 * @return string The file path.
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	protected function getPath( string $name ): string {
		$name         = ltrim( Str::replaceFirst( $this->rootNamespace(), '', $name ), '\\' );
		$relativePath = str_replace( '\\', '/', $name ) . '.php';

		return $this->app->basePath( 'app/' . $relativePath );
	}

	/**
	 * Get a relative path for display.
	 *
	 * @param string $path The absolute path.
	 *
	 * @return string The relative path.
	 */
	protected function getRelativePath( string $path ): string {
		$basePath = $this->app->basePath();

		return str_replace( $basePath . DIRECTORY_SEPARATOR, '', $path );
	}

	/**
	 * Create directory.
	 *
	 * @param string $path The file path.
	 *
	 * @return void
	 */
	protected function makeDirectory( string $path ): void {
		if ( ! $this->files->isDirectory( dirname( $path ) ) ) {
			$this->files->makeDirectory( dirname( $path ), 0755, true );
		}
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
		// Override in child classes for post-generation tasks
	}

	/**
	 * Display generated code for preview.
	 *
	 * @param string $qualifiedName The fully qualified class name.
	 * @param array<string, mixed> $nameData The parsed name data.
	 * @param string $path The file path.
	 *
	 * @return void
	 */
	protected function displayGeneratedCode( string $qualifiedName, array $nameData, string $path ): void {
		$this->newLine();
		$this->info( '<comment>Generated code preview:</comment>' );
		$this->newLine();

		try {
			$code = $this->buildClass( $qualifiedName, $nameData );
			$this->line( $code );
		} catch ( \Throwable $e ) {
			$this->error( 'Failed to generate code preview: ' . $e->getMessage() );
		}

		$this->newLine();
	}

	/**
	 * Check if running in dry-run mode.
	 *
	 * @return bool True if dry-run mode is enabled.
	 */
	protected function isDryRun(): bool {
		// Check if the option is defined before accessing it
		$definition = $this->getDefinition();
		if ( ! $definition->hasOption( 'dry-run' ) ) {
			return false;
		}

		return $this->option( 'dry-run' ) === true;
	}

	/**
	 * Prompt for missing input arguments using returned questions.
	 *
	 * @return array<string, array<string>> The prompt configuration.
	 */
	protected function promptForMissingArgumentsUsing(): array {
		return [
			'name' => [
				'What should ' . strtolower( $this->type ) . ' be named?',
				'E.g. ' . $this->getExampleName(),
			],
		];
	}

	/**
	 * Get example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'Example' . $this->type;
	}

	/**
	 * Get option value with type casting.
	 *
	 * @param string $key The option key.
	 *
	 * @return bool The boolean option value.
	 */
	protected function optionBool( string $key ): bool {
		return (bool) $this->option( $key );
	}

	/**
	 * Get option value with default.
	 *
	 * @param string $key The option key.
	 * @param mixed $default The default value.
	 *
	 * @return mixed The option value or default.
	 */
	protected function optionWithDefault( string $key, mixed $default = null ): mixed {
		return $this->option( $key ) ?? $default;
	}

	/**
	 * Get verbosity level.
	 *
	 * @return int The verbosity level (0=normal, 1=verbose, 2=more verbose, 3=debug).
	 */
	protected function getVerbosity(): int {
		return $this->getOutput()->getVerbosity();
	}

	/**
	 * Check if the output is verbose.
	 *
	 * @return bool True if verbose mode is enabled.
	 */
	protected function isVerbose(): bool {
		return $this->getVerbosity() >= 1;
	}

	/**
	 * Check if the output is very verbose.
	 *
	 * @return bool True if a very verbose mode is enabled.
	 */
	protected function isVeryVerbose(): bool {
		return $this->getVerbosity() >= 2;
	}

	/**
	 * Check if output is debug.
	 *
	 * @return bool True if debug mode is enabled.
	 */
	protected function isDebug(): bool {
		return $this->getVerbosity() >= 3;
	}

	/**
	 * Write verbose output if enabled.
	 *
	 * @param string $message The message to write.
	 *
	 * @return void
	 */
	protected function verbose( string $message ): void {
		if ( $this->isVerbose() ) {
			$this->line( "<info>{$message}</info>" );
		}
	}

	/**
	 * Write very verbose output if enabled.
	 *
	 * @param string $message The message to write.
	 *
	 * @return void
	 */
	protected function veryVerbose( string $message ): void {
		if ( $this->isVeryVerbose() ) {
			$this->line( "<comment>{$message}</comment>" );
		}
	}

	/**
	 * Write debug output if enabled.
	 *
	 * @param string $message The message to write.
	 *
	 * @return void
	 */
	protected function debug( string $message ): void {
		if ( $this->isDebug() ) {
			$this->line( "<fg=cyan>{$message}</>" );
		}
	}

	/**
	 * Validate an option value.
	 *
	 * @param string $key The option key.
	 * @param array<string>|callable|null $allowed The allowed values or validation callback.
	 * @param string|null $errorMessage The custom error message.
	 *
	 * @return bool True if valid.
	 * @throws \InvalidArgumentException If invalid.
	 */
	protected function validateOption( string $key, array|callable|null $allowed = null, ?string $errorMessage = null ): bool {
		$value = $this->option( $key );

		if ( $value === null ) {
			return true;
		}

		if ( is_callable( $allowed ) ) {
			$result = $allowed( $value );
			if ( $result !== true ) {
				throw new \InvalidArgumentException( $errorMessage ?? "Invalid value for --{$key}: {$value}" );
			}

			return true;
		}

		if ( is_array( $allowed ) && ! in_array( $value, $allowed, true ) ) {
			throw new \InvalidArgumentException( "Invalid value for --{$key}: {$value}. Allowed values: " . implode( ', ', $allowed ) );
		}

		return true;
	}
}
