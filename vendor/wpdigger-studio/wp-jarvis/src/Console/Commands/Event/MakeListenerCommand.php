<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\Event;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeListenerCommand - Creates a new event listener class.
 */
class MakeListenerCommand extends BaseCommand {
	protected $signature = 'make:listener
                            {name : The name of the listener}
                            {--event= : The event class to listen for}
                            {--queued : Create a queued listener}
                            {--force : Overwrite existing file}';

	protected $description = 'Create a new event listener class';

	protected string $type = 'Listener';

	/**
	 * Event class name (if provided).
	 */
	private ?string $eventClass = null;

	/**
	 * Event fully qualified name.
	 */
	private ?string $eventQualifiedName = null;

	protected function getStub(): string {
		if ( $this->option( 'queued' ) ) {
			return 'events/listener.queued.stub';
		}

		return 'events/listener.stub';
	}

	protected function getDefaultNamespace( string $rootNamespace, string $subPath = '' ): string {
		$namespace = $rootNamespace . '\\Listeners';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle(): int {
		// Check if an event needs to be created
		if ( $this->option( 'event' ) ) {
			$this->ensureEventExists();
		}

		// Call parent handle to create the listener
		try {
			$result = parent::handle();
		} catch ( \Throwable $e ) {
			$this->error( 'Failed to create listener: ' . $e->getMessage() );

			return self::FAILURE;
		}

		// Auto-register in config if successful
		if ( $result === self::SUCCESS && $this->eventClass ) {
			$this->registerInConfig();
		}

		return $result;
	}

	/**
	 * Ensure the event class exists, create if it doesn't.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	private function ensureEventExists(): void {
		$eventName = $this->option( 'event' );

		// Parse the event name (could be just class name or full namespace)
		if ( str_contains( $eventName, '\\' ) ) {
			// Full namespace provided
			$this->eventQualifiedName = $eventName;
			$this->eventClass         = class_basename( $eventName );
		} else {
			// Just class name provided
			$this->eventClass = Str::studly( $eventName );

			// Build qualified name
			$rootNamespace            = $this->rootNamespace();
			$this->eventQualifiedName = $rootNamespace . '\\Events\\' . $this->eventClass;
		}

		// Check if an event file exists
		$eventPath = $this->getEventPath();

		if ( ! file_exists( $eventPath ) ) {
			$this->info( "Event {$this->eventClass} does not exist. Creating it..." );
			$this->newLine();

			// Create the event
			$this->call( 'make:event', [
				'name' => $this->eventClass,
			] );

			$this->newLine();
			$this->info( "Continuing with listener creation..." );
			$this->newLine();
		}
	}

	/**
	 * Get the event file path.
	 *
	 * @return string
	 */
	private function getEventPath(): string {
		$rootNamespace = $this->rootNamespace();
		$appPath       = $this->laravel->basePath( 'app' );

		// Convert namespace to path
		$relativePath = str_replace( $rootNamespace, '', $this->eventQualifiedName );
		$relativePath = str_replace( '\\', DIRECTORY_SEPARATOR, $relativePath );

		return $appPath . $relativePath . '.php';
	}

	protected function performReplacements( string &$stub, array $nameData ): static {
		parent::performReplacements( $stub, $nameData );

		if ( $this->eventClass ) {
			// Replace event placeholders
			$stub = str_replace(
				[ '{{ event }}', '{{ eventVariable }}', '{{ eventNamespace }}' ],
				[ $this->eventClass, Str::camel( $this->eventClass ), $this->eventQualifiedName ],
				$stub
			);
		} else {
			$stub = str_replace(
				[ '{{ event }}', '{{ eventVariable }}', '{{ eventNamespace }}' ],
				[ 'Event', 'event', '' ],
				$stub
			);
		}

		return $this;
	}

	/**
	 * Register the listener in config/events.php
	 *
	 * @return void
	 */
	private function registerInConfig(): void {
		$configPath = $this->laravel->configPath( 'events.php' );

		if ( ! file_exists( $configPath ) ) {
			$this->warn( 'config/events.php not found. Skipping auto-registration.' );

			return;
		}

		$config = require $configPath;

		// Get listener class name
		$listenerClass     = $this->getNameInput();
		$rootNamespace     = $this->rootNamespace();
		$listenerQualified = $rootNamespace . '\\Listeners\\' . $listenerClass;

		// Add to listen array
		if ( ! isset( $config['listen'][ $this->eventQualifiedName ] ) ) {
			$config['listen'][ $this->eventQualifiedName ] = [];
		}

		if ( ! in_array( $listenerQualified, $config['listen'][ $this->eventQualifiedName ], true ) ) {
			$config['listen'][ $this->eventQualifiedName ][] = $listenerQualified;

			// Write back to file
			$this->writeConfigFile( $configPath, $config );

			$this->newLine();
			$this->info( '✓ Auto-registered in config/events.php' );
		}
	}

	/**
	 * Write config array to file.
	 *
	 * @param string $path Config file path.
	 * @param array $config Config array.
	 *
	 * @return void
	 */
	private function writeConfigFile( string $path, array $config ): void {
		$export = var_export( $config, true );

		// Clean up the export for better formatting
		$export = preg_replace( '/\s+$/m', '', $export ); // Remove trailing spaces
		$export = preg_replace( '/^(\s+)/m', "\t$1", $export ); // Convert spaces to tabs

		$content = <<<PHP
<?php

return {$export};

PHP;

		file_put_contents( $path, $content );
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
		$namespace = $this->getNamespace( $qualifiedName );
		$event     = $this->eventClass ?: 'Event';

		$this->newLine();
		$this->line( '<info>Listener Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>  ' . $namespace );
		$this->line( '  <comment>Event:</comment>      ' . $event );
		$this->line( '  <comment>Queued:</comment>     ' . ( $this->option( 'queued' ) ? 'Yes' : 'No' ) );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		if ( ! $this->eventClass ) {
			// Show manual registration instructions if no event specified
			$this->newLine();
			$this->line( '<info>Next Steps:</info>' );
			$this->line( '<comment>1. Register in config/events.php:</comment>' );
			$this->newLine();
			$this->line( "  'listen' => [" );
			$this->line( '      YourEvent::class => [' );
			$this->line( '          ' . $className . '::class,' );
			$this->line( '      ],' );
			$this->line( '  ],' );
			$this->newLine();
			$this->line( '<comment>2. (Optional) Cache for production:</comment>' );
			$this->line( '  php jarvis event:cache' );
		}
	}

	protected function getExampleName(): string {
		return 'SendWelcomeEmail';
	}
}
