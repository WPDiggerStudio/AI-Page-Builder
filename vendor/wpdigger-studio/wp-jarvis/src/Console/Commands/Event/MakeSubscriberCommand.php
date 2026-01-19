<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Event;

use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeSubscriberCommand - Creates a new event subscriber class.
 */
class MakeSubscriberCommand extends BaseCommand
{
    protected $signature = 'make:subscriber
                            {name : The name of the subscriber}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new event subscriber class';

    protected string $type = 'Subscriber';

    protected function getStub(): string
    {
        return 'events/subscriber.stub';
    }

    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Subscribers';

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
    protected function afterGeneration(string $qualifiedName, array $nameData, string $path): void
    {
        $className = $nameData['class'];
        $namespace = $this->getNamespace($qualifiedName);

        $this->newLine();
        $this->line('<info>Subscriber Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        $this->newLine();
        $this->line('<info>Next Steps:</info>');
        $this->line('<comment>1. Implement your event subscriptions in the subscribe() method</comment>');
        $this->newLine();
        $this->line('<comment>2. Register in config/events.php:</comment>');
        $this->newLine();
        $this->line("  'subscribe' => [");
        $this->line('      ' . $className . '::class,');
        $this->line('  ],');
        $this->newLine();
        $this->line('<comment>3. (Optional) Cache for production:</comment>');
        $this->line('  php jarvis event:cache');
    }

    protected function getExampleName(): string
    {
        return 'UserEventSubscriber';
    }
}
