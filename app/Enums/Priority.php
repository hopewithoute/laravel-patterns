<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Priority enum for tasks.
 */
final class Priority extends Enum
{
    public const Low = 'Low';

    public const Medium = 'Medium';

    public const High = 'High';

    public const Urgent = 'Urgent';

    public static function asOptions(): array
    {
        return [
            ['text' => 'Low', 'value' => self::Low],
            ['text' => 'Medium', 'value' => self::Medium],
            ['text' => 'High', 'value' => self::High],
            ['text' => 'Urgent', 'value' => self::Urgent],
        ];
    }

    /**
     * Get priority color for UI display.
     */
    public static function getColor(string $priority): string
    {
        return match ($priority) {
            self::Low => 'gray',
            self::Medium => 'blue',
            self::High => 'orange',
            self::Urgent => 'red',
            default => 'gray',
        };
    }

    /**
     * Get priority weight for sorting.
     */
    public static function getWeight(string $priority): int
    {
        return match ($priority) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
            default => 0,
        };
    }
}
