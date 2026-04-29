<?php

namespace Labtime\AiRuntime\Foundation\Config;

readonly class RuntimeGuardrailsConfig
{
    /**
     * @param  array<string, array<int, string>>  $keywordClassifier
     * @param  array<int, string>  $blockedPhrases
     * @param  array<int, string>  $promptInjectionPhrases
     */
    public function __construct(
        public array $keywordClassifier = [],
        public array $blockedPhrases = [],
        public array $promptInjectionPhrases = [],
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            keywordClassifier: is_array($config['keyword_classifier'] ?? null) ? $config['keyword_classifier'] : [],
            blockedPhrases: is_array($config['blocked_phrases'] ?? null) ? $config['blocked_phrases'] : [],
            promptInjectionPhrases: is_array($config['prompt_injection_phrases'] ?? null) ? $config['prompt_injection_phrases'] : [],
        );
    }
}
