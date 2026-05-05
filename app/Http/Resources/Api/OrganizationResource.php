<?php

namespace App\Http\Resources\Api;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->logo,
            'is_active' => $this->is_active,
            'invite_code' => $this->invite_code,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
