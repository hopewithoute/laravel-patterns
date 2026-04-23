<?php

namespace App\AI\Runtime\Tools;

use App\AI\Exceptions\AiToolException;
use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\ToolExecutionPolicy;
use App\AI\Runtime\Tools\Registry\ToolRegistry;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stringable;
use Throwable;

readonly class WorkspaceToolExecutionPolicy implements ToolExecutionPolicy
{
    public function __construct(
        private ToolRegistry $toolRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string, array<string, mixed>): mixed  $next
     */
    public function execute(
        AiRuntimeContext $context,
        string $toolName,
        array $input,
        Closure $next,
    ): ToolExecutionResult {
        $toolDefinition = $this->toolRegistry->find($toolName);
        $operation = $toolDefinition?->operation ?? 'unknown';
        $attempt = 1;
        $maxAttempts = $toolDefinition?->maxAttempts ?? 1;

        while (true) {
            try {

                Log::info('AI tool invocation started.', [
                    'tool' => $toolName,
                    'operation' => $operation,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'workspace_id' => $context->organization->id,
                    'user_id' => $context->user->id,
                ]);

                $result = $next($toolName, $input);

                $executionResult = ToolExecutionResult::success(
                    toolName: $toolName,
                    input: $input,
                    result: $this->normalizeResult($result),
                    metadata: [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'operation' => $operation,
                        'workspace_id' => $context->organization->id,
                        'user_id' => $context->user->id,
                    ],
                );

                Log::info('AI tool invocation succeeded.', [
                    'tool' => $toolName,
                    'operation' => $operation,
                    'attempt' => $attempt,
                    'workspace_id' => $context->organization->id,
                    'user_id' => $context->user->id,
                ]);

                return $executionResult;
            } catch (Throwable $exception) {
                [$failureType, $failureBehavior] = $this->classifyFailure($exception);
                $shouldRetry = $attempt < $maxAttempts
                    && $failureBehavior === 'retry'
                    && $this->shouldRetry($toolName, $exception);

                Log::warning('AI tool invocation failed.', [
                    'tool' => $toolName,
                    'operation' => $operation,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'will_retry' => $shouldRetry,
                    'workspace_id' => $context->organization->id,
                    'user_id' => $context->user->id,
                    'exception' => $exception::class,
                    'failure_type' => $failureType,
                    'failure_behavior' => $failureBehavior,
                    'error' => $exception->getMessage(),
                ]);

                if ($shouldRetry) {
                    $attempt++;

                    continue;
                }

                return ToolExecutionResult::failure(
                    toolName: $toolName,
                    input: $input,
                    error: $exception->getMessage(),
                    failureType: $failureType,
                    failureBehavior: $failureBehavior,
                    metadata: [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'operation' => $operation,
                        'workspace_id' => $context->organization->id,
                        'user_id' => $context->user->id,
                        'exception' => $exception::class,
                        'failure_type' => $failureType,
                        'failure_behavior' => $failureBehavior,
                    ],
                );
            }
        }
    }

    private function normalizeResult(mixed $result): mixed
    {
        return match (true) {
            $result instanceof Stringable => (string) $result,
            default => $result,
        };
    }

    private function shouldRetry(string $toolName, Throwable $exception): bool
    {
        return $exception instanceof ConnectionException;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function classifyFailure(Throwable $exception): array
    {
        $normalizedMessage = strtolower($exception->getMessage());

        return match (true) {
            $exception instanceof AiToolException => ['ai_tool_error', 'ask_user'],
            $exception instanceof ValidationException => ['validation_error', 'ask_user'],
            $exception instanceof AuthorizationException => ['authorization_error', 'surface_to_user'],
            $exception instanceof ConnectionException => ['transient_error', 'retry'],
            str_contains($normalizedMessage, 'not authorized'),
            str_contains($normalizedMessage, 'outside the active workspace') => ['authorization_error', 'surface_to_user'],
            $exception instanceof \DomainException => ['domain_error', 'surface_to_user'],
            default => ['unknown_error', 'surface_to_user'],
        };
    }
}
