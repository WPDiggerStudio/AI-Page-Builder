<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Console\Commands\Schedule;

use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeKernelCommand - Creates a Console Kernel for scheduling.
 *
 * Generates a Laravel-style Console Kernel with a schedule() method
 * for defining recurring tasks.
 *
 * @package WPJarvis\Framework\Console\Commands\Schedule
 */
class MakeKernelCommand extends BaseCommand {
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'make:kernel
                            {--force : Overwrite existing file}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a Console Kernel with Laravel-style schedule() method';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected string $type = 'Kernel';

	/**
	 * Get stub file path.
	 *
	 * @return string The stub file path.
	 */
	protected function getStub(): string {
		return 'console/kernel.stub';
	}

	/**
	 * Get the name input.
	 *
	 * The Kernel doesn't need a name argument - it's always "Kernel".
	 *
	 * @return string
	 */
	protected function getNameInput(): string {
		return 'Kernel';
	}

	/**
	 * Get default namespace.
	 *
	 * @param string $rootNamespace The root namespace.
	 * @param string $subPath The sub-path for namespacing (ignored for Kernel).
	 *
	 * @return string The full namespace.
	 */
	protected function getDefaultNamespace( string $rootNamespace, string $subPath = '' ): string {
		// Kernel goes directly in Console namespace, no subPath nesting
		return $rootNamespace . '\\Console';
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
		$namespace = $this->getNamespace( $qualifiedName );

		$this->newLine();
		$this->line( '<info>Console Kernel Created:</info>' );
		$this->line( '  <comment>Class:</comment>      Kernel' );
		$this->line( '  <comment>Namespace:</comment>  ' . $namespace );
		$this->line( '  <comment>File:</comment>       ' . $this->getRelativePath( $path ) );

		$this->newLine();
		$this->line( '<info>Laravel-Style Scheduling:</info>' );
		$this->line( '<comment>Define your scheduled tasks in the schedule() method:</comment>' );
		$this->newLine();
		$this->line( '  public function schedule(Scheduler $schedule): void' );
		$this->line( '  {' );
		$this->line( '      // Schedule a callback' );
		$this->line( '      $schedule->call(fn() => cleanup())->daily();' );
		$this->newLine();
		$this->line( '      // Schedule a command' );
		$this->line( '      $schedule->command(\'cache:clear\')->hourly();' );
		$this->newLine();
		$this->line( '      // Schedule a job' );
		$this->line( '      $schedule->job(new ProcessPayments)->everyFiveMinutes();' );
		$this->newLine();
		$this->line( '      // Add a task class' );
		$this->line( '      $schedule->add(new CleanupTask());' );
		$this->line( '  }' );

		$this->newLine();
		$this->line( '<info>Register the Kernel:</info>' );
		$this->line( '<comment>Add to your plugin\'s boot method or service provider:</comment>' );
		$this->line( '  \\' . $namespace . '\\Kernel::register();' );

		$this->newLine();
		$this->line( '<info>WordPress Cron:</info>' );
		$this->line( '<comment>The scheduler uses WP-Cron. Ensure cron is running:</comment>' );
		$this->line( '  define(\'DISABLE_WP_CRON\', true);  // In wp-config.php' );
		$this->line( '  * * * * * curl -s https://yoursite.com/wp-cron.php' );
	}

	/**
	 * Get example name for prompts.
	 *
	 * @return string The example name.
	 */
	protected function getExampleName(): string {
		return 'Kernel';
	}
}
