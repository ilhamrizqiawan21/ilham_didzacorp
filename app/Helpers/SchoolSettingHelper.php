<?php

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Illuminate\Support\Env::get($key, $default);
    }
}

if (! function_exists('school_setting')) {
    function school_setting(?string $key = null, mixed $default = null): mixed
    {
        $resolveSettings = function (): array {
            try {
                $setting = SchoolSetting::query()->where('singleton_key', SchoolSetting::SINGLETON_ID)->first();

                return array_merge(
                    SchoolSetting::fallback(),
                    $setting ? $setting->toArray() : []
                );
            } catch (Throwable) {
                return SchoolSetting::fallback();
            }
        };

        try {
            $settings = Cache::remember('school_settings.current', 3600, $resolveSettings);
        } catch (Throwable) {
            $settings = $resolveSettings();
        }

        if ($key === null) {
            return $settings;
        }

        return $settings[$key] ?? $default;
    }
}

if (! function_exists('clear_school_setting_cache')) {
    function clear_school_setting_cache(): void
    {
        try {
            Cache::forget('school_settings.current');
            Cache::forget('school_settings.logo_url');
            Cache::forget('school_settings.favicon_url');
        } catch (Throwable) {
            return;
        }
    }
}

if (! function_exists('school_logo_url')) {
    function school_logo_url(): string
    {
        return Cache::remember('school_settings.logo_url', 3600, function (): string {
            $path = school_setting('logo_path');

            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::url($path);
            }

            return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22%3E%3Crect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%23198754%22/%3E%3Cpath d=%22M14 49h36V27L32 15 14 27v22Z%22 fill=%22%23ffffff%22/%3E%3Cpath d=%22M24 49V34h16v15%22 fill=%22%23198754%22/%3E%3Cpath d=%22M20 29h24v5H20z%22 fill=%22%23198754%22 opacity=%22.25%22/%3E%3C/svg%3E';
        });
    }
}

if (! function_exists('school_favicon_url')) {
    function school_favicon_url(): string
    {
        return Cache::remember('school_settings.favicon_url', 3600, function (): string {
            $path = school_setting('favicon_path');

            if ($path && Storage::disk('public')->exists($path)) {
                return Storage::url($path);
            }

            return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22%3E%3Crect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%23198754%22/%3E%3Cpath d=%22M16 47h32V28L32 17 16 28v19Z%22 fill=%22%23ffffff%22/%3E%3Cpath d=%22M27 47V35h10v12%22 fill=%22%23198754%22/%3E%3C/svg%3E';
        });
    }
}
