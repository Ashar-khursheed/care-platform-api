<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            // Parse value based on type
            return static::parseValue($setting->value, $setting->type);
        });
    }

    /**
     * Set setting value by key
     */
    public static function set($key, $value, $type = 'text')
    {
        // Encode value based on type
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? 'true' : 'false';
        }

        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );

        // Clear cache
        Cache::forget("site_setting_{$key}");

        return $setting;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup($group)
    {
        return Cache::remember("site_settings_group_{$group}", 3600, function () use ($group) {
            $settings = static::where('group', $group)->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::parseValue($setting->value, $setting->type);
            }
            
            return $result;
        });
    }

    /**
     * Get all settings
     */
    public static function getAll()
    {
        return Cache::remember('site_settings_all', 3600, function () {
            $settings = static::all();
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::parseValue($setting->value, $setting->type);
            }
            
            return $result;
        });
    }

    /**
     * Parse value based on type
     */
    protected static function parseValue($value, $type)
    {
        switch ($type) {
            case 'json':
                return json_decode($value, true) ?? [];
            case 'boolean':
                return $value === 'true' || $value === '1' || $value === 1;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("site_setting_{$setting->key}");
        }
        
        $groups = static::distinct('group')->pluck('group');
        foreach ($groups as $group) {
            Cache::forget("site_settings_group_{$group}");
        }
        
        Cache::forget('site_settings_all');
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("site_setting_{$setting->key}");
            Cache::forget("site_settings_group_{$setting->group}");
            Cache::forget('site_settings_all');
        });

        static::deleted(function ($setting) {
            Cache::forget("site_setting_{$setting->key}");
            Cache::forget("site_settings_group_{$setting->group}");
            Cache::forget('site_settings_all');
        });
    }
}