<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Schedule;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeTaskCommand - Creates a new scheduled task class.
 *
 * Generates WordPress-compatible scheduled tasks that integrate
 * with WP-Cron and the WP Jarvis scheduling system.
 *
 * @package WPJarvis\Framework\Console\Commands\Schedule
 */
class MakeTaskCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:task
                            {name : The name of the task class}
                            {--schedule=daily : Schedule frequency (everyMinute, everyFiveMinutes, everyTenMinutes, everyFifteenMinutes, everyThirtyMinutes, hourly, daily, weekly, monthly, yearly)}
                            {--time= : Time to run (HH:MM format, for daily/weekly/monthly tasks)}
                            {--day= : Day of week (0-6) or month (1-31) for weekly/monthly tasks}
                            {--no-overlap : Prevent task from overlapping with itself}
                            {--mutex-expiry=1440 : Mutex expiration in seconds (default: 24 hours)}
                            {--production : Only run in production environment}
                            {--background : Run task in background}
                            {--with-cleanup : Add transient cleanup example code}
                            {--with-api-sync : Add API sync example code}
                            {--with-notification : Add notification example code}
                            {--dry-run : Preview generated code without creating file}
                            {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new WordPress-compatible scheduled task class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected string $type = 'Task';

    /**
     * Valid schedule frequencies.
     *
     * @var array<string, string>
     */
    protected array $scheduleMap = [
        'everyMinute' => 'everyMinute',
        'everyFiveMinutes' => 'everyFiveMinutes',
        'everyTenMinutes' => 'everyTenMinutes',
        'everyFifteenMinutes' => 'everyFifteenMinutes',
        'everyThirtyMinutes' => 'everyThirtyMinutes',
        'hourly' => 'hourly',
        'daily' => 'daily',
        'weekly' => 'weekly',
        'monthly' => 'monthly',
        'yearly' => 'yearly',
    ];

    /**
     * Get stub file path.
     *
     * @return string The stub file path.
     */
    protected function getStub(): string
    {
        return 'scheduling/task.stub';
    }

    /**
     * Get default namespace.
     *
     * @param string $rootNamespace The root namespace.
     * @param string $subPath The sub-path for namespacing.
     *
     * @return string The full namespace.
     */
    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Tasks';
        return $subPath ? $namespace . '\\' . $subPath : $namespace;
    }

    /**
     * Perform additional replacements specific to task.
     *
     * @param string &$stub The stub content.
     * @param array<string, mixed> $nameData The parsed name data.
     *
     * @return static For chaining.
     */
    protected function performReplacements(string &$stub, array $nameData): static
    {
        parent::performReplacements($stub, $nameData);

        $className = $nameData['class'];
        $schedule = $this->option('schedule') ?: 'daily';
        $taskName = Str::kebab($className);

        // Validate and normalize schedule
        $scheduleMethod = $this->normalizeSchedule($schedule);

        $replacements = [
            '{{ task_name }}' => $taskName,
            '{{ task_description }}' => Str::headline($className) . ' Task',
            '{{ schedule_method }}' => $scheduleMethod,
        ];

        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        // Apply advanced options to constructor
        $stub = $this->applyAdvancedOptions($stub);

        return $this;
    }

    /**
     * Normalize schedule input to method name.
     *
     * @param string $schedule The schedule input.
     *
     * @return string The schedule method name.
     */
    protected function normalizeSchedule(string $schedule): string
    {
        $schedule = Str::camel($schedule);

        if (isset($this->scheduleMap[$schedule])) {
            return $this->scheduleMap[$schedule];
        }

        // Check for common aliases
        $aliases = [
            'minute' => 'everyMinute',
            '5min' => 'everyFiveMinutes',
            '10min' => 'everyTenMinutes',
            '15min' => 'everyFifteenMinutes',
            '30min' => 'everyThirtyMinutes',
            'hour' => 'hourly',
            'day' => 'daily',
            'week' => 'weekly',
            'month' => 'monthly',
            'year' => 'yearly',
        ];

        return $aliases[strtolower($schedule)] ?? 'daily';
    }

    /**
     * Apply advanced options to the stub.
     *
     * @param string $stub The stub content.
     *
     * @return string Modified stub.
     */
    protected function applyAdvancedOptions(string $stub): string
    {
        $constructorAdditions = [];

        // Handle time option for daily/weekly/monthly
        if ($time = $this->option('time')) {
            $schedule = $this->option('schedule') ?: 'daily';
            $day = $this->option('day');

            if (in_array($schedule, ['daily', 'day'])) {
                $constructorAdditions[] = "        \$this->dailyAt('{$time}');";
            } elseif (in_array($schedule, ['weekly', 'week']) && $day !== null) {
                $constructorAdditions[] = "        \$this->weeklyOn({$day}, '{$time}');";
            } elseif (in_array($schedule, ['monthly', 'month']) && $day !== null) {
                $constructorAdditions[] = "        \$this->monthlyOn({$day}, '{$time}');";
            }
        }

        // Handle no-overlap option
        if ($this->option('no-overlap')) {
            $expiry = $this->option('mutex-expiry') ?: 1440;
            $constructorAdditions[] = "        \$this->withoutOverlappingUsing({$expiry});";
        }

        // Handle production option
        if ($this->option('production')) {
            $constructorAdditions[] = "        \$this->production();";
        }

        // Handle background option
        if ($this->option('background')) {
            $constructorAdditions[] = "        \$this->runInBackground();";
        }

        // Uncomment example code based on options
        if ($this->option('with-cleanup')) {
            $stub = str_replace(
                "// \$this->cleanupTransients();",
                "\$this->cleanupTransients();",
                $stub
            );
        }

        if ($this->option('with-api-sync')) {
            $stub = str_replace(
                "// \$this->syncData();",
                "\$this->syncData();",
                $stub
            );
        }

        if ($this->option('with-notification')) {
            $stub = str_replace(
                "// \$this->sendNotifications();",
                "\$this->sendNotifications();",
                $stub
            );
        }

        // Replace the constructor comment with actual options
        if (!empty($constructorAdditions)) {
            $optionsCode = "\n" . implode("\n", $constructorAdditions) . "\n";

            // Add after the schedule method call
            $stub = preg_replace(
                '/(\$this->\w+\(\);)\s*\n\s*\/\/ Prevent overlapping/',
                "$1{$optionsCode}\n        // Prevent overlapping",
                $stub
            );
        }

        return $stub;
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
        $schedule = $this->normalizeSchedule($this->option('schedule') ?: 'daily');
        $taskName = Str::kebab($className);

        $this->newLine();
        $this->line('<info>Task Details:</info>');
        $this->line('  <comment>Class:</comment>        ' . $className);
        $this->line('  <comment>Namespace:</comment>    ' . $namespace);
        $this->line('  <comment>Hook Name:</comment>    WpJarvis_task_' . $taskName);
        $this->line('  <comment>Schedule:</comment>     ' . $schedule . '()');
        $this->line('  <comment>File:</comment>         ' . $this->getRelativePath($path));

        // Show configured options
        $options = [];
        if ($this->option('no-overlap')) {
            $options[] = 'No Overlap';
        }
        if ($this->option('production')) {
            $options[] = 'Production Only';
        }
        if ($this->option('background')) {
            $options[] = 'Background';
        }
        if (!empty($options)) {
            $this->line('  <comment>Options:</comment>      ' . implode(', ', $options));
        }

        $this->newLine();
        $this->line('<info>WordPress Cron Integration:</info>');
        $this->line('<comment>This task uses WordPress cron (WP-Cron) for scheduling.</comment>');
        $this->line('<comment>The task will be automatically registered when added to the scheduler.</comment>');

        $this->newLine();
        $this->line('<info>Registration:</info>');
        $this->line('<comment>Add to your plugin\'s schedule (App\\Console\\Kernel or ScheduleServiceProvider):</comment>');
        $this->line('  $schedule->add(new ' . $className . '());');

        $this->newLine();
        $this->line('<info>Available Schedule Methods:</info>');
        $this->table(
            ['Method', 'Description'],
            [
                ['everyMinute()', 'Run every minute'],
                ['everyFiveMinutes()', 'Run every 5 minutes'],
                ['hourly()', 'Run every hour'],
                ['hourlyAt(15)', 'Run at 15 past every hour'],
                ['daily()', 'Run daily at midnight'],
                ['dailyAt(\'13:00\')', 'Run daily at 1 PM'],
                ['weekly()', 'Run weekly on Sunday'],
                ['weeklyOn(1, \'8:00\')', 'Run Monday at 8 AM'],
                ['monthly()', 'Run monthly on the 1st'],
                ['monthlyOn(15, \'9:00\')', 'Run 15th at 9 AM'],
            ]
        );

        $this->newLine();
        $this->line('<info>Additional Modifiers:</info>');
        $this->table(
            ['Method', 'Description'],
            [
                ['withoutOverlappingUsing(600)', 'Prevent overlapping (mutex expires in 600s)'],
                ['production()', 'Only run in production'],
                ['environments(\'staging\', \'production\')', 'Run in specific environments'],
                ['runInBackground()', 'Run in background'],
                ['weekdays()', 'Only on weekdays (Mon-Fri)'],
                ['weekends()', 'Only on weekends'],
            ]
        );
    }

    /**
     * Get example name for prompts.
     *
     * @return string The example name.
     */
    protected function getExampleName(): string
    {
        return 'CleanupTask';
    }
}
