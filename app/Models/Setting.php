<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'bool', 'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer' => (int) $setting->value,
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }

    public static function put(string $key, mixed $value, string $type = 'string'): void
    {
        if (is_bool($value)) {
            $type = 'bool';
            $value = $value ? '1' : '0';
        } elseif (is_array($value)) {
            $type = 'json';
            $value = json_encode($value);
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
    }
}
