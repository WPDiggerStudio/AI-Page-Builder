<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\WordPress;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeWidgetCommand - Creates a new widget class.
 *
 * Generates a widget class using the new Widget builder
 * with Field System integration for field definitions.
 *
 * @package WPJarvis\Framework\Console\Commands\WordPress
 */
class MakeWidgetCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:widget
                            {name : The name of widget class}
                            {--id= : Widget ID (defaults to snake_case class name)}
                            {--title= : Widget title (defaults to headline class name)}
                            {--description= : Widget description}
                            {--field=* : Add field definitions (format: type:name:default:description)}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new widget class using the new Widget builder';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Widget';

	/**
	 * Get a stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'wp/frontend/widget.stub';
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
		$namespace = $rootNamespace . '\\WordPress\\Widgets';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Perform additional replacements specific to the widget.
	 *
	 * @param string &$stub The stub content.
	 * @param array<string, mixed> $nameData The parsed name data.
	 *
	 * @return static For chaining.
	 */
	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		$className   = $nameData['class'];
		$id          = $this->option( 'id' ) ?: Str::snake( $className ) . '_widget';
		$title       = $this->option( 'title' ) ?: Str::headline( $className ) . ' Widget';
		$description = $this->option( 'description' ) ?: 'A custom widget.';

		// Build field definitions from --field options
		$fields = $this->parseFieldDefinitions();

		$replacements = [
			'{{ widget_id }}'          => $id,
			'{{ widget_title }}'       => $title,
			'{{ widget_description }}' => $description,
			'{{ field_definitions }}'  => $fields,
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
		$validTypes = [ 'text', 'textarea', 'checkbox', 'select', 'number', 'email', 'url' ];
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
		$className   = $nameData['class'];
		$id          = $this->option( 'id' ) ?: Str::snake( $className ) . '_widget';
		$title       = $this->option( 'title' ) ?: Str::headline( $className ) . ' Widget';
		$description = $this->option( 'description' ) ?: 'A custom widget.';
		$namespace   = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Widget Details:</info>' );
		$this->line( '  <comment>Class:</comment>       ' . $className );
		$this->line( '  <comment>Namespace:</comment>   ' . $namespace );
		$this->line( '  <comment>ID:</comment>          ' . $id );
		$this->line( '  <comment>Title:</comment>       ' . $title );
		$this->line( '  <comment>Description:</comment> ' . $description );
		$this->line( '  <comment>File:</comment>        ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Auto-discovery is enabled:</info>' );
		$this->line( '<comment>This widget will be automatically registered.</comment>' );
		$this->line( '<comment>No manual registration required.</comment>' );
	}

	/**
	 * Get an example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'RecentPosts';
	}
}
