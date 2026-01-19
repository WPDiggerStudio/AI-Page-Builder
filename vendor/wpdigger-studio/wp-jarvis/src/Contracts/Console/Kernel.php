<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\Console;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Kernel Interface
 *
 * Defines the contract for the console kernel.
 */
interface Kernel {
	/**
	 * Bootstrap the console application.
	 */
	public function bootstrap(): void;

	/**
	 * Handle an incoming console command.
	 */
	public function handle( $input, $output = null ): int;

	/**
	 * Run an Artisan console command by name.
	 */
	public function call( string $command, array $parameters = [] ): int;

	/**
	 * Queue an Artisan console command by name.
	 */
	public function queue( string $command, array $parameters = [] ): void;

	/**
	 * Get all registered commands.
	 */
	public function all(): array;

	/**
	 * Terminate the application.
	 */
	public function terminate( $input, int $status ): void;

	/**
	 * Display the branding banner.
	 *
	 * @param OutputInterface $output
	 *
	 * @return void
	 */
	public function displayBanner( OutputInterface $output ): void;

	/**
	 * List all commands by category.
	 *
	 * @param OutputInterface $output
	 *
	 * @return void
	 */
	public function listCommands( OutputInterface $output ): void;
}
