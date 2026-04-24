<?php

namespace App\Providers;

use App\AI\Gateway\CliProxyApiGateway;
use App\AI\Providers\CliProxyApiProvider;
use App\AI\Runtime\Artifacts\Attributes\RuntimeArtifactType;
use App\AI\Runtime\Artifacts\Builders\ArtifactBuilder;
use App\AI\Runtime\Artifacts\Registry\ArtifactCatalogBuilder;
use App\AI\Runtime\Artifacts\Registry\ArtifactManifestExporter;
use App\AI\Runtime\Artifacts\Registry\ArtifactRegistry;
use App\AI\Runtime\Artifacts\Registry\ArtifactSchemaValidator;
use App\AI\Runtime\Artifacts\Registry\DiscoveredArtifactRegistryBuilder;
use App\AI\Runtime\Artifacts\Registry\InMemoryArtifactRegistry;
use App\AI\Runtime\Artifacts\RuntimeArtifactModeCatalog;
use App\AI\Runtime\Artifacts\WorkspaceArtifactResolver;
use App\AI\Runtime\Contracts\AiStreamOutput;
use App\AI\Runtime\Contracts\AvailableToolResolver;
use App\AI\Runtime\Contracts\KnowledgeSource;
use App\AI\Runtime\Contracts\LexicalSearchIndex;
use App\AI\Runtime\Contracts\PolicyEngine;
use App\AI\Runtime\Contracts\PostRunHook;
use App\AI\Runtime\Contracts\PreflightResolver;
use App\AI\Runtime\Contracts\PromptMiddleware;
use App\AI\Runtime\Contracts\RetrievalPlanner;
use App\AI\Runtime\Contracts\TelemetryStore;
use App\AI\Runtime\Contracts\ToolAccessResolver;
use App\AI\Runtime\Contracts\ToolExecutionPolicy;
use App\AI\Runtime\Hooks\Attributes\RuntimeHook;
use App\AI\Runtime\Hooks\CompositePostRunHook;
use App\AI\Runtime\Middleware\Attributes\RuntimeMiddleware;
use App\AI\Runtime\Policy\WorkspaceScopePolicyEngine;
use App\AI\Runtime\Preflight\WorkspaceAssistantPreflightResolver;
use App\AI\Runtime\Retrieval\NullLexicalSearchIndex;
use App\AI\Runtime\Retrieval\PgsqlTsVectorLexicalSearchIndex;
use App\AI\Runtime\Retrieval\SqliteFtsLexicalSearchIndex;
use App\AI\Runtime\Retrieval\WorkspaceBasicKnowledgeSource;
use App\AI\Runtime\Retrieval\WorkspaceBasicRetrievalPlanner;
use App\AI\Runtime\Retrieval\WorkspaceDatabaseKnowledgeSource;
use App\AI\Runtime\Retrieval\WorkspaceLexicalKnowledgeSource;
use App\AI\Runtime\Streaming\AiStreamEnvelopeFactory;
use App\AI\Runtime\Streaming\AiStreamTransportRegistry;
use App\AI\Runtime\Support\AttributeClassDiscovery;
use App\AI\Runtime\Telemetry\DatabaseTelemetryStore;
use App\AI\Runtime\Telemetry\NullTelemetryStore;
use App\AI\Runtime\Tools\Registry\DiscoveredToolRegistryBuilder;
use App\AI\Runtime\Tools\Registry\InMemoryToolRegistry;
use App\AI\Runtime\Tools\Registry\RegistryAvailableToolResolver;
use App\AI\Runtime\Tools\Registry\ToolManifestExporter;
use App\AI\Runtime\Tools\Registry\ToolPromptCatalogBuilder;
use App\AI\Runtime\Tools\Registry\ToolRegistry;
use App\AI\Runtime\Tools\WorkspaceToolAccessResolver;
use App\AI\Runtime\Tools\WorkspaceToolExecutionPolicy;
use App\AI\Runtime\WorkspaceAssistantRuntime;
use App\Console\Commands\AiSyncRuntimeManifestsCommand;
use App\Models\AiRuntimeTelemetryRun;
use App\Models\AiRuntimeTelemetrySource;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;

class AiRuntimeServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PolicyEngine::class => WorkspaceScopePolicyEngine::class,
        PreflightResolver::class => WorkspaceAssistantPreflightResolver::class,
        RetrievalPlanner::class => WorkspaceBasicRetrievalPlanner::class,
        ToolAccessResolver::class => WorkspaceToolAccessResolver::class,
        ToolExecutionPolicy::class => WorkspaceToolExecutionPolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(AttributeClassDiscovery::class);

        $this->discoverAndRegisterMiddlewares();
        $this->discoverAndRegisterHooks();
        $this->discoverAndRegisterArtifactBuilders();

        $this->app->singleton(PostRunHook::class, function ($app): PostRunHook {
            return new CompositePostRunHook($app->tagged('ai.runtime.post_run_hooks'));
        });

        $this->app->singleton(DiscoveredToolRegistryBuilder::class);
        $this->app->singleton(ToolRegistry::class, function ($app): ToolRegistry {
            return new InMemoryToolRegistry(
                $app->make(DiscoveredToolRegistryBuilder::class)->build(),
            );
        });

        $this->app->singleton(ToolPromptCatalogBuilder::class);
        $this->app->singleton(ToolManifestExporter::class);

        $this->app->singleton(RuntimeArtifactModeCatalog::class, function (): RuntimeArtifactModeCatalog {
            return new RuntimeArtifactModeCatalog(
                modes: config('ai.runtime.artifact_modes', []),
            );
        });

        $this->app->singleton(AvailableToolResolver::class, function ($app): AvailableToolResolver {
            return new RegistryAvailableToolResolver(
                container: $app,
                toolRegistry: $app->make(ToolRegistry::class),
                toolPromptCatalogBuilder: $app->make(ToolPromptCatalogBuilder::class),
            );
        });

        $this->app->singleton(DiscoveredArtifactRegistryBuilder::class);
        $this->app->singleton(ArtifactRegistry::class, function ($app): ArtifactRegistry {
            return new InMemoryArtifactRegistry(
                $app->make(DiscoveredArtifactRegistryBuilder::class)->build(),
            );
        });

        $this->app->singleton(ArtifactSchemaValidator::class);
        $this->app->singleton(ArtifactCatalogBuilder::class);
        $this->app->singleton(ArtifactManifestExporter::class);

        $this->app->singleton(WorkspaceArtifactResolver::class, function ($app): WorkspaceArtifactResolver {
            return new WorkspaceArtifactResolver(
                builders: array_values(array_filter(
                    iterator_to_array($app->tagged('ai.runtime.artifact_builders')),
                    fn (mixed $builder): bool => $builder instanceof ArtifactBuilder,
                )),
                artifactRegistry: $app->make(ArtifactRegistry::class),
                artifactSchemaValidator: $app->make(ArtifactSchemaValidator::class),
            );
        });

        $this->app->scoped(WorkspaceAssistantRuntime::class, function ($app): WorkspaceAssistantRuntime {
            return new WorkspaceAssistantRuntime(
                preflightResolver: $app->make(PreflightResolver::class),
                policyEngine: $app->make(PolicyEngine::class),
                retrievalPlanner: $app->make(RetrievalPlanner::class),
                knowledgeSource: $app->make(KnowledgeSource::class),
                availableToolResolver: $app->make(AvailableToolResolver::class),
                toolAccessResolver: $app->make(ToolAccessResolver::class),
                toolExecutionPolicy: $app->make(ToolExecutionPolicy::class),
                toolRegistry: $app->make(ToolRegistry::class),
                promptMiddlewares: array_values(array_filter(
                    iterator_to_array($app->tagged('ai.runtime.prompt_middlewares')),
                    fn (mixed $middleware): bool => $middleware instanceof PromptMiddleware,
                )),
            );
        });

        $this->app->singleton(TelemetryStore::class, function ($app): TelemetryStore {
            return match (config('ai.runtime.telemetry.driver', 'database')) {
                'database' => new DatabaseTelemetryStore(
                    $app->make(AiRuntimeTelemetryRun::class),
                    $app->make(AiRuntimeTelemetrySource::class),
                ),
                default => new NullTelemetryStore,
            };
        });

        $this->app->singleton(AiStreamEnvelopeFactory::class);

        $this->app->scoped(AiStreamTransportRegistry::class, function ($app): AiStreamTransportRegistry {
            return new AiStreamTransportRegistry(
                envelopeFactory: $app->make(AiStreamEnvelopeFactory::class),
                config: config('ai.runtime.stream', []),
                debugMode: (bool) config('app.debug', false),
            );
        });

        $this->app->scoped(AiStreamOutput::class, function (): AiStreamOutput {
            return $this->app->make(AiStreamTransportRegistry::class)->resolve();
        });

        $this->app->singleton(LexicalSearchIndex::class, function (): LexicalSearchIndex {
            return match (config('ai.runtime.lexical.driver', 'null')) {
                'pgsql_tsvector' => new PgsqlTsVectorLexicalSearchIndex(
                    language: (string) config('ai.runtime.lexical.language', 'simple'),
                ),
                'sqlite_fts5' => new SqliteFtsLexicalSearchIndex,
                default => new NullLexicalSearchIndex,
            };
        });

        $this->app->singleton(KnowledgeSource::class, function ($app): KnowledgeSource {
            return new WorkspaceBasicKnowledgeSource([
                $app->make(WorkspaceDatabaseKnowledgeSource::class),
                $app->make(WorkspaceLexicalKnowledgeSource::class),
            ]);
        });

        $this->app->afterResolving(AiManager::class, function (AiManager $manager): void {
            $manager->extend('cliproxyapi', function ($app, array $config): CliProxyApiProvider {
                return new CliProxyApiProvider(
                    new CliProxyApiGateway($app['events']),
                    $config,
                    $app->make(Dispatcher::class),
                );
            });
        });
    }

    private function discoverAndRegisterMiddlewares(): void
    {
        $discovery = $this->app->make(AttributeClassDiscovery::class);

        $middlewares = $discovery->discover(
            directory: app_path('AI/Runtime/Middleware'),
            namespace: 'App\\AI\\Runtime\\Middleware',
            attributeClass: RuntimeMiddleware::class,
            interface: PromptMiddleware::class,
            priorityAttribute: RuntimeMiddleware::class,
        );

        foreach ($middlewares as $middleware) {
            $this->app->bind($middleware);
        }

        $this->app->tag($middlewares, 'ai.runtime.prompt_middlewares');
    }

    private function discoverAndRegisterHooks(): void
    {
        $discovery = $this->app->make(AttributeClassDiscovery::class);

        $hooks = $discovery->discover(
            directory: app_path('AI/Runtime/Hooks'),
            namespace: 'App\\AI\\Runtime\\Hooks',
            attributeClass: RuntimeHook::class,
            interface: PostRunHook::class,
            priorityAttribute: RuntimeHook::class,
        );

        foreach ($hooks as $hook) {
            $this->app->bind($hook);
        }

        $this->app->tag($hooks, 'ai.runtime.post_run_hooks');
    }

    private function discoverAndRegisterArtifactBuilders(): void
    {
        $discovery = $this->app->make(AttributeClassDiscovery::class);

        $builders = $discovery->discover(
            directory: app_path('AI/Runtime/Artifacts/Builders'),
            namespace: 'App\\AI\\Runtime\\Artifacts\\Builders',
            attributeClass: RuntimeArtifactType::class,
            interface: ArtifactBuilder::class,
        );

        foreach ($builders as $builder) {
            $this->app->bind($builder);
        }

        $this->app->tag($builders, 'ai.runtime.artifact_builders');
    }

    public function boot(): void
    {
        $this->commands([
            AiSyncRuntimeManifestsCommand::class,
        ]);
    }
}
