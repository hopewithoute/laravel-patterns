<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiChatSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'conversation_id' => $this->conversation_id,
            'updated_at' => $this->updated_at?->toISOString(),
            'last_message_at' => $this->last_message_at?->toISOString(),
        ];
    }
}
