<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Database;

use Illuminate\Support\Str;
use WPJarvis\Framework\Console\Commands\BaseCommand;

/**
 * MakeModelCommand - Creates a new Eloquent model class.
 */
class MakeModelCommand extends BaseCommand
{
	protected $signature = 'make:model
                            {name : The name of the model class}
                            {--table= : Custom table name}
                            {--migration : Create a migration file}
                            {--controller : Create a controller}
                            {--resource : Create a resource controller}
                            {--force : Overwrite existing file}';

	protected $description = 'Create a new Eloquent model class';

	protected string $type = 'Model';

	protected function getStub(): string
	{
		return 'models/model.stub';
	}

	protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
	{
		$namespace = $rootNamespace . '\\Models';

		return $subPath ? $namespace . '\\' . $subPath : $namespace;
	}

	protected function performReplacements(string &$stub, array $nameData): static
	{
		parent::performReplacements($stub, $nameData);

		$className = $nameData['class'];
		$table = $this->option('table') ?: Str::snake(Str::plural($className));

		$replacements = [
			'{{ table }}' => $table,
		];

		foreach ($replacements as $search => $replace) {
			$stub = str_replace($search, $replace, $stub);
		}

		return $this;
	}

	protected function afterGeneration(string $qualifiedName, array $nameData, string $path): void
	{
		$className = $nameData['class'];
		$namespace = $this->getNamespace($qualifiedName);
		$table = $this->option('table') ?: Str::snake(Str::plural($className));

		$this->newLine();
		$this->line('<info>Model Details:</info>');
		$this->line('  <comment>Class:</comment>      ' . $className);
		$this->line('  <comment>Namespace:</comment>  ' . $namespace);
		$this->line('  <comment>Table:</comment>      ' . $table);
		$this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

		if ($this->option('migration')) {
			$this->newLine();
			$this->call('make:migration', [
				'name' => "create_{$table}_table",
				'--create' => $table,
			]);
		}

		if ($this->option('controller') || $this->option('resource')) {
			$this->newLine();
			$this->call('make:controller', [
				'name' => $nameData['class'] . 'Controller',
				'--model' => $nameData['class'],
				'--resource' => $this->option('resource'),
			]);
		}

		$this->newLine();
		$this->line('<info>Usage:</info>');
		$this->line('<comment>Query examples:</comment>');
		$this->line('  ' . $className . '::all();');
		$this->line('  ' . $className . '::find($id);');
		$this->line('  ' . $className . '::where(\'status\', \'active\')->get();');
	}

	protected function getExampleName(): string
	{
		return 'User';
	}
}
