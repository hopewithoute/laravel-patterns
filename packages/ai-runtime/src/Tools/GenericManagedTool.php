<?php

namespace Labtime\AiRuntime\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Contracts\RunRecording;
use Labtime\AiRuntime\Foundation\Contracts\ToolExecutionPolicy;
use Labtime\AiRuntime\Foundation\Enums\ToolFailureBehavior;
use Labtime\AiRuntime\Foundation\Enums\ToolFailureType;
use Labtime\AiRuntime\Tools\Registry\ToolDefinition;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GenericManagedTool implements Tool
{
    public function __construct(
        private ToolDefinition $definition,
        private Tool $tool,
        private RuntimeContext $context,
        private ToolExecutionPolicy $toolExecutionPolicy,
        private ToolExecutionJournal $toolExecutionJournal,
        private RunRecording $recording,
    ) {}

    public function description(): Stringable|string
    {
        return $this->definition->llmDescription();
    }

    public function name(): string
    {
        return $this->definition->name;
    }

    public function outputContract(): ?string
    {
        return $this->definition->outputContract;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->definition->schema($schema);
    }

    public function handle(Request $request): Stringable|string
    {
        $toolName = $this->name();
        $callId = $this->recording->startCall('tool.execute', [
            'tool_name' => $toolName,
        ]);

        $executionResult = $this->toolExecutionPolicy->execute(
            context: $this->context,
            toolName: $toolName,
            input: $request->all(),
            next: fn (string $toolName, array $input): mixed => $this->tool->handle(new Request($input)),
        );

        $this->toolExecutionJournal->record($executionResult);
        $this->recording->finishCall($callId, $executionResult->successful ? 'succeeded' : 'failed', [
            'tool_name' => $toolName,
            'tool_id' => $executionResult->metadata['tool_id'] ?? null,
            'failure_type' => $executionResult->failureType,
            'error_message' => $executionResult->error,
        ]);

        if ($executionResult->successful) {
            return is_string($executionResult->result) || $executionResult->result instanceof Stringable
                ? $executionResult->result
                : json_encode($executionResult->result, JSON_THROW_ON_ERROR);
        }

        return $this->failureMessageFor($toolName, $executionResult);
    }

    private function failureMessageFor(string $toolName, ToolExecutionResult $executionResult): string
    {
        $failureType = $executionResult->failureType ?? ToolFailureType::UnknownError->value;
        $error = $executionResult->error ?? 'The action could not be completed.';

        return match ($executionResult->failureBehavior) {
            ToolFailureBehavior::AskUser->value => "{$toolName} failed with {$failureType}: {$error}. Do not retry automatically. Ask the user to correct the missing or invalid fields.",
            ToolFailureBehavior::Retry->value => "{$toolName} failed with {$failureType}: {$error}. Retry only if the transient dependency recovers.",
            default => "{$toolName} failed with {$failureType}: {$error}. Do not retry automatically. Explain the constraint to the user and ask for a different valid request if needed.",
        };
    }
}
