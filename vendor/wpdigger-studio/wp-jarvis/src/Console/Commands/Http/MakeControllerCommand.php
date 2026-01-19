<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Http;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeControllerCommand - Creates a new controller class.
 */
class MakeControllerCommand extends BaseCommand
{
    protected $signature = 'make:controller
                            {name : The name of the controller class}
                            {--resource : Create a resource controller}
                            {--api : Create an API controller}
                            {--model= : Generate a resource controller for a model}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new controller class';

    protected string $type = 'Controller';

    protected function getStub(): string
    {
        if ($this->option('api')) {
            return 'http/controller.api.stub';
        }
        if ($this->option('resource')) {
            return 'http/controller.resource.stub';
        }
        return 'http/controller.stub';
    }

    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Http\\Controllers';
        return $subPath ? $namespace . '\\' . $subPath : $namespace;
    }

    protected function performReplacements(string &$stub, array $nameData): static
    {
        parent::performReplacements($stub, $nameData);

        if ($model = $this->option('model')) {
            $modelClass = Str::studly($model);
            $modelVariable = Str::camel($model);

            $replacements = [
                '{{ model }}' => $modelClass,
                '{{ modelVariable }}' => $modelVariable,
                '{{ modelPlural }}' => Str::plural($modelVariable),
            ];

            foreach ($replacements as $search => $replace) {
                $stub = str_replace($search, $replace, $stub);
            }
        }

        return $this;
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

        $type = 'Basic';
        if ($this->option('api')) {
            $type = 'API';
        } elseif ($this->option('resource')) {
            $type = 'Resource';
        }

        $this->newLine();
        $this->line('<info>Controller Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>Type:</comment>       ' . $type);
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        $this->newLine();
        $this->line('<info>Route Registration:</info>');
        $this->line('<comment>Add routes in routes/api.php or routes/web.php:</comment>');

        $routeName = strtolower(str_replace('Controller', '', $className));
        if ($this->option('resource') || $this->option('api')) {
            $this->line('  Route::apiResource(\'' . $routeName . '\', ' . $className . '::class);');
        } else {
            $this->line('  Route::get(\'/' . $routeName . '\', [' . $className . '::class, \'index\']);');
        }
    }

    protected function getExampleName(): string
    {
        return 'UserController';
    }
}
