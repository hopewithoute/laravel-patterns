<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property-read bool $is_expired
 * @property-read string $formatted_created_at
 */
class Token extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected function isExpired(): Attribute
    {
        return Attribute::get(function () {
            if ($this->expires_at === null) {
                return false;
            }

            return $this->expires_at->isPast();
        });
    }

    protected function formattedCreatedAt(): Attribute
    {
        return Attribute::get(fn () => Carbon::parse($this->created_at)->format('d M Y H:i'));
    }
}
