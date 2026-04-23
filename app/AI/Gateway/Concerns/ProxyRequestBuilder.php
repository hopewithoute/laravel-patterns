<?php

namespace App\AI\Gateway\Concerns;

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\ObjectSchema;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;

trait ProxyRequestBuilder
{
    /**
     * @param  array<Tool>  $tools
     * @return array<string, mixed>
     */
    protected function buildChatRequestBody(
        string $model,
        ?string $instructions,
        Collection $conversationMessages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
    ): array {
        $body = [
            'model' => $model,
            'messages' => $this->mapMessages($conversationMessages, $instructions),
        ];

        if (filled($tools)) {
            $body['tools'] = $this->mapChatTools($tools);
            $body['tool_choice'] = 'auto';
        }

        if (filled($schema)) {
            $body['response_format'] = $this->buildResponseFormat($schema);
        }

        if (! is_null($options?->maxTokens)) {
            $body['max_tokens'] = $options->maxTokens;
        }

        if (! is_null($options?->temperature)) {
            $body['temperature'] = $options->temperature;
        }

        return $body;
    }

    protected function buildResponseFormat(array $schema): array
    {
        $schemaArray = (new ObjectSchema($schema))->toSchema();

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $schemaArray['name'] ?? 'schema_definition',
                'schema' => Arr::except($schemaArray, ['name']),
                'strict' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapMessages(Collection $conversationMessages, ?string $instructions): array
    {
        $messages = [];

        if (filled($instructions)) {
            $messages[] = [
                'role' => 'system',
                'content' => $instructions,
            ];
        }

        foreach ($conversationMessages as $message) {
            match ($message->role) {
                MessageRole::User => $messages[] = $this->mapChatUserMessage($message),
                MessageRole::Assistant => array_push($messages, ...$this->mapChatAssistantMessage($message)),
                MessageRole::ToolResult => array_push($messages, ...$this->mapChatToolResultMessages($message)),
            };
        }

        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapChatUserMessage(Message $message): array
    {
        if ($message instanceof UserMessage && $message->attachments->isNotEmpty()) {
            throw new \InvalidArgumentException('Attachments are not supported for the cliproxyapi driver.');
        }

        return [
            'role' => 'user',
            'content' => $message->content ?? '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapChatAssistantMessage(Message $message): array
    {
        $mappedMessage = [
            'role' => 'assistant',
            'content' => $message->content ?: null,
        ];

        if ($message instanceof AssistantMessage && $message->toolCalls->isNotEmpty()) {
            $mappedMessage['tool_calls'] = $message->toolCalls
                ->map(fn (ToolCall $toolCall) => [
                    'id' => $toolCall->id,
                    'type' => 'function',
                    'function' => [
                        'name' => $toolCall->name,
                        'arguments' => json_encode($toolCall->arguments, JSON_THROW_ON_ERROR),
                    ],
                ])->all();
        }

        return [$mappedMessage];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapChatToolResultMessages(Message $message): array
    {
        if (! $message instanceof ToolResultMessage) {
            return [];
        }

        return $message->toolResults
            ->map(fn (ToolResult $toolResult) => [
                'role' => 'tool',
                'tool_call_id' => $toolResult->resultId ?? $toolResult->id,
                'content' => $this->serializeToolResultOutput($toolResult->result),
            ])->all();
    }

    /**
     * @param  array<Tool>  $tools
     * @return array<int, array<string, mixed>>
     */
    protected function mapChatTools(array $tools): array
    {
        return collect($tools)
            ->filter(fn (mixed $tool) => $tool instanceof Tool)
            ->map(function (Tool $tool): array {
                $schema = $tool->schema(new JsonSchemaTypeFactory);

                $schemaArray = filled($schema)
                    ? (new ObjectSchema($schema))->toSchema()
                    : [];

                return [
                    'type' => 'function',
                    'function' => [
                        'name' => $this->resolveToolName($tool),
                        'description' => (string) $tool->description(),
                        'parameters' => [
                            'type' => 'object',
                            'properties' => $schemaArray['properties'] ?? (object) [],
                            'required' => $schemaArray['required'] ?? [],
                            'additionalProperties' => false,
                        ],
                    ],
                ];
            })->values()->all();
    }

    protected function resolveToolName(Tool $tool): string
    {
        if (method_exists($tool, 'name')) {
            $resolvedName = $tool->name();

            if (is_string($resolvedName) && $resolvedName !== '') {
                return $resolvedName;
            }
        }

        throw new \InvalidArgumentException('Tool ['.$tool::class.'] must expose an explicit logical name.');
    }
}
