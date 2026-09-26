<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingProvider extends Model
{
    protected $guarded = [];

    public const DEFAULT_NAME = 'Standard Shipping';

    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }

    public static function ensureDefaultProvider(): self
    {
        return static::query()->firstOrCreate([
            'name' => self::DEFAULT_NAME,
        ]);
    }

    public static function getDefaultProviderId(): ?int
    {
        return static::ensureDefaultProvider()->id;
    }

    public static function getDefaultProvider(): ?self
    {
        return static::ensureDefaultProvider();
    }
}
