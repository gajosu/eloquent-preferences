<?php

namespace Gajosu\EloquentPreferences;

use Illuminate\Support\Str;
use Illuminate\Cache\TaggedCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Cache\Repository;

class CacheModule
{
    /**
     * Returns true if preferences are cached.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $preference
     * @return bool
     */
    public function existsPreference(Model $model, string $preference): bool
    {
        return $this->getCacheBuilder($model)->has($this->getCacheKey($model, $preference));
    }

    /**
     * Get preferences from cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $preference
     * @return mixed
     */
    public function getPreference(Model $model, string $preference): mixed
    {
        return $this->getCacheBuilder($model)->get($this->getCacheKey($model, $preference));
    }

    /**
     * Set preferences in cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $preference
     * @param mixed $value
     * @return void
     */
    public function setPreference(Model $model, string $preference, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $this->getCacheBuilder($model)->forever($this->getCacheKey($model, $preference), $value);
    }

    /**
     * Delete preferences from cache.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $preference
     * @return void
     */
    public function deletePreference(Model $model, string $preference): void
    {
        $this->getCacheBuilder($model)->forget($this->getCacheKey($model, $preference));
    }

    /**
     * Delete all preferences from cache.
     * Only for drivers with tags support.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function deleteAllPreferences(Model $model): void
    {
        /** @var \Illuminate\Cache\TaggedCache */
        $builder = $this->getCacheBuilder($model);
        $builder->flush();
    }

    /**
     * Determine if caching is enabled.
     *
     * @return bool
     */
    public function cacheIsEnabled(): bool
    {
        return config('eloquent-preferences.cache.enabled');
    }

    /**
     * Get the cache prefix.
     *
     * @return string
     */
    protected function cachePrefix(): string
    {
        return config('eloquent-preferences.cache.prefix');
    }

    /**
     * Get the cache key.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    protected function getCacheTag(Model $model): string
    {
        return $this->cachePrefix() . '_' . $model::class . '_' . $model->getKey();
    }

    /**
     * Get cache key.
     *
     * @param string $preference
     * @return string
     */
    protected function getCacheKey(Model $model, string $preference): string
    {
        if ($this->cacheSupportsTags()) {
            return $this->cachePrefix() . ':' . Str::slug($preference);
        }

        return $this->getCacheTag($model) . ':' . Str::slug($preference);
    }

    /**
     * Get cache builder.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Cache\TaggedCache|\Illuminate\Contracts\Cache\Repository
     */
    protected function getCacheBuilder(Model $model): TaggedCache|Repository
    {
        /** @var \Illuminate\Cache\Repository */
        $repository = app('cache')->driver();

        if ($this->cacheSupportsTags()) {
            return $repository->tags([$this->getCacheTag($model)]);
        }

        return $repository;
    }

    /**
     * Returns true if the cache driver supports tags.
     *
     * @return bool
     */
    public function cacheSupportsTags(): bool
    {
        /** @var \Illuminate\Cache\Repository */
        $repository = app('cache')->driver();

        return $repository->supportsTags();
    }
}
