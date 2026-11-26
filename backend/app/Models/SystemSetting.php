<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'group',
    ];

    /**
     * Obtiene el valor de una configuración del sistema con caché liviana.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::remember("sys_setting_{$key}", 60, function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                return $setting !== null ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Guarda o actualiza una configuración del sistema e invalida su caché.
     */
    public static function set(string $key, mixed $value, ?string $description = null, string $group = 'general'): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'description' => $description,
                'group' => $group,
            ]
        );

        Cache::forget("sys_setting_{$key}");

        return $setting;
    }
}
