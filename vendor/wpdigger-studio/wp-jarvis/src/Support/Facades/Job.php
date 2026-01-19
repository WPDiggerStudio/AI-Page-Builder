<?php

namespace WPJarvis\Framework\Support\Facades;

use WPJarvis\Framework\Support\Facades\ScopedFacade;

/**
 * Job Facade
 *
 * Provides static access to the queue/job system.
 *
 * @method static mixed push( string|object $job, mixed $data = '', string|null $queue = null )
 * @method static mixed later( \DateTimeInterface|\Date|int $delay, string|object $job, mixed $data = '', string|null $queue = null )
 * @method static mixed bulk( array $jobs, mixed $data = '', string|null $queue = null )
 * @method static mixed pop()
 * @method static int count()
 *
 * @see \WPJarvis\Framework\Contracts\Queue
 * @package WPJarvis\Framework\Support\Facades
 */
class Job extends ScopedFacade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor(): string {
		return 'queue';
	}
}
