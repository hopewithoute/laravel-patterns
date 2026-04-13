<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * Task status enum for Task Management.
 */
final class TaskStatus extends Enum
{
    public const Todo = 'Todo';

    public const InProgress = 'In Progress';

    public const Review = 'Review';

    public const Done = 'Done';

    public static function asOptions(): array
    {
        return [
            ['text' => 'To Do', 'value' => self::Todo],
            ['text' => 'In Progress', 'value' => self::InProgress],
            ['text' => 'Review', 'value' => self::Review],
            ['text' => 'Done', 'value' => self::Done],
        ];
    }

    /**
     * Get status color for UI display.
     */
    public static function getColor(string $status): string
    {
        return match ($status) {
            self::Todo => 'gray',
            self::InProgress => 'blue',
            self::Review => 'yellow',
            self::Done => 'green',
            default => 'gray',
        };
    }

    /**
     * Get open statuses (not done).
     */
    public static function openStatuses(): array
    {
        return [
            self::Todo,
            self::InProgress,
            self::Review,
        ];
    }

    /**
     * Check if status is completed.
     */
    public static function isCompleted(string $status): bool
    {
        return $status === self::Done;
    }
}
