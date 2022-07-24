<?php

namespace Gajosu\EloquentPreferences;

use Illuminate\Support\Str;
use Illuminate\Cache\TaggedCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class CacheModule
{
    /**
     * Returns true if preferences are cached.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $preference
     * @return bool
     */
    public static function existsPreference(Model $model, string $preference): bool
    {
        return self::getCacheBuilder($model)->has(self::getCacheKey($preference));
    }

    /**
     * Get preferences from cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $preference
     * @return mixed
     */
    public static function getPreference(Model $model, string $preference): mixed
    {
        return self::getCacheBuilder($model)->get(self::getCacheKey($preference));
    }

    /**
     * Set preferences in cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $preference
     * @param mixed $value
     * @return void
     */
    public static function setPreference(Model $model, string $preference, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        self::getCacheBuilder($model)->forever(self::getCacheKey($preference), $value);
    }

    /**
     * Delete preferences from cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $preference
     * @return void
     */
    public static function deletePreference(Model $model, string $preference): void
    {
        self::getCacheBuilder($model)->forget(self::getCacheKey($preference));
    }

    /**
     * Delete all preferences from cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public static function deleteAllPreferences(Model $model): void
    {
        self::getCacheBuilder($model)->flush();
    }

    /**
     * Determine if caching is enabled.
     *
     * @return bool
     */
    public static function cacheIsEnabled(): bool
    {
        return config('eloquent-preferences.cache.enabled');
    }

    /**
     * Get the cache prefix.
     *
     * @return string
     */
    public static function cachePrefix(): string
    {
        return config('eloquent-preferences.cache.prefix');
    }

    /**
     * Get the cache key.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    public static function getCacheTag(Model $model): string
    {
        return self::cachePrefix() . '_' . $model::class . '_' . $model->getKey();
    }

    /**
     * Get cache key.
     *
     * @param string $preference
     * @return string
     */
    public static function getCacheKey(string $preference): string
    {
        return self::cachePrefix() . ':' . Str::slug($preference);
    }

    /**
     * Get cache builder.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Cache\TaggedCache
     */
    private static function getCacheBuilder(Model $model): TaggedCache
    {
        return Cache::tags([self::getCacheTag($model)]);
    }
}
