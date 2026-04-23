<?php

namespace App\AI\Resolvers;

class AiModelResolver
{
    /**
     * Resolve the AI model from the application config override.
     *
     * Returns null when the default provider model should be used.
     */
    public function resolve(): ?string
    {
        $model = (string) config('ai.model_override', 'provider-default');

        return $model === 'provider-default' ? null : $model;
    }
}
