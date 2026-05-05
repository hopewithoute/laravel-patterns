<?php

namespace App\Http\Resources\Api;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships - only when loaded
            'user' => new UserResource($this->whenLoaded('user')),
            'task' => new TaskResource($this->whenLoaded('task')),
        ];
    }
}
