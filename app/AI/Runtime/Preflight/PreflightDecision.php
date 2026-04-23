<?php

namespace App\AI\Runtime\Preflight;

use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Enums\PreflightStatus;
use App\AI\Runtime\Enums\RiskLevel;

readonly class PreflightDecision
{
    /**
     * @param  array<int, string>  $allowedCapabilities
     * @param  array<int, string>  $reasons
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public AiIntent $intent,
        public RiskLevel $riskLevel = RiskLevel::Low,
        public PreflightStatus $status = PreflightStatus::Allow,
        public bool $needsRetrieval = false,
        public array $allowedCapabilities = [],
        public ArtifactIntent $artifactIntent = ArtifactIntent::Auto,
        public array $reasons = [],
        public array $metadata = [],
    ) {}

    /**
     * @param  array<int, string>  $allowedCapabilities
     * @param  array<int, string>  $reasons
     * @param  array<string, mixed>  $metadata
     */
    public static function allow(
        AiIntent $intent = AiIntent::WorkspaceOperation,
        array $allowedCapabilities = [],
        ArtifactIntent $artifactIntent = ArtifactIntent::Auto,
        array $reasons = [],
        RiskLevel $riskLevel = RiskLevel::Low,
        bool $needsRetrieval = false,
        array $metadata = [],
    ): self {
        return new self(
            intent: $intent,
            riskLevel: $riskLevel,
            status: PreflightStatus::Allow,
            needsRetrieval: $needsRetrieval,
            allowedCapabilities: $allowedCapabilities,
            artifactIntent: $artifactIntent,
            reasons: $reasons,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function reject(string $reason, AiIntent $intent = AiIntent::OutOfScope, array $metadata = []): self
    {
        return new self(
            intent: $intent,
            riskLevel: RiskLevel::High,
            status: PreflightStatus::Reject,
            needsRetrieval: false,
            allowedCapabilities: [],
            artifactIntent: ArtifactIntent::None,
            reasons: [$reason],
            metadata: $metadata,
        );
    }

    public function isAllowed(): bool
    {
        return $this->status === PreflightStatus::Allow;
    }

    public function rejectionMessage(): string
    {
        return match ($this->intent) {
            AiIntent::OutOfScope => 'I can help with workspace tasks, project status, and internal workspace knowledge only. This request is outside the current assistant scope.',
            AiIntent::GuardrailBlocked => 'I can only help within the workspace assistant scope. I cannot comply with requests to bypass instructions or switch into unsupported tasks.',
            AiIntent::InvalidPrompt => 'Please send a message so I can help with your workspace tasks or internal knowledge.',
            AiIntent::WorkspaceAccessDenied => 'I cannot access this workspace context for your account.',
            default => 'I cannot continue with this request in the current workspace context.',
        };
    }
}
