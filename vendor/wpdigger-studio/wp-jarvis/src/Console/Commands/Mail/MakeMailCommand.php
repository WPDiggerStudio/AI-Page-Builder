<?php

declare(strict_types=1);

namespace WPJarvis\Framework\Console\Commands\Mail;

use WPJarvis\Framework\Console\Commands\BaseCommand;
use Illuminate\Support\Str;

/**
 * MakeMailCommand - Creates a new mailable class.
 */
class MakeMailCommand extends BaseCommand
{
    protected $signature = 'make:mail
                            {name : The name of the mailable class}
                            {--template= : Create a template file}
                            {--force : Overwrite existing file}';

    protected $description = 'Create a new mailable class';

    protected string $type = 'Mail';

    protected function getStub(): string
    {
        return 'mail/mailable.stub';
    }

    protected function getDefaultNamespace(string $rootNamespace, string $subPath = ''): string
    {
        $namespace = $rootNamespace . '\\Mail';
        return $subPath ? $namespace . '\\' . $subPath : $namespace;
    }

    protected function performReplacements(string &$stub, array $nameData): static
    {
        parent::performReplacements($stub, $nameData);

        $className = $nameData['class'];
        $templateName = $this->option('template') ?: 'mail/' . Str::kebab($className);

        $replacements = [
            '{{ template_name }}' => $templateName,
            '{{ subject_default }}' => Str::headline($className),
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
        $templateName = $this->option('template') ? 'mail/' . Str::kebab($className) : 'None';

        $this->newLine();
        $this->line('<info>Mail Details:</info>');
        $this->line('  <comment>Class:</comment>      ' . $className);
        $this->line('  <comment>Namespace:</comment>  ' . $namespace);
        $this->line('  <comment>Template:</comment>   ' . $templateName);
        $this->line('  <comment>File:</comment>       ' . $this->getRelativePath($path));

        if ($this->option('template')) {
            $templatePath = $this->app->basePath('resources/views/mail/' . Str::kebab($nameData['class']) . '.php');

            if (!$this->files->exists($templatePath)) {
                $this->makeDirectory($templatePath);
                $this->files->put($templatePath, $this->getTemplateContent($nameData));

                $this->newLine();
                $this->line('<info>Template created:</info>');
                $this->line('  <comment>Path:</comment>      ' . $this->getRelativePath($templatePath));
            }
        }

        $this->newLine();
        $this->line('<info>Usage:</info>');
        $this->line('<comment>Send this mail:</comment>');
        $this->line('  Mail::to($user)->send(new ' . $className . '($data));');
    }

    protected function getTemplateContent(array $nameData): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(\$subject ?? ''); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4a90d9; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 10px 20px; background: #4a90d9; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
        </div>
        <div class="content">
            <h2><?php echo esc_html(\$title ?? '{$nameData['title']}'); ?></h2>
            <p><?php echo wp_kses_post(\$content ?? 'Your email content here.'); ?></p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. All rights reserved.</p>
            <p><?php echo esc_html(home_url()); ?></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function getExampleName(): string
    {
        return 'WelcomeEmail';
    }
}
