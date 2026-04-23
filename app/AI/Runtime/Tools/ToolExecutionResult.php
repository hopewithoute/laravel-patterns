<?php

namespace App\AI\Runtime\Tools;

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
        public ?string $failureType = null,
        public string $failureBehavior = 'none',
        public array $metadata = [],
    ) {}

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
            failureBehavior: 'none',
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
        ?string $failureType = 'unknown_error',
        string $failureBehavior = 'surface_to_user',
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
}
