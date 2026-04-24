<?php

namespace App\AI\Runtime\Tools;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\ToolAccessResolver;
use App\AI\Runtime\Enums\PreflightStatus;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Tools\Registry\ToolRegistry;
use Traversable;

readonly class WorkspaceToolAccessResolver implements ToolAccessResolver
{
    public function __construct(
        private ToolRegistry $toolRegistry,
    ) {}

    /**
     * @param  iterable<mixed>  $tools
     * @return array<int, mixed>
     */
    public function resolve(AiRuntimeContext $context, PreflightDecision $decision, iterable $tools): array
    {
        if ($decision->status !== PreflightStatus::Allow) {
            return [];
        }

        $allowedCapabilities = $decision->allowedCapabilities;
        $normalizedTools = $this->normalizeTools($tools);

        return array_values(array_filter(
            $normalizedTools,
            fn (mixed $tool): bool => $this->toolIsAllowed($tool, $allowedCapabilities),
        ));
    }

    /**
     * @param  iterable<mixed>  $tools
     * @return array<int, mixed>
     */
    private function normalizeTools(iterable $tools): array
    {
        return match (true) {
            is_array($tools) => array_values($tools),
            $tools instanceof Traversable => array_values(iterator_to_array($tools, false)),
            default => [],
        };
    }

    /**
     * @param  array<int, string>  $allowedCapabilities
     */
    private function toolIsAllowed(mixed $tool, array $allowedCapabilities): bool
    {
        $capability = $this->toolRegistry->findByTool($tool)?->capability;

        if ($capability === null) {
            return $allowedCapabilities === [];
        }

        return in_array($capability, $allowedCapabilities, true);
    }
}
