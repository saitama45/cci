<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'key', 'value', 'group'];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null, $companyId = null)
    {
        $setting = self::where('key', $key)
            ->where('company_id', $companyId)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function set($key, $value, $group = 'general', $companyId = null)
    {
        return self::updateOrCreate(
            ['key' => $key, 'company_id' => $companyId],
            ['value' => $value, 'group' => $group]
        );
    }
}
