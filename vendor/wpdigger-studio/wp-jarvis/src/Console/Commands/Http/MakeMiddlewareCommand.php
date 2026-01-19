<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Http;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeMiddlewareCommand - Creates a new HTTP middleware.
 */
class MakeMiddlewareCommand extends BaseCommand
{
    protected $signature = 'make:middleware
                            {name : The name of the middleware}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new HTTP middleware class';

    protected string $type = 'Middleware';

    protected function getStub(): string
    {
        return 'http/middleware.stub';
    }

    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Http\\Middleware';
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
        $this->line('<info>Middleware Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        $this->newLine();
        $this->line('<info>Registration:</info>');
        $this->line('<comment>Register in app/Http/Kernel.php:</comment>');
        $this->line('  protected $routeMiddleware = [');
        $this->line('      \'' . $nameData['slug'] . '\' => ' . $className . '::class,');
        $this->line('  ];');

        $this->newLine();
        $this->line('<info>Usage:</info>');
        $this->line('  Route::middleware(\'' . $nameData['slug'] . '\')->group(...);');
    }

    protected function getExampleName(): string
    {
        return 'Authenticate';
    }
}
