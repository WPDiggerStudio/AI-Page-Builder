<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Contracts\View;

/**
 * Factory Interface
 *
 * Defines the contract for view factories.
 */
interface Factory {
	/**
	 * Determine if a given view exists.
	 */
	public function exists( string $view ): bool;

	/**
	 * Get the evaluated view contents for the given path.
	 *
	 * @param string $path
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $mergeData
	 *
	 * @return View
	 */
	public function file( string $path, array $data = [], array $mergeData = [] ): View;

	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param string $view
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $mergeData
	 *
	 * @return View
	 */
	public function make( string $view, array $data = [], array $mergeData = [] ): View;

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param array<string, mixed>|string $key
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function share( array|string $key, mixed $value = null ): mixed;

	/**
	 * Register a view composer event.
	 *
	 * @param array|string $views
	 * @param \Closure|string $callback
	 */
	public function composer( array|string $views, \Closure|string $callback ): void;

	/**
	 * Add a new namespace to the loader.
	 */
	public function addNamespace( string $namespace, string|array $hints ): void;
}
