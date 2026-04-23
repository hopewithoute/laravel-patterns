<?php

namespace App\AI\Gateway;

use App\AI\Gateway\Concerns\ProxyRequestBuilder;
use Generator;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Gateway\OpenAi\OpenAiGateway;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Responses\TextResponse;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;

class CliProxyApiGateway extends OpenAiGateway
{
    use ProxyRequestBuilder;

    public function generateText(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): TextResponse {
        $conversationMessages = Collection::make($messages)->map(
            static fn (mixed $message) => Message::tryFrom($message)
        );

        return $this->processConversation(
            $provider,
            $model,
            $instructions,
            $conversationMessages,
            $tools,
            $schema,
            $options,
            $timeout,
            new Collection,
        );
    }

    public function streamText(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
        ?TextGenerationOptions $options = null,
        ?int $timeout = null,
    ): Generator {
        $conversationMessages = Collection::make($messages)->map(
            static fn (mixed $message) => Message::tryFrom($message)
        );

        yield from $this->streamConversation(
            $invocationId,
            $provider,
            $model,
            $instructions,
            $conversationMessages,
            $tools,
            $schema,
            $options,
            $timeout,
        );
    }

    /**
     * @param  array<Tool>  $tools
     */
    private function processConversation(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        Collection $conversationMessages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        Collection $steps,
    ): TextResponse {
        $response = $this->withErrorHandling(
            $provider instanceof Provider ? $provider->name() : 'cliproxyapi',
            fn () => $this->client($provider, $timeout)->post('chat/completions', $this->buildChatRequestBody(
                $model,
                $instructions,
                $conversationMessages,
                $tools,
                $schema,
                $options,
            )),
        );

        $data = $response->json();

        $this->validateChatResponse($response, $data);

        $message = data_get($data, 'choices.0.message', []);
        $text = (string) ($message['content'] ?? '');
        $toolCalls = $this->extractChatToolCalls($message['tool_calls'] ?? []);
        $usage = $this->extractChatUsage($data);
        $meta = new Meta($provider instanceof Provider ? $provider->name() : 'cliproxyapi', data_get($data, 'model', $model));
        $finishReason = $this->mapFinishReason((string) data_get($data, 'choices.0.finish_reason', ''));

        $assistantMessage = new AssistantMessage($text, collect($toolCalls));
        $conversationMessages->push($assistantMessage);

        $toolResults = [];

        if ($finishReason === FinishReason::ToolCalls && filled($toolCalls)) {
            $toolResults = $this->executeChatToolCalls($toolCalls, $tools);
            $conversationMessages->push(new ToolResultMessage(collect($toolResults)));
        }

        $steps->push(new Step(
            $text,
            $toolCalls,
            $toolResults,
            $finishReason,
            $usage,
            $meta,
        ));

        if ($finishReason === FinishReason::ToolCalls && filled($toolCalls)) {
            return $this->processConversation(
                $provider,
                $model,
                $instructions,
                $conversationMessages,
                $tools,
                $schema,
                $options,
                $timeout,
                $steps,
            );
        }

        if (filled($schema)) {
            return (new StructuredTextResponse(
                json_decode($text, true) ?? [],
                $text,
                $this->combineStepUsage($steps),
                $meta,
            ))->withToolCallsAndResults(
                toolCalls: collect($steps->flatMap(fn (Step $step) => $step->toolCalls)),
                toolResults: collect($steps->flatMap(fn (Step $step) => $step->toolResults)),
            )->withSteps($steps);
        }

        return (new TextResponse(
            $text,
            $this->combineStepUsage($steps),
            $meta,
        ))->withMessages($conversationMessages)->withSteps($steps);
    }

    /**
     * @param  array<Tool>  $tools
     */
    private function streamConversation(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        Collection $conversationMessages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        int $depth = 0,
        ?int $maxSteps = null,
    ): Generator {
        $maxSteps ??= min($options?->maxSteps ?? max((int) round(max(count($tools), 1) * 1.5), 1), 10);

        $body = $this->buildChatRequestBody(
            $model,
            $instructions,
            $conversationMessages,
            $tools,
            $schema,
            $options,
        );

        $body['stream'] = true;
        $body['stream_options'] = ['include_usage' => true];

        $response = $this->withErrorHandling(
            $provider instanceof Provider ? $provider->name() : 'cliproxyapi',
            fn () => $this->client($provider, $timeout)
                ->withOptions(['stream' => true])
                ->post('chat/completions', $body),
        );

        $messageId = $this->generateStreamEventId();
        $currentText = '';
        $pendingToolCalls = [];
        $usage = new Usage(0, 0);
        $responseModel = $model;
        $textStarted = false;
        $streamStarted = false;
        $finishReason = 'stop';

        foreach ($this->parseServerSentEvents($response->getBody()) as $data) {
            if (isset($data['error'])) {
                yield (new Error(
                    $this->generateStreamEventId(),
                    'cliproxyapi_stream_error',
                    (string) data_get($data, 'error.message', 'cliproxyapi stream failed.'),
                    false,
                    time(),
                ))->withInvocationId($invocationId);

                return;
            }

            if (! $streamStarted) {
                $responseModel = (string) data_get($data, 'model', $model);
                $streamStarted = true;

                yield (new StreamStart(
                    $this->generateStreamEventId(),
                    $provider instanceof Provider ? $provider->name() : 'unknown',
                    $responseModel,
                    time(),
                ))->withInvocationId($invocationId);
            }

            $delta = data_get($data, 'choices.0.delta', []);
            $contentDelta = (string) ($delta['content'] ?? '');

            if ($contentDelta !== '') {
                if (! $textStarted) {
                    $textStarted = true;

                    yield (new TextStart(
                        $this->generateStreamEventId(),
                        $messageId,
                        time(),
                    ))->withInvocationId($invocationId);
                }

                $currentText .= $contentDelta;

                yield (new TextDelta(
                    $this->generateStreamEventId(),
                    $messageId,
                    $contentDelta,
                    time(),
                ))->withInvocationId($invocationId);
            }

            foreach ((array) ($delta['tool_calls'] ?? []) as $toolCallDelta) {
                $index = (int) ($toolCallDelta['index'] ?? count($pendingToolCalls));
                $pendingToolCalls[$index] ??= [
                    'id' => '',
                    'name' => '',
                    'arguments' => '',
                ];

                if (filled($toolCallDelta['id'] ?? null)) {
                    $pendingToolCalls[$index]['id'] = (string) $toolCallDelta['id'];
                }

                if (filled(data_get($toolCallDelta, 'function.name'))) {
                    $pendingToolCalls[$index]['name'] .= (string) data_get($toolCallDelta, 'function.name');
                }

                if (filled(data_get($toolCallDelta, 'function.arguments'))) {
                    $pendingToolCalls[$index]['arguments'] .= (string) data_get($toolCallDelta, 'function.arguments');
                }
            }

            if (filled(data_get($data, 'usage'))) {
                $usage = $this->extractChatUsage($data);
            }

            if (filled(data_get($data, 'choices.0.finish_reason'))) {
                $finishReason = (string) data_get($data, 'choices.0.finish_reason');
            }
        }

        if ($textStarted) {
            yield (new TextEnd(
                $this->generateStreamEventId(),
                $messageId,
                time(),
            ))->withInvocationId($invocationId);
        }

        $toolCalls = $this->mapStreamingToolCalls($pendingToolCalls);

        if ($toolCalls !== []) {
            $assistantMessage = new AssistantMessage($currentText, collect($toolCalls));
            $conversationMessages->push($assistantMessage);

            foreach ($toolCalls as $toolCall) {
                yield (new ToolCallEvent(
                    $this->generateStreamEventId(),
                    $toolCall,
                    time(),
                ))->withInvocationId($invocationId);
            }

            $toolResults = $this->executeChatToolCalls($toolCalls, $tools);

            foreach ($toolResults as $toolResult) {
                yield (new ToolResultEvent(
                    $this->generateStreamEventId(),
                    $toolResult,
                    true,
                    null,
                    time(),
                ))->withInvocationId($invocationId);
            }

            $conversationMessages->push(new ToolResultMessage(collect($toolResults)));

            if ($depth + 1 < $maxSteps) {
                yield from $this->streamConversation(
                    $invocationId,
                    $provider,
                    $model,
                    $instructions,
                    $conversationMessages,
                    $tools,
                    $schema,
                    $options,
                    $timeout,
                    $depth + 1,
                    $maxSteps,
                );

                return;
            }
        }

        yield (new StreamEnd(
            $this->generateStreamEventId(),
            $finishReason === 'tool_calls' ? 'stop' : $finishReason,
            $usage,
            time(),
        ))->withInvocationId($invocationId);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $toolCalls
     * @return array<int, ToolCall>
     */
    private function extractChatToolCalls(?array $toolCalls): array
    {
        return collect($toolCalls ?? [])
            ->map(function (array $toolCall): ToolCall {
                return new ToolCall(
                    (string) ($toolCall['id'] ?? ''),
                    (string) data_get($toolCall, 'function.name', ''),
                    json_decode((string) data_get($toolCall, 'function.arguments', '{}'), true) ?? [],
                    (string) ($toolCall['id'] ?? ''),
                );
            })->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateChatResponse(Response $response, array $data): void
    {
        if ($response->failed() || isset($data['error'])) {
            throw new InvalidArgumentException((string) data_get($data, 'error.message', 'cliproxyapi request failed.'));
        }

        if (! isset($data['choices'][0]['message'])) {
            throw new InvalidArgumentException('cliproxyapi returned an unexpected chat completion payload.');
        }
    }

    private function mapFinishReason(string $finishReason): FinishReason
    {
        return match ($finishReason) {
            'stop' => FinishReason::Stop,
            'tool_calls' => FinishReason::ToolCalls,
            'length' => FinishReason::Length,
            'content_filter' => FinishReason::ContentFilter,
            default => FinishReason::Unknown,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractChatUsage(array $data): Usage
    {
        return new Usage(
            (int) data_get($data, 'usage.prompt_tokens', 0),
            (int) data_get($data, 'usage.completion_tokens', 0),
            0,
            (int) data_get($data, 'usage.prompt_tokens_details.cached_tokens', 0),
            (int) data_get($data, 'usage.completion_tokens_details.reasoning_tokens', 0),
        );
    }

    private function combineStepUsage(Collection $steps): Usage
    {
        return $steps->reduce(
            fn (Usage $carry, Step $step) => $carry->add($step->usage),
            new Usage,
        );
    }

    /**
     * @param  array<int, array{id: string, name: string, arguments: string}>  $toolCalls
     * @return array<int, ToolCall>
     */
    private function mapStreamingToolCalls(array $toolCalls): array
    {
        return collect($toolCalls)
            ->filter(fn (array $toolCall) => filled($toolCall['id']) && filled($toolCall['name']))
            ->map(fn (array $toolCall): ToolCall => new ToolCall(
                $toolCall['id'],
                $toolCall['name'],
                json_decode($toolCall['arguments'] ?: '{}', true) ?? [],
                $toolCall['id'],
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @param  array<Tool>  $tools
     * @return array<int, ToolResult>
     */
    private function executeChatToolCalls(array $toolCalls, array $tools): array
    {
        return collect($toolCalls)
            ->map(function (ToolCall $toolCall) use ($tools): ?ToolResult {
                $tool = $this->findTool($toolCall->name, $tools);

                if ($tool === null) {
                    return null;
                }

                return new ToolResult(
                    $toolCall->id,
                    $toolCall->name,
                    $toolCall->arguments,
                    $this->executeTool($tool, $toolCall->arguments),
                    $toolCall->resultId,
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<Tool>  $tools
     */
    protected function findTool(string $name, array $tools): ?Tool
    {
        foreach ($tools as $tool) {
            if (! $tool instanceof Tool) {
                continue;
            }

            if ($this->resolveToolName($tool) === $name) {
                return $tool;
            }
        }

        return null;
    }

    private function generateStreamEventId(): string
    {
        return strtolower((string) Str::uuid7());
    }
}
