<?php

declare(strict_types=1);

namespace BraCalculator\App\Events;

use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * SendTestWelcomeEmail Event
 *
 * Represents an event that occurred in the application.
 *
 * @package BraCalculator\App\Events
 */
class SendTestWelcomeEmail
{
    /**
     * Event data.
     *
     * @var array<string, mixed>
     */
    public array $data;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $data Event data.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get the event name for WordPress hooks.
     *
     * @return string
     */
    public static function name(): string
    {
        return strtolower(str_replace('\\', '_', static::class));
    }

    /**
     * Dispatch the event.
     *
     * @param array<string, mixed> $data Event data.
     * @return static
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function dispatch(array $data = []): static
    {
        $event = new static($data);

        // Fire WordPress action
        Hooks::doAction(static::name(), $event);

        // Fire through event dispatcher if available
        if (function_exists('app') && wpj_app()->bound('events')) {
            wpj_app('events')->dispatch($event);
        }

        return $event;
    }

    /**
     * Listen to this event.
     *
     * @param callable $callback
     * @return void
     */
    public static function listen(callable $callback): void
    {
        Hooks::action(static::name(), $callback, 10, 1);
    }

    /**
     * Get a data value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a data value.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get all event data.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
