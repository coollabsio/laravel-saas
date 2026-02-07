<?php

namespace Coollabsio\LaravelSaas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class InstanceSettings extends Model
{
    protected $fillable = [
        'registration_enabled',
    ];

    protected function casts(): array
    {
        return [
            'registration_enabled' => 'boolean',
        ];
    }

    public static function current(): static
    {
        return Cache::remember('instance_settings', 60, function () {
            return static::firstOrCreate(['id' => 1], ['registration_enabled' => true]);
        });
    }

    public static function registrationEnabled(): bool
    {
        return static::current()->registration_enabled;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('instance_settings');
        });
    }
}
