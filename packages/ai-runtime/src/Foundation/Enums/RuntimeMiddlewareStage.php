<?php

namespace Labtime\AiRuntime\Foundation\Enums;

enum RuntimeMiddlewareStage: string
{
    case Decision = 'decision';
    case Retrieval = 'retrieval';
    case Prompt = 'prompt';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
