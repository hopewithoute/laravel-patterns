<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * General application settings.
 */
class GeneralSettings extends Settings
{
    public string $app_name;

    public string $logo_light;

    public string $logo_dark;

    public string $favicon;

    public bool $allow_registration;

    public static function group(): string
    {
        return 'general';
    }
}
