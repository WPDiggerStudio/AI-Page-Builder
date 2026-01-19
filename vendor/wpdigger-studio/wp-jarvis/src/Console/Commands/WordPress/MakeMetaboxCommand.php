<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeMetaboxCommand - Creates a new metabox class.
 *
 * Generates a metabox class with Field System integration,
 * nonce handling, and proper storage abstraction.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeMetaboxCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:metabox
                            {name : The name of metabox class}
                            {--id= : Metabox ID (defaults to snake_case class name)}
                            {--title= : Metabox title (defaults to headline class name)}
                            {--post-types=* : Post types to attach to (defaults to post)}
                            {--context=normal : Context (normal, side, advanced)}
                            {--priority=default : Priority (high, core, default, low)}
                            {--field=* : Add field definitions (format: type:name:default:description)}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new metabox class with Field System integration';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Metabox';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/content/metabox.stub';
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
		$namespace = $rootNamespace . '\\WordPress\\Metaboxes';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to metabox.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className = $nameData['class'];
		$id        = $this->option( 'id' ) ?: Str::snake( $className );
		$title     = $this->option( 'title' ) ?: Str::headline( $className );
		$context   = $this->option( 'context' ) ?: 'normal';
		$priority  = $this->option( 'priority' ) ?: 'default';

		// Validate context
		$validContexts = [ 'normal', 'side', 'advanced' ];
		if ( ! in_array( $context, $validContexts, true ) ) {
			throw new \InvalidArgumentException( "Invalid context '{$context}'. Valid values: " . implode( ', ', $validContexts ) );
		}

		// Validate priority
		$validPriorities = [ 'high', 'core', 'default', 'low' ];
		if ( ! in_array( $priority, $validPriorities, true ) ) {
			throw new \InvalidArgumentException( "Invalid priority '{$priority}'. Valid values: " . implode( ', ', $validPriorities ) );
		}

		$postTypes = $this->option( 'post-types' );
		if ( empty( $postTypes ) ) {
			$postTypes = [ 'post' ];
		}
		$postTypesString = "['" . implode( "', '", $postTypes ) . "']";

		// Build field definitions from --field options
		$fields = $this->parseFieldDefinitions();

		$replacements = [
			'{{ metabox_id }}'        => $id,
			'{{ metabox_title }}'     => $title,
			'{{ post_types }}'        => $postTypesString,
			'{{ context }}'           => $context,
			'{{ priority }}'          => $priority,
			'{{ nonce_action }}'      => $id . '_nonce_action',
			'{{ nonce_field }}'       => $id . '_nonce',
			'{{ field_definitions }}' => $fields,
		];

		foreach ( $replacements as $search => $replace ) {
			$stub = str_replace( $search, $replace, $stub );
		}

		return $this;
	}

	/**
	 * Parse field definitions from command options.
	 *
	 * @return string The formatted field definitions.
	 */
	private function parseFieldDefinitions(): string {
		$fields = $this->option( 'field' );
		if ( empty( $fields ) ) {
			return '';
		}

		$definitions = [];
		foreach ( $fields as $field ) {
			$parts = explode( ':', $field, 3 );
			if ( count( $parts ) < 2 ) {
				continue;
			}

			[ $type, $name, $rest ] = $parts;
			$definition = $this->buildFieldDefinition( $type, $name, $rest );
			if ( $definition !== null ) {
				$definitions[] = $definition;
			}
		}

		return implode( "\n            ", $definitions );
	}

	/**
	 * Build a single field definition.
	 *
	 * @param string $type The field type.
	 * @param string $name The field name.
	 * @param string $rest Additional field parameters.
	 *
	 * @return string|null The field definition or null if invalid.
	 */
	private function buildFieldDefinition( string $type, string $name, string $rest ): ?string {
		$params         = [];
		$hasDefault     = false;
		$hasDescription = false;
		$description    = '';

		// Parse additional parameters
		$parts = explode( ',', $rest );
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( str_starts_with( $part, 'default:' ) ) {
				$default    = substr( $part, 8 );
				$params[]   = "'default' => {$default}";
				$hasDefault = true;
			} elseif ( str_starts_with( $part, 'description:' ) ) {
				$description    = substr( $part, 12 );
				$params[]       = "'description' => {$description}";
				$hasDescription = true;
			} elseif ( ! empty( $part ) ) {
				$params[] = "'{$part}'";
			}
		}

		// Validate a field type
		$validTypes = [ 'text', 'textarea', 'checkbox', 'select', 'number', 'email', 'url', 'date', 'time', 'color', 'image', 'file', 'wysiwyg' ];
		if ( ! in_array( $type, $validTypes, true ) ) {
			$this->warn( "Invalid field type '{$type}'. Valid types: " . implode( ', ', $validTypes ) );

			return null;
		}

		$definition = "->{$type}('{$name}'";

		if ( ! empty( $params ) ) {
			$definition .= ', ' . implode( ', ', $params );
		}

		$definition .= ')';

		// Add comment if description provided
		if ( $hasDescription ) {
			$definition = "// {$description}\n        " . $definition;
		}

		return $definition;
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
		$id        = $this->option( 'id' ) ?: Str::snake( $className );
		$title     = $this->option( 'title' ) ?: Str::headline( $className );
		$context   = $this->option( 'context' ) ?: 'normal';
		$priority  = $this->option( 'priority' ) ?: 'default';
		$postTypes = $this->option( 'post-types' );
		if ( empty( $postTypes ) ) {
			$postTypes = [ 'post' ];
		}
		$namespace = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Metabox Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>  ' . $namespace );
		$this->line( '  <comment>ID:</comment>         ' . $id );
		$this->line( '  <comment>Title:</comment>      ' . $title );
		$this->line( '  <comment>Context:</comment>    ' . $context );
		$this->line( '  <comment>Priority:</comment>   ' . $priority );
		$this->line( '  <comment>Post Types:</comment> ' . implode( ', ', $postTypes ) );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This metabox will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'PostDetails';
	}
}
