<?php

namespace App\AI\Runtime\Artifacts;

readonly class RuntimeArtifactModeCatalog
{
    /**
     * @param  array<int, array{value: string, label: string}>  $modes
     */
    public function __construct(
        private array $modes = [],
    ) {}

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function options(): array
    {
        return array_values(array_filter(
            $this->modes,
            fn (array $mode): bool => isset($mode['value'], $mode['label'])
                && is_string($mode['value'])
                && $mode['value'] !== ''
                && is_string($mode['label'])
                && $mode['label'] !== '',
        ));
    }

    /**
     * @return array<int, string>
     */
    public function values(): array
    {
        return array_values(array_map(
            fn (array $mode): string => $mode['value'],
            $this->options(),
        ));
    }
}
