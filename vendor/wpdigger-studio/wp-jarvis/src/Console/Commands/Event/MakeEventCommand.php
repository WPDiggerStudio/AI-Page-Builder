<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\Event;

use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeEventCommand - Creates a new event class.
 */
class MakeEventCommand extends BaseCommand {
	protected $signature = 'make:event
                            {name : The name of the event}
                            {--force : Overwrite existing file}';

	protected $description = 'Create a new event class';

	protected string $type = 'Event';

	protected function getStub(): string {
		return 'events/event.stub';
	}

	protected function getDefaultNamespace( string $rootNamespace, string $subPath = '' ): string {
		$namespace = $rootNamespace . '\\Events';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
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

		$this->newLine();
		$this->line( '<info>Event Details:</info>' );
		$this->line( '  <comment>Class:</comment>      ' . $className );
		$this->line( '  <comment>Namespace:</comment>  ' . $namespace );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Usage:</info>' );
		$this->line( '<comment>Dispatch this event using:</comment>' );
		$this->line( '  event(new ' . $className . '($data));' );

		$this->newLine();
		$this->line( '<info>Create a listener:</info>' );
		$this->line( '  php jarvis make:listener Handle' . $className . ' --event=' . $className );
	}

	protected function getExampleName(): string {
		return 'UserRegistered';
	}
}
