<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeSeederCommand - Creates a new database seeder file.
 *
 * Generates seeder files in the Laravel-style database/seeders folder.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class MakeSeederCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:seeder
                           {name : The name of the seeder}
                           {--dry-run : Preview generated code without creating file}
                           {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new database seeder class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Seeder';

    /**
     * Get stub file path.
     *
     * @return string The stub file path.
     */
    protected function getStub(): string
    {
        return 'database/seeder.stub';
    }

    /**
     * Get default namespace.
     *
     * Seeders use root Database\Seeders namespace (Laravel-style).
     *
     * @param string $rootNamespace The root namespace.
     * @param string $subPath The sub-path for namespacing.
     * @return string The full namespace.
     */
    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        // Use Database\Seeders namespace (Laravel-style, not nested under App)
        return 'Database\\Seeders';
    }

    /**
     * Get the file path for the seeder.
     *
     * Seeders go to root database/seeders folder (Laravel-style).
     *
     * @param string $name The class name.
     * @return string The file path.
     */
    protected function getPath(string $name): string
    {
        // Extract just the class name (not the full namespace path)
        $parts = explode('\\', $name);
        $className = array_pop($parts);

        // Seeders go to root database/seeders folder
        return $this->app->basePath('database/seeders/' . $className . '.php');
    }

    /**
     * Tasks after generation.
     *
     * @param string $qualifiedName The fully qualified class name.
     * @param array<string, mixed> $nameData The parsed name data.
     * @param string $path The file path.
     * @return void
     */
    protected function afterGeneration(string $qualifiedName, array $nameData, string $path): void
    {
        $className = $nameData['class'];
        $namespace = $this->getNamespace($qualifiedName);

        $this->newLine();
        $this->line('<info>Seeder Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        $this->newLine();
        $this->line('<info>Run Seeders:</info>');
        $this->line('<comment>Run this seeder:</comment>');
        $this->line('  php wp-jarvis db:seed --class=' . $className);

        $this->newLine();
        $this->line('<comment>Run all seeders:</comment>');
        $this->line('  php wp-jarvis db:seed');

        $this->newLine();
        $this->line('<info>Register in DatabaseSeeder:</info>');
        $this->line('  $this->call([');
        $this->line('      ' . $className . '::class,');
        $this->line('  ]);');
    }

    /**
     * Get example name for prompts.
     *
     * @return string The example name.
     */
    protected function getExampleName(): string
    {
        return 'UserSeeder';
    }
}
