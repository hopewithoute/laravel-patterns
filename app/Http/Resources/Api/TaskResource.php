<?php

namespace App\Http\Resources\Api;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'due_date' => $this->due_date?->toDateString(),
            'sort_order' => $this->sort_order,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships - only when loaded
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'project' => new ProjectResource($this->whenLoaded('project')),
        ];
    }
}
