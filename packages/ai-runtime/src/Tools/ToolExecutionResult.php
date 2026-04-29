<?php

namespace Labtime\AiRuntime\Tools;

use Labtime\AiRuntime\Foundation\Enums\ToolFailureBehavior;
use Labtime\AiRuntime\Foundation\Enums\ToolFailureType;

readonly class ToolExecutionResult
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $toolName,
        public array $input = [],
        public mixed $result = null,
        public bool $successful = true,
        public ?string $error = null,
        ToolFailureType|string|null $failureType = null,
        ToolFailureBehavior|string $failureBehavior = ToolFailureBehavior::None,
        public array $metadata = [],
    ) {
        $this->failureType = self::failureTypeValue($failureType);
        $this->failureBehavior = self::failureBehaviorValue($failureBehavior);
    }

    public ?string $failureType;

    public string $failureBehavior;

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
     */
    public static function success(
        string $toolName,
        array $input = [],
        mixed $result = null,
        array $metadata = [],
    ): self {
        return new self(
            toolName: $toolName,
            input: $input,
            result: $result,
            successful: true,
            error: null,
            failureType: null,
            failureBehavior: ToolFailureBehavior::None,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
     */
    public static function failure(
        string $toolName,
        array $input = [],
        ?string $error = null,
        ToolFailureType|string|null $failureType = ToolFailureType::UnknownError,
        ToolFailureBehavior|string $failureBehavior = ToolFailureBehavior::SurfaceToUser,
        array $metadata = [],
    ): self {
        return new self(
            toolName: $toolName,
            input: $input,
            result: null,
            successful: false,
            error: $error,
            failureType: $failureType,
            failureBehavior: $failureBehavior,
            metadata: $metadata,
        );
    }

    private static function failureTypeValue(ToolFailureType|string|null $failureType): ?string
    {
        return $failureType instanceof ToolFailureType ? $failureType->value : $failureType;
    }

    private static function failureBehaviorValue(ToolFailureBehavior|string $failureBehavior): string
    {
        return $failureBehavior instanceof ToolFailureBehavior ? $failureBehavior->value : $failureBehavior;
    }
}
