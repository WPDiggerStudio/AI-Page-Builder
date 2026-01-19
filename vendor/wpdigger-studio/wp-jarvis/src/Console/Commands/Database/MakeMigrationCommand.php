<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeMigrationCommand - Creates a new database migration file.
 *
 * Generates a migration file with enhanced options
 * including table specification and dry-run support.
 *
 * @package WPJarvis\Framework\Console\Commands\Database
 */
class MakeMigrationCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration
                           {name : The name of migration}
                           {--create= : The table to create}
                           {--table= : The table to modify}
                           {--dry-run : Preview generated code without creating file}
                           {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new database migration file with table options';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Migration';

    /**
     * Get stub file path.
     *
     * @return string The stub file path.
     */
    protected function getStub(): string
    {
        if ($this->option('create')) {
            return 'database/migration.create.stub';
        }

        return 'database/migration.stub';
    }

    /**
     * Get default namespace.
     *
     * Migrations use root Database\Migrations namespace (Laravel-style).
     *
     * @param string $rootNamespace The root namespace.
     * @param string $subPath The sub-path for namespacing.
     * @return string The full namespace.
     */
    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        // Use Database\Migrations namespace (Laravel-style, not nested under App)
        return 'Database\\Migrations';
    }

    /**
     * Get the file path for the migration.
     *
     * Migrations go to root database/migrations folder (Laravel-style).
     *
     * @param string $name The class name.
     * @return string The file path.
     */
    protected function getPath(string $name): string
    {
        // Extract just the class name (not the full namespace path)
        $parts = explode('\\', $name);
        $className = array_pop($parts);

        // Migrations go to root database/migrations folder
        return $this->app->basePath('database/migrations/' . $className . '.php');
    }

    /**
     * Perform additional replacements specific to migration.
     *
     * @param string &$stub The stub content.
     * @param array<string, mixed> $nameData The parsed name data.
     * @return static For chaining.
     */
    protected function performReplacements(string &$stub, array $nameData): static
    {
        parent::performReplacements($stub, $nameData);

        $table = $this->option('create') ?: $this->option('table') ?: $this->guessTableName($nameData['original']);

        $replacements = [
            '{{ table }}' => $table,
            '{{ migration_type }}' => $this->option('create') ? 'create' : 'modify',
            '{{ is_create }}' => $this->option('create') ? 'true' : 'false',
            '{{ is_modify }}' => $this->option('table') ? 'true' : 'false',
            '{{ is_alter }}' => (!$this->option('create') && !$this->option('table')) ? 'true' : 'false',
            '{{ alter_column }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ alter_type }}' => $this->option('table') ? 'add' : 'null',
            '{{ alter_column_name }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ alter_column_type }}' => $this->option('table') ? 'add' : 'null',
            '{{ alter_column_default }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_column }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_column_from }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_column_to }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ drop_column }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ drop_column_name }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table_from }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table_to }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table_columns }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table_columns_from }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table_columns_to }}' => $this->option('table') ? $this->option('table') : 'null',
            '{{ rename_table_columns_to_name }}' => $this->option('table') ? $this->option('table') : 'null',
        ];

        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        return $this;
    }

    /**
     * Guess table name from migration name.
     *
     * @param string $name The migration name.
     * @return string The guessed table name.
     */
    protected function guessTableName(string $name): string
    {
        if (preg_match('/create_(\w+)_table/', Str::snake($name), $matches)) {
            return $matches[1];
        }

        if (preg_match('/alter_(\w+)_table/', Str::snake($name), $matches)) {
            return $matches[1];
        }

        if (preg_match('/(\w+)_table/', Str::snake($name), $matches)) {
            return $matches[1];
        }

        if (preg_match('/drop_(\w+)_from_(\w+)_table/', Str::snake($name), $matches)) {
            return $matches[1];
        }

        if (preg_match('/rename_(\w+)_table/', Str::snake($name), $matches)) {
            return $matches[1];
        }

        if (preg_match('/rename_(\w+)_table/', Str::snake($name), $matches)) {
            return $matches[1];
        }

        return Str::snake(Str::plural($name));
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
        $this->newLine();
        $this->info('Migration file created successfully.');
        $this->newLine();
        $this->info('Run migrations:');
        $this->line('  php wp-jarvis migrate');
        $this->newLine();
        $this->info('To rollback:');
        $this->line('  php wp-jarvis migrate:rollback --step=1');
        $this->newLine();
        $this->info('To view status:');
        $this->line('  php wp-jarvis migrate:status');
    }

    /**
     * Get example name for prompts.
     *
     * @return string The example name.
     */
    protected function getExampleName(): string
    {
        return 'create_users_table';
    }
}
