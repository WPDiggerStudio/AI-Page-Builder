<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Provider;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeProviderCommand - Creates a new service provider class.
 */
class MakeProviderCommand extends BaseCommand
{
    protected $signature = 'make:provider
                            {name : The name of the provider class}
                            {--deferred : Create a deferred provider}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new service provider class';

    protected string $type = 'Provider';

    protected function getStub(): string
    {
        if ($this->option('deferred')) {
            return 'providers/provider.deferred.stub';
        }
        return 'providers/provider.stub';
    }

    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Providers';
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
        $this->line('<info>Provider Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>Deferred:</comment>   ' . ($this->option('deferred') ? 'Yes' : 'No'));
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        $this->newLine();
        $this->line('<info>Registration:</info>');
        $this->line('<comment>Add to config/app.php providers array:</comment>');
        $this->line('  \'providers\' => [');
        $this->line('      ' . $namespace . '\\' . $className . '::class,');
        $this->line('  ],');
    }

    protected function getExampleName(): string
    {
        return 'AppServiceProvider';
    }
}
