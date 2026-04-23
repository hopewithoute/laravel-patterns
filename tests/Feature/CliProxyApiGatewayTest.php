<?php

namespace Tests\Feature;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;
use Laravel\Ai\Tools\Request as ToolRequest;
use Stringable;
use Tests\TestCase;

class CliProxyApiGatewayTest extends TestCase
{
    public function test_cliproxyapi_driver_can_complete_tool_loop_via_chat_completions(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        Http::fake([
            'https://cliproxyapi.test/v1/chat/completions' => Http::sequence()
                ->push([
                    'id' => 'chatcmpl_1',
                    'model' => 'gpt-5.4-mini-2026-03-17',
                    'choices' => [[
                        'index' => 0,
                        'finish_reason' => 'tool_calls',
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'FakeCreateTaskTool',
                                    'arguments' => json_encode([
                                        'title' => 'Create task from proxy',
                                    ], JSON_THROW_ON_ERROR),
                                ],
                            ]],
                        ],
                    ]],
                    'usage' => [
                        'prompt_tokens' => 10,
                        'completion_tokens' => 5,
                        'prompt_tokens_details' => ['cached_tokens' => 0],
                        'completion_tokens_details' => ['reasoning_tokens' => 0],
                    ],
                ])
                ->push([
                    'id' => 'chatcmpl_2',
                    'model' => 'gpt-5.4-mini-2026-03-17',
                    'choices' => [[
                        'index' => 0,
                        'finish_reason' => 'stop',
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Task created successfully.',
                            'tool_calls' => null,
                        ],
                    ]],
                    'usage' => [
                        'prompt_tokens' => 12,
                        'completion_tokens' => 3,
                        'prompt_tokens_details' => ['cached_tokens' => 0],
                        'completion_tokens_details' => ['reasoning_tokens' => 0],
                    ],
                ]),
        ]);

        $response = \Laravel\Ai\agent(
            instructions: 'Use the available tool to create a task.',
            tools: [new FakeCreateTaskTool],
        )->prompt(
            prompt: 'Create the task now.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );

        $this->assertSame('Task created successfully.', $response->text);
        $this->assertCount(1, $response->toolCalls);
        $this->assertCount(1, $response->toolResults);
        $this->assertSame('gpt-5.4-mini-2026-03-17', $response->meta->model);
        $this->assertSame('task-123', json_decode($response->toolResults->first()->result, true, flags: JSON_THROW_ON_ERROR)['task_id']);

        $requests = Http::recorded()->map(fn (array $pair) => $pair[0])->values();

        $this->assertCount(2, $requests);
        $this->assertSame('gpt-5.4-mini', $requests[0]->data()['model']);
        $this->assertSame('gpt-5.4-mini', $requests[1]->data()['model']);
        $this->assertSame('tool', $requests[1]->data()['messages'][3]['role']);
        $this->assertSame('call_1', $requests[1]->data()['messages'][3]['tool_call_id']);
    }

    public function test_cliproxyapi_driver_uses_logical_names_for_wrapped_tools_during_chat_completions(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        Http::fake([
            'https://cliproxyapi.test/v1/chat/completions' => Http::sequence()
                ->push([
                    'id' => 'chatcmpl_wrapped_1',
                    'model' => 'gpt-5.4-mini-2026-03-17',
                    'choices' => [[
                        'index' => 0,
                        'finish_reason' => 'tool_calls',
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_lookup_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'LookupProjectsTool',
                                    'arguments' => json_encode([
                                        'query' => 'web design',
                                    ], JSON_THROW_ON_ERROR),
                                ],
                            ]],
                        ],
                    ]],
                    'usage' => [
                        'prompt_tokens' => 10,
                        'completion_tokens' => 5,
                        'prompt_tokens_details' => ['cached_tokens' => 0],
                        'completion_tokens_details' => ['reasoning_tokens' => 0],
                    ],
                ])
                ->push([
                    'id' => 'chatcmpl_wrapped_2',
                    'model' => 'gpt-5.4-mini-2026-03-17',
                    'choices' => [[
                        'index' => 0,
                        'finish_reason' => 'stop',
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Project ditemukan.',
                            'tool_calls' => null,
                        ],
                    ]],
                    'usage' => [
                        'prompt_tokens' => 12,
                        'completion_tokens' => 3,
                        'prompt_tokens_details' => ['cached_tokens' => 0],
                        'completion_tokens_details' => ['reasoning_tokens' => 0],
                    ],
                ]),
        ]);

        $response = \Laravel\Ai\agent(
            instructions: 'Use the available lookup tool before creating data.',
            tools: [
                new NamedDelegatingTool('CreateTaskTool', fn (ToolRequest $request): string => json_encode([
                    'task_id' => 'task-123',
                    'title' => $request['title'] ?? null,
                ], JSON_THROW_ON_ERROR)),
                new NamedDelegatingTool('LookupProjectsTool', fn (ToolRequest $request): string => json_encode([
                    'projects' => [[
                        'project_id' => 'project-456',
                        'project_name' => 'Web Design',
                        'query' => $request['query'] ?? null,
                    ]],
                ], JSON_THROW_ON_ERROR)),
            ],
        )->prompt(
            prompt: 'Cari project web design.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );

        $this->assertSame('Project ditemukan.', $response->text);
        $this->assertCount(1, $response->toolCalls);
        $this->assertCount(1, $response->toolResults);
        $this->assertSame('LookupProjectsTool', $response->toolCalls->first()->name);
        $this->assertSame('LookupProjectsTool', $response->toolResults->first()->name);
        $this->assertSame(
            '{"projects":[{"project_id":"project-456","project_name":"Web Design","query":"web design"}]}',
            $response->toolResults->first()->result,
        );

        $request = Http::recorded()->map(fn (array $pair) => $pair[0])->first();

        $this->assertSame([
            'CreateTaskTool',
            'LookupProjectsTool',
        ], collect($request->data()['tools'])->pluck('function.name')->all());
    }

    public function test_cliproxyapi_driver_handles_plain_chat_completion_without_tool_calls(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        Http::fake([
            'https://cliproxyapi.test/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl_plain',
                'model' => 'gpt-5.4-mini-2026-03-17',
                'choices' => [[
                    'index' => 0,
                    'finish_reason' => 'stop',
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello from cliproxyapi.',
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 8,
                    'completion_tokens' => 4,
                    'prompt_tokens_details' => ['cached_tokens' => 0],
                    'completion_tokens_details' => ['reasoning_tokens' => 0],
                ],
            ]),
        ]);

        $response = \Laravel\Ai\agent(
            instructions: 'Reply briefly.',
            tools: [],
        )->prompt(
            prompt: 'Say hello.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );

        $this->assertSame('Hello from cliproxyapi.', $response->text);
        $this->assertCount(0, $response->toolCalls);
        $this->assertCount(0, $response->toolResults);
    }

    public function test_cliproxyapi_driver_requires_explicit_tool_names(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must expose an explicit logical name');

        \Laravel\Ai\agent(
            instructions: 'Reply briefly.',
            tools: [new UnnamedFakeTool],
        )->prompt(
            prompt: 'Say hello.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );
    }

    public function test_cliproxyapi_driver_supports_structured_output_via_chat_completions(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        Http::fake([
            'https://cliproxyapi.test/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl_structured',
                'model' => 'gpt-5.4-mini-2026-03-17',
                'choices' => [[
                    'index' => 0,
                    'finish_reason' => 'stop',
                    'message' => [
                        'role' => 'assistant',
                        'content' => '{"title":"Review weekly tasks","priority":"medium"}',
                    ],
                ]],
                'usage' => [
                    'prompt_tokens' => 14,
                    'completion_tokens' => 8,
                    'prompt_tokens_details' => ['cached_tokens' => 0],
                    'completion_tokens_details' => ['reasoning_tokens' => 0],
                ],
            ]),
        ]);

        $response = \Laravel\Ai\agent(
            instructions: 'Return structured JSON only.',
            schema: fn (JsonSchema $schema) => [
                'title' => $schema->string()->required(),
                'priority' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
            ],
        )->prompt(
            prompt: 'Generate a todo with title and priority.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );

        $this->assertInstanceOf(StructuredAgentResponse::class, $response);
        $this->assertSame('Review weekly tasks', $response['title']);
        $this->assertSame('medium', $response['priority']);
        $this->assertSame(
            '{"title":"Review weekly tasks","priority":"medium"}',
            $response->text,
        );

        $request = Http::recorded()->map(fn (array $pair) => $pair[0])->sole();

        $this->assertSame('json_schema', data_get($request->data(), 'response_format.type'));
        $this->assertSame(
            'schema_definition',
            data_get($request->data(), 'response_format.json_schema.name'),
        );
        $this->assertTrue(data_get($request->data(), 'response_format.json_schema.strict'));
        $this->assertSame(
            'object',
            data_get($request->data(), 'response_format.json_schema.schema.type'),
        );
    }

    public function test_cliproxyapi_driver_can_stream_tool_calls_via_chat_completions(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        Http::fake([
            'https://cliproxyapi.test/v1/chat/completions' => Http::sequence()
                ->push($this->chatCompletionStream([
                    [
                        'id' => 'chatcmpl_stream_1',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [
                                'tool_calls' => [[
                                    'index' => 0,
                                    'id' => 'call_stream_1',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'FakeCreateTaskTool',
                                        'arguments' => '{"title":"Create',
                                    ],
                                ]],
                            ],
                            'finish_reason' => null,
                        ]],
                    ],
                    [
                        'id' => 'chatcmpl_stream_1',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [
                                'tool_calls' => [[
                                    'index' => 0,
                                    'function' => [
                                        'arguments' => ' task from stream"}',
                                    ],
                                ]],
                            ],
                            'finish_reason' => null,
                        ]],
                    ],
                    [
                        'id' => 'chatcmpl_stream_1',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [],
                            'finish_reason' => 'tool_calls',
                        ]],
                        'usage' => [
                            'prompt_tokens' => 10,
                            'completion_tokens' => 5,
                            'prompt_tokens_details' => ['cached_tokens' => 0],
                            'completion_tokens_details' => ['reasoning_tokens' => 0],
                        ],
                    ],
                ]))
                ->push($this->chatCompletionStream([
                    [
                        'id' => 'chatcmpl_stream_2',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [
                                'content' => 'Task created successfully.',
                            ],
                            'finish_reason' => null,
                        ]],
                    ],
                    [
                        'id' => 'chatcmpl_stream_2',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [],
                            'finish_reason' => 'stop',
                        ]],
                        'usage' => [
                            'prompt_tokens' => 12,
                            'completion_tokens' => 3,
                            'prompt_tokens_details' => ['cached_tokens' => 0],
                            'completion_tokens_details' => ['reasoning_tokens' => 0],
                        ],
                    ],
                ])),
        ]);

        $stream = \Laravel\Ai\agent(
            instructions: 'Use the available tool to create a task.',
            tools: [new FakeCreateTaskTool],
        )->stream(
            prompt: 'Create the task now.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );

        $events = collect(iterator_to_array($stream));

        $this->assertSame('Task created successfully.', TextDelta::combine($events));
        $this->assertCount(1, $events->whereInstanceOf(ToolCallEvent::class));
        $this->assertCount(1, $events->whereInstanceOf(ToolResultEvent::class));
        $this->assertCount(1, $events->whereInstanceOf(StreamEnd::class));

        $toolCallEvent = $events->first(fn ($event) => $event instanceof ToolCallEvent);
        $toolResultEvent = $events->first(fn ($event) => $event instanceof ToolResultEvent);

        $this->assertSame('FakeCreateTaskTool', $toolCallEvent->toolCall->name);
        $this->assertSame(['title' => 'Create task from stream'], $toolCallEvent->toolCall->arguments);
        $this->assertSame('{"task_id":"task-123","title":"Create task from stream"}', $toolResultEvent->toolResult->result);
    }

    public function test_cliproxyapi_driver_uses_logical_names_for_wrapped_tools_during_streaming(): void
    {
        config()->set('ai.default', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.driver', 'cliproxyapi');
        config()->set('ai.providers.cliproxyapi.key', 'test-key');
        config()->set('ai.providers.cliproxyapi.url', 'https://cliproxyapi.test/v1');

        Http::fake([
            'https://cliproxyapi.test/v1/chat/completions' => Http::sequence()
                ->push($this->chatCompletionStream([
                    [
                        'id' => 'chatcmpl_wrapped_stream_1',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [
                                'tool_calls' => [[
                                    'index' => 0,
                                    'id' => 'call_lookup_stream_1',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'LookupProjectsTool',
                                        'arguments' => '{"query":"web',
                                    ],
                                ]],
                            ],
                            'finish_reason' => null,
                        ]],
                    ],
                    [
                        'id' => 'chatcmpl_wrapped_stream_1',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [
                                'tool_calls' => [[
                                    'index' => 0,
                                    'function' => [
                                        'arguments' => ' design"}',
                                    ],
                                ]],
                            ],
                            'finish_reason' => null,
                        ]],
                    ],
                    [
                        'id' => 'chatcmpl_wrapped_stream_1',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [],
                            'finish_reason' => 'tool_calls',
                        ]],
                        'usage' => [
                            'prompt_tokens' => 10,
                            'completion_tokens' => 5,
                            'prompt_tokens_details' => ['cached_tokens' => 0],
                            'completion_tokens_details' => ['reasoning_tokens' => 0],
                        ],
                    ],
                ]))
                ->push($this->chatCompletionStream([
                    [
                        'id' => 'chatcmpl_wrapped_stream_2',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [
                                'content' => 'Project lookup selesai.',
                            ],
                            'finish_reason' => null,
                        ]],
                    ],
                    [
                        'id' => 'chatcmpl_wrapped_stream_2',
                        'model' => 'gpt-5.4-mini-2026-03-17',
                        'choices' => [[
                            'index' => 0,
                            'delta' => [],
                            'finish_reason' => 'stop',
                        ]],
                        'usage' => [
                            'prompt_tokens' => 12,
                            'completion_tokens' => 3,
                            'prompt_tokens_details' => ['cached_tokens' => 0],
                            'completion_tokens_details' => ['reasoning_tokens' => 0],
                        ],
                    ],
                ])),
        ]);

        $stream = \Laravel\Ai\agent(
            instructions: 'Use lookup tools before write tools.',
            tools: [
                new NamedDelegatingTool('CreateTaskTool', fn (ToolRequest $request): string => json_encode([
                    'task_id' => 'task-123',
                    'title' => $request['title'] ?? null,
                ], JSON_THROW_ON_ERROR)),
                new NamedDelegatingTool('LookupProjectsTool', fn (ToolRequest $request): string => json_encode([
                    'projects' => [[
                        'project_id' => 'project-456',
                        'project_name' => 'Web Design',
                        'query' => $request['query'] ?? null,
                    ]],
                ], JSON_THROW_ON_ERROR)),
            ],
        )->stream(
            prompt: 'Cari project web design.',
            provider: 'cliproxyapi',
            model: 'gpt-5.4-mini',
        );

        $events = collect(iterator_to_array($stream));
        $toolCallEvent = $events->first(fn ($event) => $event instanceof ToolCallEvent);
        $toolResultEvent = $events->first(fn ($event) => $event instanceof ToolResultEvent);

        $this->assertSame('Project lookup selesai.', TextDelta::combine($events));
        $this->assertInstanceOf(ToolCallEvent::class, $toolCallEvent);
        $this->assertInstanceOf(ToolResultEvent::class, $toolResultEvent);
        $this->assertSame('LookupProjectsTool', $toolCallEvent->toolCall->name);
        $this->assertSame(['query' => 'web design'], $toolCallEvent->toolCall->arguments);
        $this->assertSame('LookupProjectsTool', $toolResultEvent->toolResult->name);
        $this->assertSame(
            '{"projects":[{"project_id":"project-456","project_name":"Web Design","query":"web design"}]}',
            $toolResultEvent->toolResult->result,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $chunks
     */
    private function chatCompletionStream(array $chunks): string
    {
        $payload = collect($chunks)
            ->map(fn (array $chunk) => 'data: '.json_encode($chunk, JSON_THROW_ON_ERROR))
            ->push('data: [DONE]')
            ->implode("\n\n");

        return $payload."\n\n";
    }
}

class FakeCreateTaskTool implements Tool
{
    public function name(): string
    {
        return 'FakeCreateTaskTool';
    }

    public function description(): Stringable|string
    {
        return 'Create a task in the workspace.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
        ];
    }

    public function handle(ToolRequest $request): Stringable|string
    {
        return json_encode([
            'task_id' => 'task-123',
            'title' => $request['title'],
        ], JSON_THROW_ON_ERROR);
    }
}

class UnnamedFakeTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Unnamed test tool.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string(),
        ];
    }

    public function handle(ToolRequest $request): Stringable|string
    {
        return (string) ($request['title'] ?? '');
    }
}

class NamedDelegatingTool implements Tool
{
    /**
     * @param  \Closure(ToolRequest): string  $handler
     */
    public function __construct(
        private readonly string $logicalName,
        private readonly \Closure $handler,
    ) {}

    public function name(): string
    {
        return $this->logicalName;
    }

    public function description(): Stringable|string
    {
        return "Tool {$this->logicalName}.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string(),
            'title' => $schema->string(),
        ];
    }

    public function handle(ToolRequest $request): Stringable|string
    {
        return ($this->handler)($request);
    }
}
