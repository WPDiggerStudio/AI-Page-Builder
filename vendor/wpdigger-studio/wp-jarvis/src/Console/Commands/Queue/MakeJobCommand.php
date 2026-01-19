<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Queue;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeJobCommand - Creates a new queue job class.
 */
class MakeJobCommand extends BaseCommand
{
    protected $signature = 'make:job
                            {name : The name of the job}
                            {--sync : Create a synchronous job}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new queue job class';

    protected string $type = 'Job';

    protected function getStub(): string
    {
        return 'queue/job.stub';
    }

    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Jobs';
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
        $this->line('<info>Job Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>Sync:</comment>       ' . ($this->option('sync') ? 'Yes' : 'No'));
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        $this->newLine();
        $this->line('<info>Usage:</info>');
        $this->line('<comment>Dispatch this job:</comment>');

        if ($this->option('sync')) {
            $this->line('  ' . $className . '::dispatchSync($data);');
        } else {
            $this->line('  ' . $className . '::dispatch($data);');
            $this->newLine();
            $this->line('<comment>Or dispatch with delay:</comment>');
            $this->line('  ' . $className . '::dispatch($data)->delay(now()->addMinutes(10));');
        }
    }

    protected function getExampleName(): string
    {
        return 'ProcessPayment';
    }
}
