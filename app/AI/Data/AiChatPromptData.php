<?php

namespace App\AI\Data;

use App\AI\Runtime\Artifacts\RuntimeArtifactModeCatalog;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class AiChatPromptData extends Data
{
    public function __construct(
        public string $prompt,
        public string $artifact_mode = 'auto',
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'prompt' => ['required', 'string', 'max:10000'],
            'artifact_mode' => ['required', 'string', 'in:'.implode(',', app(RuntimeArtifactModeCatalog::class)->values())],
        ];
    }

    public static function authorize(): bool
    {
        return true;
    }
}
