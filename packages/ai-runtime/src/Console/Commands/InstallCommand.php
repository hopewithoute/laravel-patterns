<?php

namespace Labtime\AiRuntime\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'ai-runtime:install';

    protected $description = 'Install the AI Runtime resources and default scaffolding';

    public function handle(): int
    {
        $this->components->info('Installing AI Runtime Scaffolding...');

        $this->publishConfiguration();
        $this->publishDefinition();
        $this->publishServiceProvider();
        $this->registerServiceProvider();

        $this->components->info('AI Runtime scaffolding installed successfully.');

        return self::SUCCESS;
    }

    private function publishConfiguration(): void
    {
        $this->call('vendor:publish', ['--tag' => 'ai-runtime-config']);
    }

    private function publishDefinition(): void
    {
        $path = app_path('AI/Runtime/AssistantDefinition.php');

        if (! File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        if (! File::exists($path)) {
            File::copy(__DIR__.'/../../../stubs/AssistantDefinition.stub', $path);
            $this->components->task('Created AssistantDefinition', fn () => true);
        } else {
            $this->components->twoColumnDetail('AssistantDefinition', '<fg=yellow;options=bold>ALREADY EXISTS</>');
        }
    }

    private function publishServiceProvider(): void
    {
        $path = app_path('Providers/AiRuntimeServiceProvider.php');

        if (! File::exists($path)) {
            File::copy(__DIR__.'/../../../stubs/AiRuntimeServiceProvider.stub', $path);
            $this->components->task('Created AiRuntimeServiceProvider', fn () => true);
        } else {
            $this->components->twoColumnDetail('AiRuntimeServiceProvider', '<fg=yellow;options=bold>ALREADY EXISTS</>');
        }
    }

    private function registerServiceProvider(): void
    {
        $namespace = 'App\\Providers\\AiRuntimeServiceProvider';

        // Attempt to register in bootstrap/providers.php (Laravel 11+)
        $providersPath = base_path('bootstrap/providers.php');
        if (File::exists($providersPath)) {
            $providers = File::get($providersPath);
            if (! str_contains($providers, $namespace)) {
                $providers = str_replace(
                    'return [',
                    "return [\n    {$namespace}::class,",
                    $providers
                );
                File::put($providersPath, $providers);
                $this->components->task('Registered ServiceProvider in bootstrap/providers.php', fn () => true);
            }
            return;
        }

        // Fallback for Laravel 10 and below in config/app.php
        $appConfigPath = config_path('app.php');
        if (File::exists($appConfigPath)) {
            $appConfig = File::get($appConfigPath);
            if (! str_contains($appConfig, $namespace)) {
                $appConfig = str_replace(
                    'App\\Providers\\RouteServiceProvider::class,',
                    "App\\Providers\\RouteServiceProvider::class,\n        {$namespace}::class,",
                    $appConfig
                );
                File::put($appConfigPath, $appConfig);
                $this->components->task('Registered ServiceProvider in config/app.php', fn () => true);
            }
        }
    }
}
