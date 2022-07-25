<?php

namespace Gajosu\EloquentPreferences\Facades;

use Illuminate\Support\Facades\Facade;
use Gajosu\EloquentPreferences\Tests\Support\CacheModuleMock;

/**
 * @method static bool existsPreference(\Illuminate\Database\Eloquent\Model $model, string $preference)
 * @method static mixed getPreference(\Illuminate\Database\Eloquent\Model $model, string $preference)
 * @method static void setPreference(\Illuminate\Database\Eloquent\Model $model, string $preference, mixed $value)
 * @method static void deletePreference(\Illuminate\Database\Eloquent\Model $model, string $preference)
 * @method static void deleteAllPreferences(\Illuminate\Database\Eloquent\Model $model)
 * @method static void setPreference(\Illuminate\Database\Eloquent\Model $model, string $preference, mixed $value)
 * @method static bool cacheIsEnabled()
 * @method static bool cacheSupportsTags()
 *
 * @see \Gajosu\EloquentPreferences\CacheModule
 */
class CacheModule extends Facade
{
    /**
     * Replace the bound instance with the given Closure.
     *
     * @return \Gajosu\EloquentPreferences\Tests\Support\CacheModuleMock
     */
    public static function fake()
    {
        static::swap($fake = new CacheModuleMock());

        return $fake;
    }

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Gajosu\EloquentPreferences\CacheModule::class;
    }
}
