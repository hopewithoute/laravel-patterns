<?php

namespace Labtime\AiRuntime;

use Illuminate\Support\ServiceProvider;
use Labtime\AiRuntime\Artifacts\Registry\ExplicitArtifactRegistryBuilder;
use Labtime\AiRuntime\Execution\RuntimeKernel;
use Labtime\AiRuntime\Foundation\Contracts\ToolExecutionPolicy;
use Labtime\AiRuntime\Tools\PassThroughToolExecutionPolicy;
use Labtime\AiRuntime\Tools\Registry\ExplicitToolRegistryBuilder;

class AiRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-runtime.php', 'ai-runtime');

        $this->app->singleton(ExplicitToolRegistryBuilder::class);
        $this->app->singleton(ExplicitArtifactRegistryBuilder::class);
        $this->app->singleton(RuntimeCompositionFactory::class);
        $this->app->bindIf(ToolExecutionPolicy::class, PassThroughToolExecutionPolicy::class);
        $this->app->scoped(Tools\Registry\ToolPromptCatalogBuilder::class);
        $this->app->scoped(Tools\Registry\ToolManifestExporter::class);
        $this->app->scoped(Artifacts\Registry\ArtifactCatalogBuilder::class);
        $this->app->scoped(Artifacts\Registry\ArtifactManifestExporter::class);
        $this->app->scoped(Artifacts\Registry\ArtifactSchemaValidator::class);
        $this->app->singleton(Streaming\AiStreamEnvelopeFactory::class);

        $this->app->scoped(RuntimeKernel::class, function ($app): RuntimeKernel {
            return new RuntimeKernel(container: $app);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../config/ai-runtime.php' => config_path('ai-runtime.php'),
            ], 'ai-runtime-config');

            $this->commands([
                Console\Commands\SyncRuntimeManifestsCommand::class,
                Console\Commands\InstallCommand::class,
            ]);
        }
    }
}
